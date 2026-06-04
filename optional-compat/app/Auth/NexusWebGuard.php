<?php
namespace App\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class NexusWebGuard implements StatefulGuard
{
    use GuardHelpers;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new authentication guard.
     *
     * @param  callable  $callback
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Auth\UserProvider|null  $provider
     * @return void
     */
    public function __construct(Request $request, UserProvider $provider = null)
    {
        $this->request = $request;
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }
        $credentials = $this->request->cookie();
        if ($this->validate($credentials)) {
            /**
             * @var User $user
             */
            $user = $this->provider->retrieveByCredentials($credentials);
            if (empty($user)) {
                return null;
            }
            if ($this->provider->validateCredentials($user, $credentials)) {
                $user->checkIsNormal();
                return $this->user = $user;
            }
        }
    }


    /**
     * Validate a user's credentials.
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $required = ['c_secure_pass'];
        foreach ($required as $value) {
            if (empty($credentials[$value])) {
                return false;
            }
        }
        return true;
    }

    public function logout()
    {
        logoutcookie();
        return nexus_redirect('login.php');
    }


    public function attempt(array $credentials = [], $remember = false)
    {
        $login = $credentials['email'] ?? $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;
        if (!$login || !$password) {
            return false;
        }

        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();
        if (!$user || !$this->validatePassword($user, $password)) {
            return false;
        }

        $user->checkIsNormal();
        $this->login($user, $remember);
        return true;
    }

    public function once(array $credentials = [])
    {
        $login = $credentials['email'] ?? $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;
        if (!$login || !$password) {
            return false;
        }

        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();
        if (!$user || !$this->validatePassword($user, $password)) {
            return false;
        }

        $user->checkIsNormal();
        $this->setUser($user);
        return true;
    }

    public function login(Authenticatable $user, $remember = false)
    {
        if (empty($user->auth_key)) {
            $user->auth_key = hash('sha256', mksecret(32));
            $user->save();
        }

        logincookie($user->getAuthIdentifier(), $user->auth_key, $remember ? 0 : 0);
        $this->setUser($user);
    }

    public function loginUsingId($id, $remember = false)
    {
        $user = $this->provider->retrieveById($id);
        if (!$user) {
            return false;
        }

        $this->login($user, $remember);
        return $user;
    }

    public function onceUsingId($id)
    {
        $user = $this->provider->retrieveById($id);
        if (!$user) {
            return false;
        }

        $this->setUser($user);
        return $user;
    }

    public function viaRemember()
    {
        return false;
    }

    private function validatePassword(User $user, string $password): bool
    {
        $newHash = hash('sha256', $user->secret . hash('sha256', $password));
        if (hash_equals((string) $user->passhash, $newHash)) {
            return true;
        }

        $oldHash = md5($user->secret . $password . $user->secret);
        return hash_equals((string) $user->passhash, $oldHash);
    }
}
