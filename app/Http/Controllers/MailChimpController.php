<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailChimpController extends Controller
{
    public function getRecords(Request $request)
    {
        $request->validate([
            'data_center' => 'required|string',
            'api_key' => 'required|string',
            'count' => 'required|integer',
            'endpoint' => 'required|string',
        ]);

        $params = [
            'data_center' => $request->input('data_center'),
            'api_key' => $request->input('api_key'),
            'offset' => 0,
            'count' => $request->input('count'),
        ];

        $authorizationHeader = $request->header('Authorization');
        Log::info('Authorization Header: ' . $authorizationHeader);
        if (!$this->auth($authorizationHeader)) {
            return response()->json(['error_code' => '401', 'error_description' => 'Unauthorized'], 401);
        }

        $result = $this->fetchRecords($request->input('endpoint'), $params);

        return response()->json($result);
    }

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

    private function fetchRecords($endpoint, $params)
    {
        $MailChimpDataCenter = $params['data_center'];
        $MailChimpApiKey = $params['api_key'];

        $auth = base64_encode('user:' . $MailChimpApiKey);
        $url = "https://$MailChimpDataCenter.api.mailchimp.com/3.0" . $endpoint;
        $url_params = [];
        if (!empty($params['count'])) $url_params['count'] = $params['count'];
        if (!empty($params['offset'])) $url_params['offset'] = $params['offset'];
        if (!empty($url_params)) $url .= "?" . http_build_query($url_params);

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
}
