<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
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

    public function upload(Request $request, $type, $id) {

        $request['type']=$type;
        $request['id']=$id;

        $this->validate($request, [
            'type'   => 'required|in:announcement,material,regulation',
            'id'     => 'required|integer',
            'file'   => 'mimes:csv,txt,xlx,xls,xlsx,pdf,doc,docx,rtf|max:10240'
        ]);

        $originalFilename = $request->file('file')->getClientOriginalName();
        $file = Str::random().'_'.$originalFilename;
        $request->file('file')->move(ROOT_PATH.'/public/docs', $file);

        $fileId = DB::table('files')
            ->insertGetId([
                'title'         => strip_tags($request->input('title')),
                'original_name' => basename($originalFilename),
                'file'          => $file
            ]);

        switch ($type) {
            case 'announcement':
                try {
                    DB::table('announcement_files')
                        ->insert([
                            'announcement'=>$id,
                            'file'  => $fileId
                        ]);
                } catch (\Exception $e) {
                    return response()->json(['status' => 'err', 'message' => 'Invalid type id'], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                }
                break;
            case 'regulation':
                try {
                    DB::table('regulation_files')
                        ->insert([
                            'regulation'=>$id,
                            'file'  => $fileId
                        ]);
                } catch (\Exception $e) {
                    return response()->json(['status' => 'err', 'message' => 'Invalid type id'], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                }
                break;
            case 'material':
                try {
                    DB::table('material_files')
                        ->insert([
                            'material'=>$id,
                            'file'  => $fileId
                        ]);
                } catch (\Exception $e) {
                    return response()->json(['status' => 'err', 'message' => 'Invalid type id'], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                }
                break;
        }

        return response()->json([
            'status'    => 'ok',
            'message'   => 'File uploaded',
            'filename'  => $file,
            'file_id'   => $fileId,
            'title'     => strip_tags($request->input('title'))
        ], RESPONSE::HTTP_OK);

    }

    public function delete(Request $request, $type, $id) {
        $request['type']=$type;
        $request['id']=$id;

        $this->validate($request, [
            'type'   => 'required|in:announcement,material,regulation',
            'id'     => 'required|integer'
        ]);

        switch ($type) {
            case 'announcement':
                try {
                    DB::table('announcement_files')
                        ->where([
                            'file'  => intval($id)
                        ])
                        ->delete();
                } catch (\Exception $e) {
                    return response()->json(['status' => 'err', 'message' => 'Invalid type id'], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                }
                break;
            case 'regulation':
                try {
                    DB::table('regulation_files')
                        ->where([
                            'file'  => intval($id)
                        ])
                        ->delete();
                } catch (\Exception $e) {
                    return response()->json(['status' => 'err', 'message' => 'Invalid type id'], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                }
                break;
            case 'material':
                try {
                    DB::table('material_files')
                        ->where([
                            'file'  => intval($id)
                        ])
                        ->delete();
                } catch (\Exception $e) {
                    return response()->json(['status' => 'err', 'message' => 'Invalid type id'], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
                }
                break;
        }
        return response()->json(['status' => 'ok', 'message' => 'File removed.'], RESPONSE::HTTP_OK);
    }
}
