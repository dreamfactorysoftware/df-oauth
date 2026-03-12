<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\Models\User;
use DreamFactory\Core\OAuth\Components\GoogleProvider;
use DreamFactory\Core\OAuth\Models\RoleGoogle;
use Laravel\Socialite\Contracts\User as OAuthUserContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class Google extends BaseOAuthService
{
    /**
     * OAuth service provider name.
     */
    const PROVIDER_NAME = 'google';

    /**
     * Whether to map groups to roles.
     *
     * @var bool
     */
    protected $mapGroupToRole = false;

    /**
     * {@inheritdoc}
     */
    protected function setProvider($config)
    {
        $clientId = Arr::get($config, 'client_id');
        $clientSecret = Arr::get($config, 'client_secret');
        $redirectUrl = Arr::get($config, 'redirect_url');
        $this->mapGroupToRole = Arr::get($config, 'map_group_to_role', false);

        $this->provider = new GoogleProvider($clientId, $clientSecret, $redirectUrl);

        if ($this->mapGroupToRole) {
            $this->provider->enableGroupFetching();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName()
    {
        return self::PROVIDER_NAME;
    }

    /**
     * Get role ID based on user's group membership.
     *
     * @param array $groups
     * @return int|null
     */
    protected function getRoleByGroup(array $groups)
    {
        if (!$this->mapGroupToRole || empty($groups)) {
            Log::debug('Google OAuth: getRoleByGroup early return', [
                'map_group_to_role' => $this->mapGroupToRole,
                'groups_empty' => empty($groups),
            ]);
            return null;
        }

        foreach ($groups as $group) {
            $groupEmail = Arr::get($group, 'email');
            if (!empty($groupEmail)) {
                $role = $this->findRoleByGroupEmail($groupEmail);
                Log::debug('Google OAuth: Group match attempt', [
                    'group_email' => $groupEmail,
                    'match_found' => !empty($role),
                    'role_id' => $role->role_id ?? null,
                ]);
                if (!empty($role)) {
                    return $role->role_id;
                }
            }
        }

        Log::warning('Google OAuth: No group matched any configured role mapping', [
            'user_groups' => array_column($groups, 'email'),
        ]);

        return null;
    }

    /**
     * Find a role mapping by Google group email.
     *
     * @param string $groupEmail
     * @return RoleGoogle|null
     */
    protected function findRoleByGroupEmail($groupEmail)
    {
        if (empty($groupEmail)) {
            return null;
        }

        return RoleGoogle::whereGroupEmail($groupEmail)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function createShadowOAuthUser(OAuthUserContract $OAuthUser)
    {
        $fullName = $OAuthUser->getName() || $OAuthUser->getNickname();
        @list($firstName, $lastName) = explode(' ', $fullName);

        $email = $OAuthUser->getEmail();
        $serviceName = $this->getName();
        $providerName = $this->getProviderName();

        if (empty($email)) {
            $email = $OAuthUser->getId() . '+' . $serviceName . '@' . $serviceName . '.com';
        } else {
            list($emailId, $domain) = explode('@', $email);
            $email = $emailId . '+' . $serviceName . '@' . $domain;
        }

        $user = User::whereEmail($email)->first();

        if (empty($user)) {
            $config = Arr::get($this->config, 'allow_new_users', true);
            if (!$config) {
                throw new \DreamFactory\Core\Exceptions\UnauthorizedException(
                    'New user registration is not allowed for this OAuth service. ' .
                    'Please contact your administrator to create an account or enable new user registration.'
                );
            }

            $data = [
                'username'       => $email,
                'name'           => $fullName,
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'email'          => $email,
                'is_active'      => true,
                'oauth_provider' => $providerName,
            ];

            $user = User::create($data);
        }

        // Priority: Group mapping > App role map > Default role
        $roleToApply = null;

        Log::debug('Google OAuth: Role assignment starting', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'map_group_to_role' => $this->mapGroupToRole,
            'default_role' => $this->getDefaultRole(),
            'service_id' => $this->getServiceId(),
        ]);

        if ($this->mapGroupToRole) {
            $userRaw = $OAuthUser->getRaw();
            $groups = Arr::get($userRaw, 'groups', []);

            Log::debug('Google OAuth: Group data from OAuth user', [
                'group_count' => count($groups),
                'group_emails' => array_column($groups, 'email'),
                'raw_keys' => array_keys($userRaw),
            ]);

            $roleToApply = $this->getRoleByGroup($groups);

            Log::debug('Google OAuth: Group-to-role lookup result', [
                'role_from_group' => $roleToApply,
                'configured_mappings' => RoleGoogle::all()->toArray(),
            ]);
        }

        if (empty($roleToApply)) {
            if (!empty($defaultRole = $this->getDefaultRole())) {
                $roleToApply = $defaultRole;
                Log::debug('Google OAuth: Falling back to default role', ['role_id' => $defaultRole]);
            }
        }

        // Always refresh role assignments on login to reflect current group membership
        if (!empty($roleToApply)) {
            Log::debug('Google OAuth: Applying role', [
                'user_id' => $user->id,
                'role_id' => $roleToApply,
                'source' => ($this->mapGroupToRole && $roleToApply !== $this->getDefaultRole()) ? 'group_mapping' : 'default_role',
            ]);
            \DB::table('user_to_app_to_role')->where('user_id', $user->id)->delete();
            User::applyDefaultUserAppRole($user, $roleToApply);
        } elseif (!empty($serviceId = $this->getServiceId())) {
            Log::debug('Google OAuth: Applying service app role map', ['service_id' => $serviceId]);
            \DB::table('user_to_app_to_role')->where('user_id', $user->id)->delete();
            User::applyAppRoleMapByService($user, $serviceId);
        } else {
            Log::warning('Google OAuth: No role applied! User will have no permissions.', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
        }

        return $user;
    }
}
