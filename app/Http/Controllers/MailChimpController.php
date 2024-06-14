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
    public function getRecords(Request $request) //Funcion para conseguir los resultados de todas las campañas. Va con el fetchRecords
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

    private function getCampaignIds($dataCenter, $apiKey) //Funcion para conseguir el id de todas las campañas
    {
        $auth = base64_encode('user:' . $apiKey);
        $campaigns = [];
        $offset = 0;
        $count = 1000;
        $moreResults = true;

        $sixMonthsAgo = Carbon::now()->subMonths(6);

        while ($moreResults) {
            $url = "https://$dataCenter.api.mailchimp.com/3.0/campaigns?fields=campaigns.id,campaigns.send_time&offset=$offset&count=$count";
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $auth,
            ])->get($url);

            $data = $response->json();
            if (isset($data['campaigns']) && count($data['campaigns']) > 0) {
                foreach ($data['campaigns'] as $campaign) {
                    if (Carbon::parse($campaign['send_time'])->greaterThanOrEqualTo($sixMonthsAgo)) {
                        $campaigns[] = $campaign['id'];
                    }
                }
                $offset += $count;
            } else {
                $moreResults = false;
            }
        }

        return $campaigns;
    }



    public function getOpenDetails(Request $request) //Funcion para conseguir los contactos que han abierto el mail de cada campaña, cuando se le pasa el Id de la campaña
    {                                                //Llama a los Ids de cada campaña de la petición de getCampaignsIds
        $request->validate([
            'data_center' => 'required|string',
            'api_key' => 'required|string',
        ]);

        $dataCenter = $request->input('data_center');
        $apiKey = $request->input('api_key');

        $campaignIds = $this->getCampaignIds($dataCenter, $apiKey);

        //return $campaignIds;  //Testeo del retorno de las Ids que se están devolviendo
        //Log::info('Campaign IDs: ' . json_encode($campaignIds));

        $openDetails = [];

        // Para cada ID de campaña, obtener los detalles de apertura y almacenarlos en el array
        foreach ($campaignIds as $campaignId) {
            $details = $this->fetchOpenDetails($dataCenter, $apiKey, $campaignId);
            $openDetails[] = $details;
        }

        return response()->json($openDetails);
    }

    private function fetchOpenDetails($dataCenter, $apiKey, $campaignId)
    {
        $auth = base64_encode('user:' . $apiKey);
        $offset = 0;
        $count = 1000;

        $url = "https://$dataCenter.api.mailchimp.com/3.0/reports/$campaignId/open-details?fields=members.campaign_id,members.list_id,members.list_is_active,members.contact_status,members.email_address,members.merge_fields.FNAME,members.merge_fields.LNAME,members.merge_fields.MMERGE5,members.merge_fields.MMERGE6,members.opens_count,members.opens.timestamp&offset=$offset&count=$count";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $auth,
        ])->get($url);

        return $response->json();
    }
}
