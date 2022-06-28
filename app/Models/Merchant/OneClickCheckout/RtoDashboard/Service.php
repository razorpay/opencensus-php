<?php

namespace RZP\Models\Merchant\OneClickCheckout\RtoDashboard;

use Illuminate\Support\Str;
use RZP\Http\Request\Requests;
use RZP\Models\Order;

class Service
{
    protected $app;

    const DASHBOARD_LIST = 'dashboard_list';
    const PATH                                  = 'path';
    const CREATED_BY                 = "created_by";
    const MERCHANT_ID                = "merchant_id";

    const PARAMS = [
        self::DASHBOARD_LIST  =>   [
            self::PATH   => 'twirp/rzp.rto_prediction.dashboard.v1.DashboardAPI/List',
        ],
    ];

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function list($input,$merchantId)
    {
        $input = $this->addMerchantDetails($input,$merchantId);

        $params = self::PARAMS[self::DASHBOARD_LIST];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    protected function addMerchantDetails($input, $merchantId)
    {
        $input['merchant_id'] = $merchantId;

        return $input;
    }
}
