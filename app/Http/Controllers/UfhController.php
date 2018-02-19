<?php

namespace RZP\Http\Controllers;

use ApiResponse;

use Razorpay\Ufh\Client as UfhClient;

class UfhController extends Controller
{
    public function getSignedUrl(string $fileId)
    {
        $response = $this->ufhClient()->getSignedUrl($fileId, []);

        return ApiResponse::json($response);
    }

    /**
     * Builds and returns ufh client
     *
     * @return UfhClient
     */
    protected function ufhClient(): UfhClient
    {
        $ufhConfig = [
            'base_uri'      => $this->config['applications.ufh.url'],
            'username'      => $this->config['applications.ufh.auth.username'],
            'password'      => $this->config['applications.ufh.auth.password'],
            'headers'       => [
                'X-Merchant-Id' => $this->ba->getMerchantId(),
            ]
        ];

        return new UfhClient($ufhConfig);
    }
}
