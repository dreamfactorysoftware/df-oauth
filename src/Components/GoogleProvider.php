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
     * Enable group fetching and add the required scopes.
     *
     * @return $this
     */
    public function enableGroupFetching()
    {
        $this->fetchGroups = true;
        $this->scopes = array_unique(array_merge($this->scopes, [
            'https://www.googleapis.com/auth/cloud-identity.groups.readonly',
        ]));

        return $this;
    }

    /**
     * Get the user's groups from Google Cloud Identity API.
     *
     * Uses the searchDirectGroups endpoint which allows any Google Workspace
     * user to query their own group memberships via a standard OAuth 2.0 token.
     *
     * @param string $token
     * @param string $userEmail
     * @return array
     */
    protected function getUserGroups($token, $userEmail)
    {
        try {
            $groups = [];
            $pageToken = null;

            do {
                $query = [
                    'query' => "member_key_id == '" . $userEmail . "'",
                    'page_size' => 1000,
                ];

                if ($pageToken) {
                    $query['page_token'] = $pageToken;
                }

                $response = $this->getHttpClient()->get(
                    'https://cloudidentity.googleapis.com/v1/groups/-/memberships:searchDirectGroups',
                    [
                        'headers' => ['Authorization' => 'Bearer ' . $token],
                        'query' => $query,
                    ]
                );

                $data = json_decode($response->getBody()->getContents(), true);

                \Log::debug('Google OAuth: Cloud Identity API response', [
                    'user_email' => $userEmail,
                    'membership_count' => isset($data['memberships']) ? count($data['memberships']) : 0,
                    'raw_keys' => array_keys($data ?? []),
                ]);

                if (isset($data['memberships']) && is_array($data['memberships'])) {
                    foreach ($data['memberships'] as $membership) {
                        $groups[] = [
                            'id'    => $membership['group'] ?? null,
                            'email' => $membership['groupKey']['id'] ?? null,
                            'name'  => $membership['displayName'] ?? null,
                        ];
                    }
                }

                $pageToken = $data['nextPageToken'] ?? null;
            } while ($pageToken);

            \Log::debug('Google OAuth: Groups fetched successfully', [
                'user_email' => $userEmail,
                'group_count' => count($groups),
                'group_emails' => array_column($groups, 'email'),
            ]);

            return $groups;

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $body = $response ? $response->getBody()->getContents() : 'No response body';
            \Log::warning('Google OAuth: Failed to fetch groups from Cloud Identity API', [
                'status' => $response ? $response->getStatusCode() : 'unknown',
                'error' => $body
            ]);
            return [];
        } catch (\Exception $e) {
            \Log::warning('Google OAuth: Failed to fetch groups', ['error' => $e->getMessage()]);
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
