<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'surname', 'email', 'phone', 'has_safari', 'sms_notification', 'updated_at'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public static function registerUser($email, $password, $name, $surname, $phone) {
        DB::table('users')
            ->insert([
                'email'=> $email,
                'password' => Hash::make($password),
                'name'  => $name,
                'surname' => $surname,
                'role' => 'user',
                'phone' => $phone
            ]);
    }

    public static function getUsers() {
        return DB::table('users')
            ->leftJoin('teams', 'users.team', '=', 'teams.id')
            ->select([
                'users.id as id',
                'users.name as name',
                'users.surname as surname',
                'users.active as active',
                'users.email as email',
                'users.phone as phone',
                'users.role as role',
                'teams.id as team_id',
                'teams.name as team_name'
            ])
            ->get();
    }
}
