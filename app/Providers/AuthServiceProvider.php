<?php

namespace App\Providers;

use App\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use App\Security;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {

            if ($request->bearerToken()) {
                //Extract token data
                $explodeToken = explode(":", $request->bearerToken());
                if (count($explodeToken) == 2) {
                    $userId = intval($explodeToken[0]);
                    if ($userId > 0) {
                        $dbUser = DB::table('users')
                            ->where(['id' => $userId, 'active'=>1])
                            ->first();
                        if ($dbUser && $dbUser->token_valid >= date("Y-m-d H:i:s")) {
                            try {
                                $decryptedToken = Crypt::decrypt($dbUser->token, false);
                                if ($decryptedToken === $explodeToken[1]) {
                                    return $dbUser;
                                } else {
                                    //Invalid token
                                    Security::logEvent(0, 2, 'ID:'.$explodeToken[0], $_SERVER['REMOTE_ADDR'], gethostbyaddr($_SERVER['REMOTE_ADDR']), 'Invalid token provided: '.$request->bearerToken());

                                }
                            } catch (DecryptException $e) {
                                //Decrypt error
                                Security::logEvent(0, 2, 'ID:'.$explodeToken[0], $_SERVER['REMOTE_ADDR'], gethostbyaddr($_SERVER['REMOTE_ADDR']), 'Token decrypt error : '.$request->bearerToken());
                                //Log incident

                            }

                        } else {
                            //No such user
                            //Log incident
                            Security::logEvent(0, 2, 'ID:'.$explodeToken[0], $_SERVER['REMOTE_ADDR'], gethostbyaddr($_SERVER['REMOTE_ADDR']), 'Token validation error: No such user '.$request->bearerToken(). 'or token expired');
                        }
                        //exit();
                    }
                }
            }

            return null;

        });
    }
}
