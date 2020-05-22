<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Merchant\Account;
use Razorpay\Ufh\Client as UfhClient;
use RZP\Services\UfhService;

class UfhController extends Controller
{
    // key for entity data array sent in Upload File request
    const ENTITY = 'entity';

    public function getSignedUrl(string $fileId)
    {
        $response = $this->ufhClient()->getSignedUrl($fileId, []);

        return ApiResponse::json($response);
    }

    public function uploadFileAndGetUrl()
    {
        $input = Request::all();

        $response = $this->app['ufh.service']->uploadFileAndGetUrl($input['file'],
                                                $input[UfhService::NAME],
                                                $input[UfhService::TYPE],
                                                $input[self::ENTITY],
                                                $input[UfhService::METADATA]);

        return ApiResponse::json($response);
    }

    /**
     * Builds and returns ufh client
     *
     * @return UfhClient
     */
    protected function ufhClient(): UfhClient
    {
        if (($this->ba->isAppAuth() === true) and
            ($this->ba->isAdminAuth() === true))
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
