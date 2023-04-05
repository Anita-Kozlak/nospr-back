<?php

namespace App;

use Illuminate\Support\Facades\DB;

class Messages
{
    public static function getAll() {
        return DB::table('messages')
            ->join('users', 'users.id', '=', 'messages.user')
            ->select([
                'messages.id',
                'messages.date',
                'messages.message',
                'messages.user as user_id',
                'users.name as user_name',
                'users.surname as user_surname'
            ])
            ->orderBy('date', 'desc')
            ->get();
    }

}
