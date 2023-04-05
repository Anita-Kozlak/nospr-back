<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Pusher\PushNotifications\PushNotifications;

class Notifications
{
    const INSTANCE_ID = "95497c8d-ec23-4657-bf27-325aee32f0b2";
    const KEY = "FCDEA6A6371C85852FBED7C8EFDC21B7D6C664EBE8540D2299166C2CAA067DB0";
    private $pushService = null;

    public function __construct()
    {
        $this->pushService = new PushNotifications(array(
            "instanceId" => self::INSTANCE_ID,
            "secretKey" => self::KEY,
        ));
    }

    public function testOnly() {
        //NotificationLogs::logNotification('sms', ['array' => [1,2,3]]);
        /*
        return $this->pushService->publishToUsers(
            ["16"],
            [
                "fcm" => [
                    "notification" => [
                        "title" => 'Test prod 222',
                        "body" => "Wiadomość testowa 222"
                    ]
                ],
                "apns" => ["aps" => [
                    "alert" => [
                        "title" => 'Test prod',
                        "body" => "Wiadomość testowa"
                    ]
                ]],
                "web" => [
                    "notification" => [
                        "title" => "Test prod",
                        "body" => "Wiadomość testowa",
                        "icon"  => "https://inspektor.nfm.wroclaw.pl/icon_512x512.png",
                        "deep_link" => "https://inspektor.nfm.wroclaw.pl"
                    ]
                ]
            ]);
        */
    }

    public function sendNotification($team, $title, $body, $deepLink ='', $forceSendSms = true, $smsBody = null) {

        $sms = new Sms();

        $teamMembers = User::where('team', $team)->get();
        $sendTo = [];
        $sendSms = [];
        foreach ($teamMembers as $member) {
            if (Auth::user()) {
                if (intval(Auth::user()->id) !== intval($member->id)) {
                    $sendTo[] = (string)$member->id;
                    if ($member->sms_notification) {
                        $sendSms[]=$member->phone;
                    }
                }
            } else {
                $sendTo[] = (string)$member->id;
                if ($member->sms_notification) {
                    $sendSms[]=$member->phone;
                }
            }
        }

        if (count($sendSms) > 0 && $forceSendSms) {
            $sms->sendSms($sendSms, $smsBody ? $smsBody : $body);
            NotificationLogs::logNotification('sms', ['users' => $sendSms, 'text' => $smsBody ? $smsBody : $body]);
        }

        if (count($sendTo) > 0) {
            $response = $this->pushService->publishToUsers(
                $sendTo,
                [
                    "fcm" => [
                        "notification" => [
                            "title" => $title,
                            "body" => $body
                        ]
                    ],
                    "apns" => ["aps" => [
                        "alert" => [
                            "title" => $title,
                            "body" => $body
                        ]
                    ]],
                    "web" => [
                        "notification" => [
                            "title" => $title,
                            "body" => $body,
                            "icon"  => "https://inspektor.nfm.wroclaw.pl/icon_512x512.png",
                            "deep_link" => "https://inspektor.nfm.wroclaw.pl".$deepLink
                        ]
                    ]
                ]);
            NotificationLogs::logNotification('pusher', ['users' => $sendTo, 'pusher' => $response]);
        }
    }

    public function generateBeamsToken($userId) {
        return $this->pushService->generateToken($userId);
    }

}
