<?php

namespace RZP\Models\Merchant\OneClickCheckout\RtoPredictionConfigs\MerchantMLModelConfigs;

use Illuminate\Support\Str;
use RZP\Http\Request\Requests;
use RZP\Models\Order;

class Service
{
    protected $app;

    const PATH                                  = 'path';
    const UPDATE_MERCHANT_CONFIGS = 'update_merchant_configs';
    const GET_MERCHANT_CONFIGS = 'get_merchant_configs';
    const DELETE_MERCHANT_CONFIGS = 'delete_merchant_configs';
    const UPDATE_MERCHANT_MODEL = 'update_merchant_model';

    const MERCHANT_ID                = "merchant_id";

    const PARAMS = [
        self::UPDATE_MERCHANT_CONFIGS  =>   [
            self::PATH   => 'twirp/rzp.rto_prediction.merchant_ml_models.v1.MerchantMLModelsAPI/UpdateConfigsByMerchantId',
        ],
        self::GET_MERCHANT_CONFIGS  =>   [
            self::PATH   => 'twirp/rzp.rto_prediction.merchant_ml_models.v1.MerchantMLModelsAPI/GetConfigsByMerchantId',
        ],
        self::DELETE_MERCHANT_CONFIGS  =>   [
            self::PATH   => 'twirp/rzp.rto_prediction.merchant_ml_models.v1.MerchantMLModelsAPI/DeleteConfigsByMerchantId',
        ],
        self::UPDATE_MERCHANT_MODEL  =>   [
            self::PATH   => 'twirp/rzp.rto_prediction.merchant_ml_models.v1.MerchantMLModelsAPI/Update',
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

    public function updateConfigs($input)
    {
        $params = self::PARAMS[self::UPDATE_MERCHANT_CONFIGS];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function updateModel($input)
    {
        $params = self::PARAMS[self::UPDATE_MERCHANT_MODEL];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function get($input)
    {
        $params = self::PARAMS[self::GET_MERCHANT_CONFIGS];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function delete($input)
    {
        $params = self::PARAMS[self::DELETE_MERCHANT_CONFIGS];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }
}
