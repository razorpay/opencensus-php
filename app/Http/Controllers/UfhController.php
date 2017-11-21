<?php

namespace RZP\Http\Controllers;

use ApiResponse;

use Razorpay\Ufh\Client as UfhClient;

class UfhController extends Controller
{
    protected $ufhClient;

    public function getSignedUrl(string $fileId)
    {
        $ufhConfig = [
            'base_uri'      => $this->config['applications.ufh.url'],
            'username'      => $this->config['applications.ufh.auth.username'],
            'password'      => $this->config['applications.ufh.auth.password'],
            'X-Merchant-Id' => $this->ba->getMerchantId(),
        ];

        $ufhClient = (new UfhClient)->setConfig($ufhConfig);

        $response = $ufhClient->getSignedUrl($fileId);

        $data = json_decode($response->getBody(), true);

        return ApiResponse::json($data);
    }
}
