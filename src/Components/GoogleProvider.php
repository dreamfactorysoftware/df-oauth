<?php
namespace DreamFactory\Core\OAuth\Components;

use Illuminate\Http\Request;
use SocialiteProviders\Manager\OAuth2\User;
use Illuminate\Support\Arr;

/**
 * Class GoogleProvider
 *
 * @package DreamFactory\Core\OAuth\Components
 */
class GoogleProvider extends \Laravel\Socialite\Two\GoogleProvider
{
    use DfOAuthTwoProvider;

    /**
     * @var bool Whether to fetch user groups
     */
    protected $fetchGroups = false;

    /**
     * @param Request $clientId
     * @param string  $clientSecret
     * @param string  $redirectUrl
     */
    public function __construct($clientId, $clientSecret, $redirectUrl)
    {
        /** @var Request $request */
        $request = \Request::instance();
        parent::__construct($request, $clientId, $clientSecret, $redirectUrl);
    }

    /**
     * Enable group fetching and add the required scope.
     *
     * @return $this
     */
    public function enableGroupFetching()
    {
        $this->fetchGroups = true;
        $this->scopes = array_unique(array_merge($this->scopes, [
            'https://www.googleapis.com/auth/cloud-identity.groups.readonly'
        ]));

        return $this;
    }

    /**
     * Get the user's groups from Google Cloud Identity API.
     *
     * @param string $token
     * @param string $userEmail
     * @return array
     */
    protected function getUserGroups($token, $userEmail)
    {
        try {
            \Log::info('Google OAuth: Fetching groups for user', ['email' => $userEmail]);

            // Use searchTransitiveGroups which is more reliable and includes nested group memberships
            // Query format: member_key_id == 'user@domain.com'
            $query = "member_key_id == '" . $userEmail . "'";
            $url = "https://cloudidentity.googleapis.com/v1/groups/-/memberships:searchTransitiveGroups?"
                 . http_build_query(['query' => $query]);

            \Log::debug('Google OAuth: Groups API URL', ['url' => $url]);

            $response = $this->getHttpClient()->get($url, [
                'headers' => ['Authorization' => 'Bearer ' . $token]
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            \Log::info('Google OAuth: Groups API response', [
                'status' => $response->getStatusCode(),
                'body' => $body
            ]);

            $groups = [];

            if (isset($data['memberships']) && is_array($data['memberships'])) {
                foreach ($data['memberships'] as $membership) {
                    // Extract group email from groupKey
                    if (isset($membership['groupKey']['id'])) {
                        $groups[] = [
                            'id'    => $membership['group'] ?? null,
                            'email' => $membership['groupKey']['id'],
                        ];
                    }
                }
            }

            \Log::info('Google OAuth: Parsed groups', ['groups' => $groups]);

            return $groups;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $body = $response ? $response->getBody()->getContents() : 'No response body';
            \Log::error('Google OAuth: Groups API client error', [
                'status' => $response ? $response->getStatusCode() : 'unknown',
                'error' => $e->getMessage(),
                'response' => $body
            ]);
            return [];
        } catch (\Exception $e) {
            \Log::error('Google OAuth: Failed to fetch groups', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://www.googleapis.com/oauth2/v3/userinfo',
            $this->getRequestOptions($token)
        );

        $user = json_decode($response->getBody()->getContents(), true);

        if ($this->fetchGroups && !empty($user['email'])) {
            $user['groups'] = $this->getUserGroups($token, $user['email']);
        }

        return $user;
    }

    /**
     * Get the request options for the HTTP client.
     *
     * @param string $token
     * @return array
     */
    protected function getRequestOptions($token)
    {
        return [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $avatarUrl = Arr::get($user, 'picture');

        return (new User)->setRaw($user)->map([
            'id'       => Arr::get($user, 'sub', Arr::get($user, 'id')),
            'nickname' => Arr::get($user, 'nickname'),
            'name' => Arr::get($user, 'name'),
            'email' => Arr::get($user, 'email'),
            'avatar' => $avatarUrl,
            'avatar_original' => preg_replace('/\?sz=([0-9]+)/', '', $avatarUrl),
        ]);
    }
}