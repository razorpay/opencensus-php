<?php

namespace RZP\Http\Controllers;

use ApiResponse;

use Razorpay\Ufh\Client as UfhClient;

class UfhController extends Controller
{
    public function getSignedUrl(string $fileId)
    {
        $response = $this->getUfhClient()->getSignedUrl($fileId, []);

        $data = json_decode($response->getBody(), true);

        return ApiResponse::json($data);
    }

    /**
     * Builds Ufh Client
     *
     * @return UfhClient
     */
    protected function getUfhClient(): UfhClient
    {
        $ufhConfig = [
            'base_uri'      => $this->config['applications.ufh.url'],
            'username'      => $this->config['applications.ufh.auth.username'],
            'password'      => $this->config['applications.ufh.auth.password'],
            'X-Merchant-Id' => $this->ba->getMerchantId(),
        ];

        return (new UfhClient)->setConfig($ufhConfig);
    }
}
