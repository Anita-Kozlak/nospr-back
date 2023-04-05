<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class Security
{
    const MAX_INVALID_LOGIN_ATTEMPTS = 50;
    const MAX_INVALID_PASSWORDS = 10;
    const ENCRYPT_TOKEN = true;

    private $login =null;
    private $password = null;
    private $isAuthenticated = false;
    private $remoteAddress = null;
    private $loggedUser=null;

    public function __construct($login, $password,$remote)
    {
        $this->login = $login;
        $this->password = $password;
        $this->remoteAddress = $remote;
    }


    public function authenticateUser()
    {
        //Check if user is allready banned
        if ($this->checkLoginAttempt($this->remoteAddress, $this->login)) {
            //Get user
            $user = DB::table('users')
                ->where('email', $this->login)
                ->first();

            if ($user) {
                if (Hash::check($this->password, $user->password) && intval($user->active) === 1) {
                    $this->isAuthenticated = true;

                    //Generate API key
                    $apiToken = base64_encode(Str::random(60));

                    //Encrypt token
                    $encryptedToken = Crypt::encryptString($apiToken);

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'token' => $encryptedToken,
                            'token_valid'=> date("Y-m-d H:i:s", strtotime("+30 days")),
                            'last_login' => date("Y-m-d H:i:s")]);

                    $user->token = $user->id.':'.$apiToken;
                    $this->loggedUser = $user;

                    $this->logSecurityEvent(1, 0, $this->login, $this->remoteAddress, gethostbyaddr($this->remoteAddress), 'User logged in.');

                } else {
                    //Invalid password
                    $this->logSecurityEvent(1, 1, $this->login, $this->remoteAddress, gethostbyaddr($this->remoteAddress), 'Invalid password.');
                }

            } else {
                $this->logSecurityEvent(0, 1, $this->login, $this->remoteAddress, gethostbyaddr($this->remoteAddress), 'No such user');
            }
        }

    }

    public function getLoggedUser() {
        return $this->loggedUser;
    }

    public function checkLoginAttempt ($remoteAddress, $login) {
        $this->refreshBanList();
        //Check if ip address is not banned
        if ($this->checkIpAddress($remoteAddress))return false;
        if ($this->checkLogin($login))return false;
        return true;
    }

    /**
     * Function checks if provided ipaddress is banned
     * @param $remoteAddress
     * @return bool - true-> banned; false -> not banned
     */
    private function checkIpAddress($remoteAddress) {
        $bannedAddress = DB::table('banned_users')
            ->where('ip', ip2long($remoteAddress))
            ->first();
        return $bannedAddress ? true : false;
    }

    private function checkLogin($login) {
        $bannedAddress = DB::table('banned_users')
            ->where(['login' => $login])
            ->first();
        return $bannedAddress ? true : false;
    }

    private function refreshBanList() {
        DB::table('banned_users')
            ->where(['ban_type' => 0])
            ->where('stop', '<', date('Y-m-d H:i:s'))
            ->delete();
    }

    private function addBan($banType, $priority, $start, $stop, $desc, $login, $ip, $remote)
    {
        DB::table('banned_users')->insert([
            'ban_type' => $banType,
            'priority' => $priority,
            'start' => $start,
            'stop' => $stop,
            'info' => $desc,
            'login' => $login,
            'ip' => ip2long($ip),
            'remote' => $remote
        ]);
    }

    public function isAuthenticated() {
        return $this->isAuthenticated;
    }

    private function logSecurityEvent($type, $priority, $login, $ip, $remote, $desc) {
        DB::table('log_logins')
            ->insert([
                'type'      =>  $type,
                'priority'  =>  $priority,
                'login'     => $login,
                'ip'        => ip2long($ip),
                'remote'    => $remote,
                'description'=>$desc
            ]);

        if ($type === 0 && $priority > 0) {
            //Check maximum login attempts
            $entriesCount = DB::table('log_logins')
                ->where(['type'=>0,'ip' => ip2long($this->remoteAddress)])
                ->where('date', '>=', date("Y-m-d H:i:s", strtotime("-1 day")))
                ->count();

            if ($entriesCount > self::MAX_INVALID_LOGIN_ATTEMPTS) {
                $this->addBan(0, 0, date("Y-m-d H:i:s"), date("Y-m-d H:i:s", strtotime("+1 day")), 'Exceeded max login attempts for user', $this->login, $this->remoteAddress, gethostbyaddr($this->remoteAddress));
            }
        } elseif ($type === 1 && $priority >0) {
            //Check maximum passwords attempts
            $entriesCount = DB::table('log_logins')
                ->where(['type'=>1, 'login' => $this->login])
                ->where('date', '>=', date("Y-m-d H:i:s", strtotime("-1 day")))
                ->count();

            if ($entriesCount > self::MAX_INVALID_PASSWORDS) {
                $this->addBan(0, 0, date("Y-m-d H:i:s"), date("Y-m-d H:i:s", strtotime("+1 day")), 'Exceeded max user invalid passwords attempts.', $this->login, $_SERVER['REMOTE_ADDR'], gethostbyaddr($_SERVER['REMOTE_ADDR']));
            }
        }

    }

    public static function logEvent($type, $priority, $login, $ip, $remote, $desc)
    {
        DB::table('log_logins')
            ->insert([
                'type' => $type,
                'priority' => $priority,
                'login' => $login,
                'ip' => ip2long($ip),
                'remote' => $remote,
                'description' => $desc
            ]);
    }
}
