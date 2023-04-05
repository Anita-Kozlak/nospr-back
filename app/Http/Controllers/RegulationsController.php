<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RegulationsController extends Controller
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
        $regulation = DB::table('regulations')
        ->join('users', 'users.id', '=', 'regulations.user')
        ->select([
            'regulations.id',
            'regulations.date',
            'regulations.updated',
            'regulations.description',
            'regulations.user as user_id',
            'users.name as user_name',
            'users.surname as user_surname'
        ])
        ->where(['regulations.team'=>Auth::user()->team])
        ->orderBy('regulations.date', 'desc')
        ->paginate(10,['*']);

        $arr = $regulation->toArray();
        foreach ($arr['data'] as $key => $item) {
            $arr['data'][$key]->files = DB::table('regulation_files')
                ->join('files', 'files.id', '=', 'regulation_files.file')
                ->select([
                    'files.id',
                    'files.title',
                    'files.original_name',
                    'files.file'])
                ->where(['regulation'=>$item->id])->get();
        }
        return response()->json($arr, RESPONSE::HTTP_OK);
    }

    public function create(Request $request) {
        $this->validate($request, [
            'description'   => 'required'
        ]);

        $regulationId = DB::table('regulations')
            ->insertGetId([
                'user'          => Auth::user()->id,
                'team'          => Auth::user()->team,
                'date'          => date("Y-m-d H:i:s"),
                'description'   => strip_tags($request->input('description'))
            ]);

        if ($request->file('files')) {
            foreach ($request->file('files') as $file) {

                $originalFilename = $file->getClientOriginalName();
                $fileName = Str::random() . '_' . $originalFilename;
                $file->move(ROOT_PATH . '/public/docs', $fileName);

                $addFileId = DB::table('files')
                    ->insertGetId([
                        'title' => strip_tags($request->input('title')),
                        'original_name' => basename($originalFilename),
                        'file' => $fileName
                    ]);

                DB::table('regulation_files')
                    ->insert([
                        'regulation' => $regulationId,
                        'file' => $addFileId
                    ]);
            }
        }
        return response()->json(['status' => 'ok', 'message' => 'Regulation created'], RESPONSE::HTTP_OK);
    }

    public function edit(Request $request, $id) {
        $this->validate($request, [
            'description'   => 'required'
        ]);

        DB::table('regulations')
            ->where(['id'=> $id])
            ->update([
                'user'          => Auth::user()->id,
                'description'   => strip_tags($request->input('description')),
            ]);

        return response()->json(['status' => 'ok', 'message' => 'Regulation edited'], RESPONSE::HTTP_OK);
    }

    public function delete(Request $request, $id)
    {
        $request['id'] = $id;
        $this->validate($request, [
            'id'    =>  'required|integer'
        ]);

        DB::table('regulations')
            ->where(['id'=> $id])
            ->delete();

        return response()->json(['status' => 'ok', 'message' =>  'Regulation deleted'], RESPONSE::HTTP_OK);
    }

}
