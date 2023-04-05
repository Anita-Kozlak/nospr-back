<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MessagesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getAll(Request $request) {
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
            ->where(['messages.team'=>Auth::user()->team])
            ->orderBy('date', 'desc')
            ->paginate(100,['*']);
    }

    public function send(Request $request) {
        $this->validate($request, [
            'message'     => 'required|max:2054',
        ]);

        DB::table('messages')
            ->insert([
                'user'      => Auth::user()->id,
                'team'      => Auth::user()->team,
                'date'      => date("Y-m-d H:i:s"),
                'message'   => strip_tags($request->input('message'))
            ]);

        //Send notification
        $notifyUsers = new \App\Notifications();
        $notifyUsers->sendNotification([Auth::user()->team], 'Inspektor NFM', Auth::user()->name .' '. Auth::user()->surname . ' wysłał/a nową wiadomość', '/chat', false);

        return response()->json(['status' => 'ok', 'message' => 'Message sent'], RESPONSE::HTTP_OK);
    }

    public function delete(Request $request, $id) {
        $request['id'] = $id;

        $this->validate($request, [
            'id'     => 'required|integer|numeric',
        ]);

        DB::table('messages')
            ->where(['id'=> $id])
            ->delete();

        return response()->json(['status' => 'ok', 'message' => 'Message deleted'], RESPONSE::HTTP_OK);
    }
}
