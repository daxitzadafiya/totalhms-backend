<?php

namespace App\Services\Strex;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Service
{
    protected $baseUrl;
    protected $header;
    protected $strexKey;
    protected $client;
    
    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
        $setting = Setting::where('key', 'strex_system')->where('is_disabled',1)->first();
        if(!$setting){
            return [];
        }
        $this->strexKey = @$setting->value_details['strexPersonalKey'];
        $this->header = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-ApiKey' => $this->strexKey,
        ];

        $this->baseUrl = config('app.strex_api_url');
    }


    // get company detils
    public function authenticate()
    {
        $responses =  $this->client->request(
            "POST",
            $this->baseUrl,
            [
                'X-API-KEY' => [
                    null,
                    $this->strexKey
                ],
            ]
        );
        return  $responses->getHeaders()['Request-Context'][0];
    }

    public function sendMessage($content,$number)
    {
        try {
            $body = [
                "sender"=> "Total HMS",
                "recipient"=> "+47".$number,
                "content"=> $content,
                "sendTime"=> Carbon::now(),
                "timeToLive"=> 120,
                "priority"=> "Normal",
                "deliveryMode"=> "AtMostOnce",
                "deliveryReportUrl"=> "https://tempuri.org",
                "created"=> Carbon::now(),
            ];

            $response = $this->client->post($this->baseUrl . '/api/out-messages', [
                'headers' => $this->header,
                'body' => json_encode($body),
            ]);

            $message =  $this->client->request('GET', $response->getHeaders()['Location'][0], [
                'headers' => $this->header,
            ]);
            $strexMessage = json_decode($message->getBody()->getContents(), true);

            return $strexMessage;
        } catch (\Exception $e) {
            Log::debug('Failed to strex: ', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

}