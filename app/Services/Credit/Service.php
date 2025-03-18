<?php

namespace App\Services\Credit;

use App\Helpers\Helper;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class Service
{
    protected $baseUrl;
    protected $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
        $this->baseUrl = config('app.creditsafe_api_url');
    }

    // get company detils
    public function authenticate()
    {
        try {
            $setting = Setting::where('key', 'credit_system')->where('is_disabled', 1)->first();
            if (!$setting) {
                return [];
            }
            $userName = @$setting->value_details['creditUserName'];
            $password = @$setting->value_details['creditPassword'];

            // authentication
            $headers = [
                'Accept' => 'application/json',
            ];
            $body = [
                "username" => $userName,
                "password" => $password
            ];
            $res = $this->client->post($this->baseUrl . '/authenticate', [
                'headers' => $headers,
                'json' => $body,
            ]);
            $response = json_decode($res->getBody()->getContents(), true);

            return $response['token'];
        } catch (\Exception $e) {
            Log::debug('Failed to fiken: ', ['error' => $e->getMessage()]);
            Helper::SendEmailIssue($e->getMessage());
            return [];
        }
    }

    public function getCompanyCredit($token, $company)
    {
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];

        $response1 =  $this->client->request('GET', $this->baseUrl . '/companies', [
            'query' => [
                'countries' => $company['countries'],
                'regNo' => $company['regNo'],
            ],
            'headers' => $headers,
        ]);

        $companies = json_decode($response1->getBody()->getContents(), true);

        if (!$companies['companies']) {
            return [];
        }

        $companies_id = $companies['companies'][0]['id'];

        // get company credit 

        $response2 =  $this->client->request('GET', $this->baseUrl . '/companies/' . $companies_id, [
            'query' => [
                'language' => 'en',
            ],
            'headers' => $headers,
        ]);

        $companies_credit = json_decode($response2->getBody()->getContents(), true);

        $currentCredit = $companies_credit['report']['creditScore']['currentCreditRating']['providerValue']['value'];

        return $currentCredit;
    }
}