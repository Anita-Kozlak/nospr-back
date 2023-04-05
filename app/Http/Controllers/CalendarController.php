<?php

namespace App\Http\Controllers;

use App\Calendar;
use App\Dict;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

use App\Security;

class CalendarController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        ///
    }

    public function getEvents(Request $request) {
        $this->validate($request, [
            'from'     => 'required|date',
            'to'        => 'required|date']);

        return response()->json(['status' => 'ok',
            'events' => Calendar::getEvents(Auth::user()->team, $request->input('from'), $request->input('to'))], RESPONSE::HTTP_OK);
    }

    public function getEvent(Request $request, $id) {

        $request['id'] = $id;
        $this->validate($request, [
            'id'        => 'required|integer']);

        return response()->json(['status' => 'ok', 'event' => Calendar::getEvent(intval($id))], RESPONSE::HTTP_OK);
    }

    public function addEvent(Request $request)
    {
        $this->validate($request, [
            'title'     => 'required|max:255',
            'start'     => 'required|date',
            'end'       => 'required|date',
            'allday'    => 'required|boolean',
            'color'     => 'required',
            'cast'      => 'string',
            'squad'     => 'string'

        ]);
        /*
        $squadFile = null;
        $castFile = null;

        if($request->file('squadFile')) {
            $this->validate($request, [
                'squadFile' => 'mimes:csv,txt,xlx,xls,xlsx,pdf,doc,docx,rtf|max:2048'
            ]);
            $fileName = Str::random().'_'.$request->file('squadFile')->getClientOriginalName();
            $request->file('squadFile')->move(ROOT_PATH.'/public/docs', $fileName);
            $squadFile = $fileName;
        }

        if($request->file('castFile')) {
            $this->validate($request, [
                'castFile' => 'mimes:csv,txt,xlx,xls,xlsx,pdf,doc,docx,rtf|max:2048'
            ]);

            $fileName = Str::random().'_'.$request->file('castFile')->getClientOriginalName();
            $request->file('castFile')->move(ROOT_PATH.'/public/docs', $fileName);
            $castFile = $fileName;
        }*/

        $newEventId = Calendar::addEvent(
            Auth::user()->team,
            $request->input('title'),
            $request->input('description'),
            $request->input('location'),
            $request->input('start'),
            $request->input('end'),
            $request->input('allday'),
            $request->input('color'),
            $request->input('cast'),
            $request->input('squad'));



        if ($request->input('notification') === "true") {

            $carbon = Carbon::create($request->input('start'));
            $startDate = $carbon->startOfMonth()->format("Y-m-d");
            $endDate =  $carbon->endOfMonth()->format("Y-m-d");

            //Send notification
            $notifyUsers = new \App\Notifications();
            $notifyUsers->sendNotification(
                [Auth::user()->team],
                'Inspektor NFM',
                'Dodano wydarzenie - "' . $request->input('start') . '"',
                '/calendar?start='.$startDate.'&end='.$endDate.'&event='.$newEventId);
        }
        return response()->json(['status' => 'ok', 'message' => 'Event created'], RESPONSE::HTTP_OK);
    }

    public function editEvent(Request $request, $id)
    {
        $request['id'] = $id;

        $this->validate($request, [
            'id'        => 'required|integer',
            'title'     => 'required|max:255',
            'start'     => 'required|date',
            'end'       => 'required|date',
            'allday'    => 'required|boolean',
            'color'     => 'required',
            'cast'      => 'string',
            'squad'     => 'string'
        ]);
        /*
        $squadFile = null;
        $castFile = null;

        if($request->file('squadFile')) {
            $this->validate($request, [
                'squadFile' => 'mimes:csv,txt,xlx,xls,xlsx,pdf,doc,docx,rtf|max:2048'
            ]);
            $fileName = Str::random().'_'.$request->file('squadFile')->getClientOriginalName();
            $request->file('squadFile')->move(ROOT_PATH.'/public/docs', $fileName);
            $squadFile = $fileName;
        } else {
            $squadFile = $request->input('squadFile');
        }

        if($request->file('castFile')) {
            $this->validate($request, [
                'castFile' => 'mimes:csv,txt,xlx,xls,xlsx,pdf,doc,docx,rtf|max:2048'
            ]);

            $fileName = Str::random().'_'.$request->file('castFile')->getClientOriginalName();
            $request->file('castFile')->move(ROOT_PATH.'/public/docs', $fileName);
            $castFile = $fileName;
        } else {
            $castFile = $request->input('castFile');
        }*/

        if ($request->input('notification') === "true") {

            $carbon = Carbon::create($request->input('start'));
            $startDate = $carbon->startOfMonth()->format("Y-m-d");
            $endDate =  $carbon->endOfMonth()->format("Y-m-d");

            //Send notification
            $notifyUsers = new \App\Notifications();
            $notifyUsers->sendNotification(
                [Auth::user()->team],
                'Inspektor NFM',
                'Zmieniono wydarzenie - "'.$request->input('start').'"',
                '/calendar?start='.$startDate.'&end='.$endDate.'&event='.$id);
        }

        Calendar::editEvent(
            intval($id),
            $request->input('title'),
            $request->input('description'),
            $request->input('location'),
            $request->input('start'),
            $request->input('end'),
            $request->input('allday'),
            $request->input('color'),
            $request->input('cast'),
            $request->input('squad'));

        return response()->json(['status' => 'ok', 'message' => 'Event updated'], RESPONSE::HTTP_OK);
    }

    public function deleteEvent(Request $request, $id)
    {
        $request['id'] = $id;
        $this->validate($request, [
            'id'    =>  'required|integer']);

        Calendar::deleteEvent(intval($id));
        return response()->json(['status' => 'ok', 'message' =>  'Event removed'], RESPONSE::HTTP_OK);
    }
}
