<?php

namespace App;

use Illuminate\Support\Facades\DB;

class Calendar
{
    public static function getEvents($team, $from, $to) {
        return DB::table('events')
            ->where(['team'=>$team])
            ->where([
                ['start', '>=', date('Y-m-d', strtotime($from)).' 00:00:00'],
                ['end', '<=', date('Y-m-d', strtotime($to)).' 23:59:59']])
            ->get();
    }

    public static function getEvent($id) {
        return DB::table('events')
            ->where('id', $id)
            ->first();
    }

    public static function addEvent($team, $title, $description, $location, $start, $end, $allDay, $color, $cast='', $squad='') {
        return DB::table('events')
            ->insertGetId([
                'team'          => $team,
                'title'         => $title,
                'description'   => $description,
                'location'      => $location,
                'start'         => $start,
                'end'           => $end,
                'allday'        => $allDay,
                'color'         => $color,
                'cast'          => $cast,
                'squad'         => $squad]);
    }

    public static function editEvent($id, $title, $description, $location, $start, $end, $allDay, $color, $cast=null, $squad=null) {
        $update = [
            'title'         => $title,
            'description'   => $description,
            'location'      => $location,
            'start'         => $start,
            'end'           => $end,
            'allday'        => $allDay,
            'color'         => $color];
        if ($cast !== null) $update['cast']=$cast;
        if ($squad !== null) $update['squad']=$squad;
        DB::table('events')
            ->where(['id'=>$id])
            ->update($update);
    }

    public static function deleteEvent($id) {
        DB::table('events')
            ->where([
                'id'    => $id
            ])->delete();
    }
}
