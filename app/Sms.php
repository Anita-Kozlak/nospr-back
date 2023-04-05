<?php

namespace App;


class Sms
{
    public $client = null;

    public function __construct()
    {
        $this->client = new \SMSPLANET\PHP\Client([
            'key' => env('SMS_KEY'),
            'password' => env('SMS_PASSWORD')
        ]);
    }

    public function sendSms($recipients, $message) {

        $message_id = $this->client->sendSimpleSMS([
            'from' => 'INSPEKTOR',
            'to' => $this->normalizePhoneNumbers($recipients),
            'msg' => $message
        ]);
    }

    public function normalizePhoneNumbers($phoneNumbers) {
        $normalizedNumbers = [];
        foreach ($phoneNumbers as $phoneNumber) {
            $phoneNumber = str_replace(' ', '', $phoneNumber);
            $phoneNumber = str_replace('-', '', $phoneNumber);
            $phoneNumber = str_replace('(48)', '48', $phoneNumber);
            if (strpos($phoneNumber, '+48') !== false) {
                $normalizedNumbers[]=$phoneNumber;
            }
        }
        return implode(',', $normalizedNumbers);
    }
}