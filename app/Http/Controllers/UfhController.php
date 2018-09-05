<?php

namespace RZP\Http\Controllers;

use ApiResponse;

use RZP\Models\Merchant\Account;
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
        if ($this->ba->isAdminAuth() === true)
        {
            $headers = [
                'X-Merchant-Id' => Account::SHARED_ACCOUNT,
            ];
        }
        else
        {
            $headers = [
                'X-Merchant-Id' => $this->ba->getMerchantId(),
            ];
        }

        $ufhConfig = [
            'base_uri'      => $this->config['applications.ufh.url'],
            'username'      => $this->config['applications.ufh.auth.username'],
            'password'      => $this->config['applications.ufh.auth.password'],
            'headers'       => $headers,
        ];

        return new UfhClient($ufhConfig);
    }
}
