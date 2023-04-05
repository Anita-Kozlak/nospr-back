<?php

namespace App\Http\Controllers;
use App\Teams;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AnnouncementsController extends Controller
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

    public function get(Request $request, $type) {
        $request['type'] = $type;

        $this->validate($request, [
            'type'   => 'in:artistic,administrative']);

        $announcement = DB::table('announcements')
            ->join('users', 'users.id', '=', 'announcements.user')
            ->select([
                'announcements.id',
                'announcements.date',
                'announcements.updated',
                'announcements.description',
                'announcements.title',
                'announcements.user as user_id',
                'users.name as user_name',
                'users.surname as user_surname'
            ])
            ->where([
                'announcements.team'=>Auth::user()->team,
                'announcements.is_artistic' => $type === 'artistic' ? 1 : 0
            ])
            ->orderBy('announcements.date', 'desc')
            ->paginate(4,['*']);

        $arr = $announcement->toArray();
        foreach ($arr['data'] as $key => $item) {
            $arr['data'][$key]->files = DB::table('announcement_files')
                ->join('files', 'files.id', '=', 'announcement_files.file')
                ->select([
                    'files.id as file_id',
                    'files.title as file_title',
                    'files.original_name as file_original_name',
                    'files.file as file_name'])
                ->where(['announcement'=>$item->id])->get();
        }
        return response()->json($arr, RESPONSE::HTTP_OK);
    }

    public function create(Request $request) {
        $this->validate($request, [
            'description'   => 'required',
            'title'         => 'required|string|max:255',
            'is_artistic'   => 'required|boolean',
            'notify_all'   => 'required|boolean'
        ]);

        $notifyAll = boolval($request->input('notify_all'));
        $isArtistic = boolval($request->input('is_artistic'));
        $uploadedFiles=[];

        if ($request->file('files')) {
            $uploadedFiles = $this->uploadFiles($request);
        }

        if ($notifyAll) {
            $getTeams = Teams::getAll();
            foreach ($getTeams as $team) {
                $announcementId = DB::table('announcements')
                    ->insertGetId([
                        'user'          => Auth::user()->id,
                        'team'          => $team->id,
                        'date'          => date("Y-m-d H:i:s"),
                        'is_artistic'   => $isArtistic,
                        'title'         => strip_tags($request->input('title')),
                        'description'   => strip_tags($request->input('description'))
                    ]);

                    foreach ($uploadedFiles as $uploadedFile)
                    {
                        DB::table('announcement_files')
                            ->insert([
                                'announcement'=>$announcementId,
                                'file'  => $uploadedFile
                            ]);
                    }

                $link = $isArtistic ? '/announcements-artistic' : '/announcements-administrative';
                //Send notification
                $messageBody = Auth::user()->name.' '.Auth::user()->surname.' wysłał/a nowe ogłoszenie ';
                $isArtistic ? $messageBody.='artystyczne' : $messageBody.= 'administracyjne';

                $smsBody = $messageBody . ' w aplikacji NFM Inspektor - ' .strip_tags($request->input('title'));
                $pushBody = $messageBody . ' - '.strip_tags($request->input('title'));

                $notifyUsers = new \App\Notifications();
                $notifyUsers->sendNotification([Auth::user()->team], 'Inspektor NFM', $pushBody, $link, true, $smsBody);
            }
        } else {
            $announcementId = DB::table('announcements')
                ->insertGetId([
                    'user'          => Auth::user()->id,
                    'team'          => Auth::user()->team,
                    'date'          => date("Y-m-d H:i:s"),
                    'is_artistic'   => boolval($request->input('is_artistic')),
                    'title'         => strip_tags($request->input('title')),
                    'description'   => strip_tags($request->input('description'))
                ]);

            foreach ($uploadedFiles as $uploadedFile)
            {
                DB::table('announcement_files')
                    ->insert([
                        'announcement'=>$announcementId,
                        'file'  => $uploadedFile
                    ]);
            }

            //Send notification
            $link = $isArtistic ? '/announcements-artistic' : '/announcements-administrative';
            //Send notification
            $messageBody = Auth::user()->name.' '.Auth::user()->surname.' wysłał/a nowe ogłoszenie ';
            $isArtistic ? $messageBody.='artystyczne' : $messageBody.= 'administracyjne';

            $smsBody = $messageBody . ' w aplikacji NFM Inspektor - ' .strip_tags($request->input('title'));
            $pushBody = $messageBody . ' - '.strip_tags($request->input('title'));

            $notifyUsers = new \App\Notifications();
            $notifyUsers->sendNotification([Auth::user()->team], 'Inspektor NFM', $pushBody, $link, true, $smsBody);
        }

        return response()->json(['status' => 'ok', 'message' => 'Announcement created'], RESPONSE::HTTP_OK);
    }

    public function edit(Request $request, $id) {
        $request['id'] = $id;
        $this->validate($request, [
            'description'   => 'required',
            'id'            => 'required|integer',
            'title'         => 'required|string|max:255',
        ]);

        DB::table('announcements')
            ->where(['id'=> $id])
            ->update([
                'user'          => Auth::user()->id,
                'description'   => strip_tags($request->input('description')),
                'title'         => strip_tags($request->input('title')),
            ]);

        return response()->json(['status' => 'ok', 'message' => 'Announcement edited'], RESPONSE::HTTP_OK);
    }

    public function delete(Request $request, $id)
    {
        $request['id'] = $id;
        $this->validate($request, [
            'id'    =>  'required|integer'
        ]);

        DB::table('announcements')
            ->where(['id'=> $id])
            ->delete();

        return response()->json(['status' => 'ok', 'message' =>  'Announcement deleted'], RESPONSE::HTTP_OK);
    }

    private function uploadFiles($request) {
        $filesIds = [];

            foreach ($request->file('files') as $file) {

                $originalFilename = $file->getClientOriginalName();
                $fileName = Str::random().'_'.$originalFilename;
                $file->move(ROOT_PATH.'/public/docs', $fileName);

                $addFileId = DB::table('files')
                    ->insertGetId([
                        'title'         => strip_tags($request->input('file_title')),
                        'original_name' => basename($originalFilename),
                        'file'          => $fileName
                    ]);

                $filesIds[] = $addFileId;
            }
            return $filesIds;
        }
}
