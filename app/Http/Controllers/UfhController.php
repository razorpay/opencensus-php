<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Merchant\Account;
use Razorpay\Ufh\Client as UfhClient;
use RZP\Services\UfhService;
use RZP\Services\Mock\UfhService as MockUfhService;

class UfhController extends Controller
{
    // key for entity data array sent in Upload File request
    const ENTITY = 'entity';

    public function getSignedUrl(string $fileId)
    {
        $app = $this->app;

        $merchantId = $this->ba->getMerchantId();

        $isMockUfhService = $app['config']->get('applications.ufh.mock');

        if ($isMockUfhService === true)
        {
            $ufhService = $app['ufh.service'];

            $response = $ufhService->getSignedUrl($fileId, []);
        }
        else
        {
            $response = $this->ufhClient()->getSignedUrl($fileId, []);
        }

        (new UfhService($this->app, $merchantId))->validateUserRoleForAccess($response['type']);

        return ApiResponse::json($response);
    }

    public function uploadFileAndGetUrl()
    {
        $input = Request::all();
        $app = $this->app;
        $ufhServiceMock = $app['config']->get('applications.ufh.mock');
        $merchantId = isset($input['merchant_id']) == true ? $input['merchant_id'] : null;
        $params  = [];

        if (isset($input['Content-Disposition']) == true)
        {
            $params['Content-Disposition'] = $input['Content-Disposition'];
        }
        if ($ufhServiceMock === true)
        {
            $ufhService = new MockUfhService($app, $merchantId);
        }
        else
        {
            $ufhService = new UfhService($app, $merchantId);
        }

        $response = $ufhService->uploadFileAndGetUrl($input['file'],
                                                $input[UfhService::NAME],
                                                $input[UfhService::TYPE],
                                                $input[self::ENTITY],
                                                $params);

        return ApiResponse::json($response);
    }

    public function getSignedUrlByMid(string $mId, string $fileId)
    {
        $response = $this->ufhClient()->getSignedUrl($fileId, [], $mId);

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

        $headers['X-Task-Id'] = $this->app['request']->getTaskId();

        $ufhConfig = [
            'base_uri'      => $this->config['applications.ufh.url'],
            'username'      => $this->config['applications.ufh.auth.username'],
            'password'      => $this->config['applications.ufh.auth.password'],
            'headers'       => $headers,
        ];

        return new UfhClient($ufhConfig);
    }
}
