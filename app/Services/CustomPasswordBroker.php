<?php

namespace App\Services;

use Illuminate\Auth\Passwords\PasswordBroker as BasePasswordBroker;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class CustomPasswordBroker extends BasePasswordBroker {

    /**
     * Determine if the passwords are valid for the request.
     *
     * @param  array  $credentials
     * @return bool
     */
    protected function validatePasswordWithDefaults(array $credentials) {
        [$password, $confirm] = [
            $credentials['a_password'],
            $credentials['a_password_confirmation'],
        ];

        return $password === $confirm && mb_strlen($password) >= 6;
    }

    /**
     * Reset the password for the given token.
     *
     * @param  array  $credentials
     * @param  \Closure  $callback
     * @return mixed
     */
    public function reset(array $credentials, \Closure $callback) {

        // If the responses from the validate method is not a user instance, we will
        // assume that it is a redirect and simply return it from this method and
        // the user is properly redirected having an error message on the post.
        $user = $this->validateReset($credentials);
        
        if (!$user instanceof CanResetPasswordContract) {
            return $user;
        }
        
        $password = $credentials['a_password'];
        
        // Once the reset has been validated, we'll call the given callback with the
        // new password. This gives the user an opportunity to store the password
        // in their persistent storage. Then we'll delete the token and return.
        $callback($user, $password);

        $this->tokens->delete($user);

        return static::PASSWORD_RESET;
    }

}
