<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use Razorpay\Ufh\Client as UfhClient;

class UfhController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $ufhConfig = ['base_uri' => $this->config['applications.ufh.url']];

        $this->ufhClient = (new UfhClient())->setConfig($ufhConfig);
    }

    public function getSignedUrl($fileId)
    {
        $input = Request::all();

        $response = $this->ufhClient->getSignedUrl(
            $fileId, ['merchant_id' => $this->ba->getMerchantId()]);

        $data = json_decode($response->getBody(), true);

        return ApiResponse::json($data);
    }
}