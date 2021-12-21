<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Http\BasicAuth;
use RZP\Models\Payout\Batch as PayoutsBatch;

use RZP\Http\Controllers\Traits\HasCrudMethods;

class PayoutsBatchController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = (new PayoutsBatch\Service());
    }

    use HasCrudMethods;

    public function createXDemoPayoutCron()
    {
        // Setting mode inside service layer does not work
        $this->app['basicauth']->setModeAndDbConnection('test');

        // Batch payouts can only be accessed via Private Auth
        $this->app['basicauth']->setBasicType(BasicAuth\Type::PRIVATE_AUTH);

        $merchant_id = \RZP\Models\Merchant\Account::X_DEMO_PROD_ACCOUNT;

        $this->app['basicauth']->setMerchantById($merchant_id);

        $response = $this->service()->createXDemoPayoutCron();

        return ApiResponse::json($response);
    }
}
