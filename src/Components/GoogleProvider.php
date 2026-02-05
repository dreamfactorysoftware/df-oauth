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
            'https://www.googleapis.com/auth/directory.readonly',
            'https://www.googleapis.com/auth/admin.directory.group.readonly',
        ]));

        return $this;
    }

    /**
     * Get the user's groups - tries multiple Google APIs.
     *
     * @param string $token
     * @param string $userEmail
     * @return array
     */
    protected function getUserGroups($token, $userEmail)
    {
        \Log::info('Google OAuth: Fetching groups for user', ['email' => $userEmail]);

        // Try Admin Directory API first (most reliable if user has permissions)
        $groups = $this->getGroupsFromAdminDirectory($token, $userEmail);

        if (!empty($groups)) {
            return $groups;
        }

        // Try People API Directory as fallback
        $groups = $this->getGroupsFromPeopleDirectory($token);

        return $groups;
    }

    /**
     * Get groups from Admin Directory API.
     *
     * @param string $token
     * @param string $userEmail
     * @return array
     */
    protected function getGroupsFromAdminDirectory($token, $userEmail)
    {
        try {
            $url = "https://admin.googleapis.com/admin/directory/v1/groups?"
                 . http_build_query(['userKey' => $userEmail]);

            \Log::debug('Google OAuth: Trying Admin Directory API', ['url' => $url]);

            $response = $this->getHttpClient()->get($url, [
                'headers' => ['Authorization' => 'Bearer ' . $token]
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            \Log::info('Google OAuth: Admin Directory API response', [
                'status' => $response->getStatusCode(),
                'groups_count' => isset($data['groups']) ? count($data['groups']) : 0
            ]);

            $groups = [];
            if (isset($data['groups']) && is_array($data['groups'])) {
                foreach ($data['groups'] as $group) {
                    $groups[] = [
                        'id'    => $group['id'] ?? null,
                        'email' => $group['email'] ?? null,
                        'name'  => $group['name'] ?? null,
                    ];
                }
            }

            \Log::info('Google OAuth: Parsed groups from Admin Directory', ['groups' => $groups]);
            return $groups;

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $body = $response ? $response->getBody()->getContents() : 'No response body';
            \Log::warning('Google OAuth: Admin Directory API error', [
                'status' => $response ? $response->getStatusCode() : 'unknown',
                'response' => $body
            ]);
            return [];
        } catch (\Exception $e) {
            \Log::warning('Google OAuth: Admin Directory API exception', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get groups from People API Directory.
     *
     * @param string $token
     * @return array
     */
    protected function getGroupsFromPeopleDirectory($token)
    {
        try {
            $url = "https://people.googleapis.com/v1/people/me?"
                 . http_build_query(['personFields' => 'memberships']);

            \Log::debug('Google OAuth: Trying People API', ['url' => $url]);

            $response = $this->getHttpClient()->get($url, [
                'headers' => ['Authorization' => 'Bearer ' . $token]
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            \Log::info('Google OAuth: People API response', ['body' => $body]);

            $groups = [];
            if (isset($data['memberships']) && is_array($data['memberships'])) {
                foreach ($data['memberships'] as $membership) {
                    $groups[] = [
                        'id'    => $membership['metadata']['source']['id'] ?? null,
                        'type'  => $membership['metadata']['source']['type'] ?? null,
                        'email' => null, // People API doesn't return group email
                    ];
                }
            }

            return $groups;

        } catch (\Exception $e) {
            \Log::warning('Google OAuth: People API error', ['error' => $e->getMessage()]);
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