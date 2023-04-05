<?php

namespace App;

use Illuminate\Support\Facades\DB;

class Teams
{
    public static function getAll() {
        return DB::table('teams')
            ->get();
    }
}
