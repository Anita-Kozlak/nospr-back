<?php

namespace App\Http\Controllers;

use App\Mails\ResetPassword;
use App\Notifications;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

use App\Security;

class UsersController extends Controller
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

    public function login(Request $request)
    {
        $this->validate($request, [
            'login' => 'required',
            'password' => 'required'
        ]);

        $security = new Security($request->input('login'),$request->input('password'),$_SERVER['REMOTE_ADDR']);
        $security->authenticateUser();

        if ($security->isAuthenticated()) {

            $loggedUser = $security->getLoggedUser();

            if ($request->input('is_safari')) {
                if (!$loggedUser->has_safari) {
                    DB::table('users')
                        ->where('id', $loggedUser->id)
                        ->update([
                            'has_safari' => true,
                            'sms_notification'=> true
                        ]);
                }
            }

            return response()->json([
                    'id'   => $loggedUser->id,
                    'role'=> $loggedUser->role,
                    'email'=> $loggedUser->email,
                    'name' => $loggedUser->name,
                    'surname' => $loggedUser->surname,
                    'token' => $loggedUser->token,
                    'team' => $loggedUser->team,
                    'phone' => $loggedUser->phone,
                    'sms_notification'=> $loggedUser->sms_notification,
                    'status' => 'ok'
            ], RESPONSE::HTTP_OK);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Invalid login and/or password'], RESPONSE::HTTP_UNAUTHORIZED);
        }
    }

    public function register(Request $request) {
        //return response()->json(['pw'=>Hash::make('1234567')]);

        $this->validate($request, [
            'email'     => 'required|email|max:255',
            'password'  => 'required|min:7|max:255',
            'name'      => 'required|max:255',
            'surname'   => 'required|max:255',
            'phone'     => 'required|max:255'
        ]);

        $checkEmail = DB::table('users')
            ->where(['email'=>$request->input('email')])
            ->first();
        if ($checkEmail) {
            return response()->json(['email' => ['Provided e-mail is already registered']], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        }

        //$email, $password, $name, $surname, $phone
        User::registerUser(
            $request->input('email'),
            $request->input('password'),
            $request->input('name'),
            $request->input('surname'),
            $request->input('phone')
        );

        return response()->json(['status' => 'ok', 'message' => ''], RESPONSE::HTTP_OK);
    }

    public function activate(Request $request, $id)
    {
        $request['id'] = $id;
        $this->validate($request, [
            'id'        =>  'required|integer',
            'active'    =>  'required|boolean',
            'team'      =>  'required|integer'
        ]);

        DB::table('users')
            ->where(['id'=> intval($id)])
            ->update(['active'=>boolval($request->input('active')), 'team'=> intval($request->input('team'))]);

        return response()->json(['status' => 'ok', 'message' => 'User updated'], RESPONSE::HTTP_OK);
    }

    public function get() {
        return response()->json(['status' => 'ok', 'users' => User::getUsers()], RESPONSE::HTTP_OK);
    }

    public function delete(Request $request, $id) {
        $request['id'] = $id;
        $this->validate($request, [
            'id' => 'required|integer'
        ]);

        DB::table('users')
            ->where(['id'=>$id])
            ->delete();

        return response()->json(['status' => 'ok', 'message' => 'User removed'], RESPONSE::HTTP_OK);
    }

    public function authCheck() {
        return response()->json([
            'id'   => Auth::user()->id,
            'role'=> Auth::user()->role,
            'email'=> Auth::user()->email,
            'name' => Auth::user()->name,
            'surname' => Auth::user()->surname,
            'phone' => Auth::user()->phone,
            'token' => Auth::user()->id.':'.Crypt::decrypt(Auth::user()->token, false),
            'team' => Auth::user()->team,
            'sms_notification' => (bool)Auth::user()->sms_notification,
            'status' => 'ok'
        ], RESPONSE::HTTP_OK);
    }

    public function resetPasswordRequest(Request $request) {
        $this->validate($request, [
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->get('email'))->first();

        if ($user) {
            //Generate password recovery token
            $passwordResetCode = Str::random(32);
            DB::table('users')
                ->where('email', $user->email)
                ->update([
                    'password_reset' => $passwordResetCode,
                    'password_reset_valid' => date("Y-m-d H:i:s", strtotime("+1 day"))
                ]);

            $resetPasswordEmail = new ResetPassword($user->name, $passwordResetCode);
            Mail::to($user->email)->send($resetPasswordEmail);
        }

        return response()->json([
            'status'=> 'ok',
            'msg'   => 'Request received.'
        ], RESPONSE::HTTP_OK);
    }

    public function resetPassword(Request $request) {

        $this->validate($request, [
            'code' => 'required|string|min:32|max:32',
            'new_password'=> 'required|string|min:7|max:255',
            'confirm_password' => 'required|same:new_password'
        ]);

        $user = User::where('password_reset', $request->get('code'))->first();

        if ($user && $user->password_reset_valid >=date("Y-m-d H:i:s")) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'password' => Hash::make($request->get('new_password')),
                    'password_reset'=> null,
                    'password_reset_valid' => null
                ]);
            return response()->json([
                'status'    => 'ok',
                'msg'       => 'Password changed.'
            ], RESPONSE::HTTP_OK);


        } else {
            return response()->json([
                'status'=> 'err',
            ], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function changePassword(Request $request) {

        $this->validate($request, [
            'old_password'      => 'required|string|min:7|max:255',
            'new_password'      => 'required|string|min:7|max:255',
            'confirm_password'  => 'required|same:new_password'
        ]);

        //Check old password
        if (Hash::check($request->get('old_password'), Auth::user()->password)) {
            DB::table('users')
                ->where('id', Auth::user()->id)
                ->update([
                    'password' => Hash::make($request->get('new_password'))
                ]);
            return response()->json([
                'status'    => 'ok',
                'msg'       => 'Password changed.'
            ], RESPONSE::HTTP_OK);
        } else {
            return response()->json([
                'status'=> 'err',
                'msg' => 'Invalid password'
            ], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function getBeamsToken(Request $request) {
        $requestUserId = $request->input('user_id');

        $userID = $request->user()->id; // If you use a different auth system, do your checks here
        $userIDInQueryParam = $request->input('user_id');

        if ($userID != $userIDInQueryParam) {
            return response('Inconsistent request', 401);
        } else {
            $beamsToken = (new Notifications())->generateBeamsToken(Auth::user()->id);
            return response()->json($beamsToken);
        }
    }

    public function toggleNotifications(Request $request) {
        $sendNotification = (bool)$request->input('sms_notification');
        DB::table('users')
            ->where('id', $request->user()->id)
            ->update(['sms_notification' => $sendNotification]);

        return response()->json([
            'status'=> 'ok',
            'msg' => 'Changes saved'
        ], RESPONSE::HTTP_OK);
    }

}
