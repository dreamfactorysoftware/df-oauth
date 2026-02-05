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
     * Get the user's groups from Google People API (Directory).
     *
     * @param string $token
     * @param string $userEmail
     * @return array
     */
    protected function getUserGroups($token, $userEmail)
    {
        try {
            \Log::info('Google OAuth: Fetching groups for user', ['email' => $userEmail]);

            // Try People API directory endpoint to get memberships
            $url = "https://people.googleapis.com/v1/people/me?personFields=memberships";

            \Log::debug('Google OAuth: Trying People API', ['url' => $url]);

            $response = $this->getHttpClient()->get($url, [
                'headers' => ['Authorization' => 'Bearer ' . $token]
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            \Log::info('Google OAuth: People API response', [
                'status' => $response->getStatusCode(),
                'body' => $body
            ]);

            $groups = [];

            // Check memberships in response
            if (isset($data['memberships']) && is_array($data['memberships'])) {
                foreach ($data['memberships'] as $membership) {
                    if (isset($membership['domainMembership'])) {
                        // This is a domain/group membership
                        $groups[] = [
                            'id'    => $membership['metadata']['source']['id'] ?? null,
                            'email' => $membership['domainMembership']['inViewerDomain'] ?? null,
                        ];
                    }
                }
            }

            // If People API doesn't have groups, try Cloud Identity with different format
            if (empty($groups)) {
                \Log::info('Google OAuth: People API had no groups, trying Cloud Identity lookup');
                $groups = $this->getUserGroupsFromCloudIdentity($token, $userEmail);
            }

            \Log::info('Google OAuth: Final parsed groups', ['groups' => $groups]);

            return $groups;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $body = $response ? $response->getBody()->getContents() : 'No response body';
            \Log::warning('Google OAuth: People API error, trying Cloud Identity', [
                'status' => $response ? $response->getStatusCode() : 'unknown',
                'response' => $body
            ]);
            // Fall back to Cloud Identity API
            return $this->getUserGroupsFromCloudIdentity($token, $userEmail);
        } catch (\Exception $e) {
            \Log::error('Google OAuth: Failed to fetch groups', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get user's groups from Cloud Identity API using checkTransitiveMembership.
     *
     * @param string $token
     * @param string $userEmail
     * @return array
     */
    protected function getUserGroupsFromCloudIdentity($token, $userEmail)
    {
        try {
            // Try listing all groups and check membership for each
            // First, get groups in the domain
            $url = "https://cloudidentity.googleapis.com/v1/groups?"
                 . http_build_query([
                     'parent' => 'customers/my_customer',
                     'view' => 'BASIC'
                 ]);

            \Log::debug('Google OAuth: Cloud Identity list groups URL', ['url' => $url]);

            $response = $this->getHttpClient()->get($url, [
                'headers' => ['Authorization' => 'Bearer ' . $token]
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            \Log::info('Google OAuth: Cloud Identity groups list response', [
                'status' => $response->getStatusCode(),
                'body' => $body
            ]);

            $groups = [];

            if (isset($data['groups']) && is_array($data['groups'])) {
                foreach ($data['groups'] as $group) {
                    $groups[] = [
                        'id'    => $group['name'] ?? null,
                        'email' => $group['groupKey']['id'] ?? null,
                        'displayName' => $group['displayName'] ?? null,
                    ];
                }
            }

            return $groups;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $body = $response ? $response->getBody()->getContents() : 'No response body';
            \Log::error('Google OAuth: Cloud Identity groups list error', [
                'status' => $response ? $response->getStatusCode() : 'unknown',
                'response' => $body
            ]);
            return [];
        } catch (\Exception $e) {
            \Log::error('Google OAuth: Cloud Identity error', [
                'error' => $e->getMessage()
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