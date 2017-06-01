<?php

namespace RZP\Http\Controllers;

use Request;
use Trace;
use ApiResponse;

use Razorpay\OAuth\Application;

class OAuthApplicationController extends Controller
{
    /**
     * @var \RZP\Models\Merchant\Entity
     */
    protected $merchant;

    /**
     * @var Application\Service
     */
    protected $appService;

    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->appService = new Application\Service;
    }

    public function createApplication()
    {
        $input = Request::all();

        $merchantId = $this->merchant->getId();

        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        $app = $this->appService->createApplication($input);

        return ApiResponse::json($app);
    }

    public function get(string $id)
    {
        $app = $this->appService->fetch($id);

        return ApiResponse::json($app);
    }

    public function getMultiple()
    {
        $input = Request::all();

        $merchantId = $this->merchant->getId();

        $input[Application\Entity::MERCHANT_ID] = $merchantId;

        $apps = $this->appService->fetchMultiple($input);

        return ApiResponse::json($apps);
    }
}
