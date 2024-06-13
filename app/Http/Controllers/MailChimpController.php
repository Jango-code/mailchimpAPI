<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MailChimpController extends Controller
{

    private function auth($authorization)
    {
        if (!$authorization) {
            return false;
        }

        list($type, $authorization) = explode(" ", $authorization);
        Log::info('Token Type: ' . $type);
        Log::info('Authorization Token: ' . $authorization);

        $valid_tokens = [
            'FREETOKEN',
        ];

        return in_array($authorization, $valid_tokens);
    }
    public function getRecords(Request $request)
    {
        $request->validate([
            'data_center' => 'required|string',
            'api_key' => 'required|string',
            'count' => 'required|integer',
            'endpoint' => 'required|string',
        ]);

        $authorizationHeader = $request->header('Authorization');
        Log::info('Authorization Header: ' . $authorizationHeader);
        if (!$this->auth($authorizationHeader)) {
            return response()->json(['error_code' => '401', 'error_description' => 'Unauthorized'], 401);
        }

        $result = $this->fetchRecords($request->input('endpoint'), [
            'data_center' => $request->input('data_center'),
            'api_key' => $request->input('api_key'),
            'count' => $request->input('count'),
            'offset' => 0,
        ]);

        return response()->json($result);
    }

    private function fetchRecords($endpoint, $params)
    {
        $MailChimpDataCenter = $params['data_center'];
        $MailChimpApiKey = $params['api_key'];

        $auth = base64_encode('user:' . $MailChimpApiKey);
        $url = "https://$MailChimpDataCenter.api.mailchimp.com/3.0" . $endpoint;
        $url_params = [];
        if (!empty($params['count']))
            $url_params['count'] = $params['count'];
        if (!empty($params['offset']))
            $url_params['offset'] = $params['offset'];
        if (!empty($url_params))
            $url .= "?" . http_build_query($url_params);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $auth,
        ])->get($url);

        $result = $response->json();

        $endpoint_method = last(explode("/", $endpoint));

        if (!in_array($endpoint_method, ["lists", "members", "segments", "campaigns", "reports"])) {
            return $result;
        }

        $total_items = $result['total_items'];

        $records = $result[$endpoint_method];

        if ($total_items > $params['offset'] + count($records)) {
            $params['offset'] += count($records);
            $records = array_merge($records, $this->fetchRecords($endpoint, $params));
        }

        return $records;
    }

    // public function getCampaigns(Request $request)
    // {
    //     $request->validate([
    //         'data_center' => 'required|string',
    //         'api_key' => 'required|string',
    //     ]);

    //     $authorizationHeader = $request->header('Authorization');
    //     Log::info('Authorization Header: ' . $authorizationHeader);
    //     if (!$this->auth($authorizationHeader)) {
    //         return response()->json(['error_code' => '401', 'error_description' => 'Unauthorized'], 401);
    //     }

    //     //$campaignIds = $this->fetchCampaignIds($request->input('data_center'), $request->input('api_key'));

    //     return response()->json($campaignIds);
    // }

    private function fetchCampaignIds($dataCenter, $apiKey)
    {
        $auth = base64_encode('user:' . $apiKey);
        $url = "https://$dataCenter.api.mailchimp.com/3.0/campaigns?fields=campaigns.id";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $auth,
        ])->get($url);

        //$campaigns = $response['campaigns'];
        $campaigns = $response->json()['campaigns'];
        // $oneYearAgo = Carbon::now()->subYear();

        // $campaignIds = [];
        // foreach ($campaigns as $campaign) {
        //     if (Carbon::parse($campaign['create_time'])->greaterThanOrEqualTo($oneYearAgo)) {
        //         $campaignIds[] = $campaign['id'];
        //     }
        // }
        //return $campaignIds;
        return $response['campaigns'];
    }

    public function getOpenDetails(Request $request)
    {
        $request->validate([
            'data_center' => 'required|string',
            'api_key' => 'required|string',
        ]);

        $dataCenter = $request->input('data_center');
        $apiKey = $request->input('api_key');

        $campaignIds = $this->fetchCampaignIds($dataCenter, $apiKey);

        //return $campaignIds;
        Log::info('Campaign IDs: ' . json_encode($campaignIds));

        $openDetails = [];

        // Para cada ID de campaña, obtener los detalles de apertura y almacenarlos en el array
        foreach ($campaignIds as $campaignId) {
            $details = $this->fetchOpenDetails($dataCenter, $apiKey, $campaignId['id']);
            $openDetails[] = $details;
        }

        return response()->json($openDetails);
    }

    private function fetchOpenDetails($dataCenter, $apiKey, $campaignId)
    {
        $auth = base64_encode('user:' . $apiKey);
        $url = "https://$dataCenter.api.mailchimp.com/3.0/reports/$campaignId/open-details";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $auth,
        ])->get($url);

        return $response->json();
    }
}
