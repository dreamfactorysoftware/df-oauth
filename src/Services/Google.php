<?php
namespace DreamFactory\Core\OAuth\Services;

use DreamFactory\Core\Models\User;
use DreamFactory\Core\OAuth\Components\GoogleProvider;
use DreamFactory\Core\OAuth\Models\RoleGoogle;
use Laravel\Socialite\Contracts\User as OAuthUserContract;
use Illuminate\Support\Arr;

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
            \Log::debug('Google OAuth: getRoleByGroup - early exit', [
                'mapGroupToRole' => $this->mapGroupToRole,
                'groups_empty' => empty($groups)
            ]);
            return null;
        }

        // Log configured mappings for debugging
        $configuredMappings = RoleGoogle::all()->toArray();
        \Log::info('Google OAuth: Configured group-role mappings', ['mappings' => $configuredMappings]);

        foreach ($groups as $group) {
            $groupEmail = Arr::get($group, 'email');
            \Log::debug('Google OAuth: Checking group', ['group_email' => $groupEmail]);

            if (!empty($groupEmail)) {
                $role = $this->findRoleByGroupEmail($groupEmail);
                if (!empty($role)) {
                    \Log::info('Google OAuth: Found matching role', [
                        'group_email' => $groupEmail,
                        'role_id' => $role->role_id
                    ]);
                    return $role->role_id;
                }
            }
        }

        \Log::info('Google OAuth: No matching role found for any group');
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

        \Log::info('Google OAuth: Processing role assignment', [
            'user_id' => $user->id,
            'map_group_to_role' => $this->mapGroupToRole
        ]);

        if ($this->mapGroupToRole) {
            $userRaw = $OAuthUser->getRaw();
            $groups = Arr::get($userRaw, 'groups', []);

            \Log::info('Google OAuth: User groups from OAuth', [
                'groups' => $groups,
                'raw_keys' => array_keys($userRaw)
            ]);

            $roleToApply = $this->getRoleByGroup($groups);

            \Log::info('Google OAuth: Role from group mapping', ['role_id' => $roleToApply]);
        }

        if (empty($roleToApply)) {
            if (!empty($defaultRole = $this->getDefaultRole())) {
                $roleToApply = $defaultRole;
                \Log::info('Google OAuth: Using default role', ['role_id' => $roleToApply]);
            }
        }

        // Always refresh role assignments on login
        if (!empty($roleToApply)) {
            \Log::info('Google OAuth: Applying role to user', [
                'user_id' => $user->id,
                'role_id' => $roleToApply
            ]);
            \DB::table('user_to_app_to_role')->where('user_id', $user->id)->delete();
            User::applyDefaultUserAppRole($user, $roleToApply);
        } elseif (!empty($serviceId = $this->getServiceId())) {
            \DB::table('user_to_app_to_role')->where('user_id', $user->id)->delete();
            User::applyAppRoleMapByService($user, $serviceId);
        }

        return $user;
    }
}