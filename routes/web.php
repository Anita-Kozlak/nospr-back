<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\User;
use Illuminate\Support\Facades\DB;
use \App\Sms;

$router->get('/', function () {
    return response()->json(['app_version' => '0.2', 'date' => date("Y-m-d H:i:s")]);
});
$router->get('/user/get', ['middleware' => 'auth', 'as' => 'user-get', 'uses' => 'UsersController@get']);
$router->post('/user/activate/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'user-activate', 'uses' => 'UsersController@activate']);
$router->delete('/user/delete/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'user-delete', 'uses' => 'UsersController@delete']);
$router->get('/user/auth-check', ['middleware' => ['auth'], 'as' => 'user-auth-check', 'uses' => 'UsersController@authCheck']);
$router->post('/user/auth', ['as' => 'user-login', 'uses' => 'UsersController@login']);
$router->post('/user/register', ['as' => 'user-register', 'uses' => 'UsersController@register']);
//Beams token
$router->get('/user/get-beams-token', ['middleware' => ['auth'], 'as' => 'beams-get-token', 'uses' => 'UsersController@getBeamsToken']);

//Password reset
$router->post('/user/reset-password-request', ['as' => 'user-reset-password-request', 'uses' => 'UsersController@resetPasswordRequest']);
$router->post('/user/reset-password', ['as' => 'user-reset-password', 'uses' => 'UsersController@resetPassword']);
//Password change
$router->post('/user/change-password', ['middleware' => ['auth'], 'as' => 'change-password', 'uses' => 'UsersController@changePassword']);

//Notifications
$router->post('/user/toggle-notifications', ['middleware' => ['auth'], 'as' => 'toggle-notification', 'uses' => 'UsersController@toggleNotifications']);

//Calendar
$router->post('/calendar/get-events', ['middleware' => 'auth', 'as' => 'calendar-get-events', 'uses' => 'CalendarController@getEvents']);
$router->get('/calendar/get-event/{id}', ['middleware' => 'auth', 'as' => 'calendar-get-events', 'uses' => 'CalendarController@getEvent']);
//Admin
$router->post('/calendar/add-event', ['middleware' => ['auth', 'admin'], 'as' => 'calendar-add-event', 'uses' => 'CalendarController@addEvent']);
$router->post('/calendar/edit-event/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'calendar-edit-event', 'uses' => 'CalendarController@editEvent']);
$router->post('/calendar/delete-event/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'calendar-edit-event', 'uses' => 'CalendarController@deleteEvent']);

//Messages
$router->get('/messages', ['middleware' => 'auth', 'as' => 'messages-get-all', 'uses' => 'MessagesController@getAll']);
$router->post('/messages/send', ['middleware' => 'auth', 'as' => 'messages-send', 'uses' => 'MessagesController@send']);
//Admin
$router->delete('/messages/delete/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'messages-delete', 'uses' => 'MessagesController@delete']);

//Announcements
$router->get('/announcements/get/{type}', ['middleware' => 'auth', 'as' => 'announcements-edit', 'uses' => 'AnnouncementsController@get']);
//Admin
$router->post('/announcements/create', ['middleware' => ['auth', 'admin'], 'as' => 'announcements-create', 'uses' => 'AnnouncementsController@create']);
$router->post('/announcements/edit/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'announcements-edit', 'uses' => 'AnnouncementsController@edit']);
$router->delete('/announcements/delete/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'announcements-delete', 'uses' => 'AnnouncementsController@delete']);

//Regulations
$router->get('/regulations', ['middleware' => 'auth', 'as' => 'announcements-edit', 'uses' => 'RegulationsController@get']);
//Admin
$router->post('/regulations/create', ['middleware' => ['auth', 'admin'], 'as' => 'announcements-create', 'uses' => 'RegulationsController@create']);
$router->post('/regulations/edit/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'announcements-edit', 'uses' => 'RegulationsController@edit']);
$router->delete('/regulations/delete/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'announcements-delete', 'uses' => 'RegulationsController@delete']);

//Materials
$router->get('/materials', ['middleware' => 'auth', 'as' => 'materials-edit', 'uses' => 'MaterialsController@get']);
//Admin
$router->post('/materials/create', ['middleware' => ['auth', 'admin'], 'as' => 'materials-create', 'uses' => 'MaterialsController@create']);
$router->post('/materials/edit/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'materials-edit', 'uses' => 'MaterialsController@edit']);
$router->delete('/materials/delete/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'materials-delete', 'uses' => 'MaterialsController@delete']);

//File upload
$router->post('/file/upload/{type}/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'file-upload', 'uses' => 'FileController@upload']);
$router->delete('/file/delete/{type}/{id}', ['middleware' => ['auth', 'admin'], 'as' => 'file-delete', 'uses' => 'FileController@delete']);

$router->get('/test', function () {
    //Send notification
    //$notifyUsers = new \App\Notifications();
    //$notifyUsers->testOnly();

    //$smsService = new Sms();
    //$user = User::find(16);
    //$smsService->sendSms([$user->phone], 'Wiadomość testowa');
    //var_dump(Sms::sendSms(['723972285'], 'Wiadomość testowa'));
    //$notifyUsers = new \App\Notifications();
    //$notifyUsers->testOnly();
});

//Teams
$router->get('/teams', function () {
    return response()->json(['teams' => DB::table('teams')->get()]);
});