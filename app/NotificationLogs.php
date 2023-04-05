<?php

namespace App;

use Illuminate\Support\Facades\DB;

class NotificationLogs
{
    public static function logNotification($type, $content) {
        DB::table('notification_logs')
            ->insert([
               'type'       => $type,
               'date'       => date("Y-m-d H:i:s"),
               'content'    => json_encode($content)
            ]);
    }
}