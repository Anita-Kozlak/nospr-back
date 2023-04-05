<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MaterialsController extends Controller
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

    public function get() {
        $materials = DB::table('materials')
            ->join('users', 'users.id', '=', 'materials.user')
            ->select([
                'materials.id',
                'materials.date',
                'materials.updated',
                'materials.description',
                'materials.title',
                'materials.user as user_id',
                'users.name as user_name',
                'users.surname as user_surname'
            ])
            ->where(['materials.team'=>Auth::user()->team])
            ->orderBy('materials.date', 'desc')
            ->paginate(10,['*']);

        $arr = $materials->toArray();
        foreach ($arr['data'] as $key => $item) {
            $arr['data'][$key]->files = DB::table('material_files')
                ->join('files', 'files.id', '=', 'material_files.file')
                ->select([
                    'files.id as file_id',
                    'files.title as file_title',
                    'files.original_name as file_original_name',
                    'files.file as file_name'])
                ->where(['material'=>$item->id])->get();
        }
        return response()->json($arr, RESPONSE::HTTP_OK);
    }

    public function create(Request $request) {
        $this->validate($request, [
            'description'   => 'required',
            'title'         => 'required|string|max:255',
        ]);

        $materialId = DB::table('materials')
            ->insertGetId([
                'user'          => Auth::user()->id,
                'team'          => Auth::user()->team,
                'date'          => date("Y-m-d H:i:s"),
                'title'         => strip_tags($request->input('title')),
                'description'   => strip_tags($request->input('description'))
            ]);

        if ($request->file('files')) {
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

                DB::table('material_files')
                    ->insert([
                        'material'=>$materialId,
                        'file'  => $addFileId
                    ]);
            }
        }
        //Send notification
        $notifyUsers = new \App\Notifications();
        $notifyUsers->sendNotification([Auth::user()->team], 'Inspektor NFM', Auth::user()->name.' '.Auth::user()->surname.' dodał nowy materiał', '/materials');


        return response()->json(['status' => 'ok', 'message' => 'Material created'], RESPONSE::HTTP_OK);
    }

    public function edit(Request $request, $id) {
        $this->validate($request, [
            'description'   => 'required'
        ]);

        DB::table('materials')
            ->where(['id'=> $id])
            ->update([
                'user'          => Auth::user()->id,
                'title'         => strip_tags($request->input('title')),
                'description'   => strip_tags($request->input('description')),
            ]);

        return response()->json(['status' => 'ok', 'message' => 'Material edited'], RESPONSE::HTTP_OK);
    }

    public function delete(Request $request, $id)
    {
        $request['id'] = $id;
        $this->validate($request, [
            'id'    =>  'required|integer'
        ]);

        DB::table('materials')
            ->where(['id'=> $id])
            ->delete();

        return response()->json(['status' => 'ok', 'message' =>  'Material deleted'], RESPONSE::HTTP_OK);
    }

}
