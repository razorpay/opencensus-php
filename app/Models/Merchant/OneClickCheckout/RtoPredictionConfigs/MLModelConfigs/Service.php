<?php

namespace RZP\Models\Merchant\OneClickCheckout\RtoPredictionConfigs\MLModelConfigs;

use Illuminate\Support\Str;
use RZP\Http\Request\Requests;
use RZP\Models\Order;

class Service
{
    protected $app;

    const PATH = 'path';
    const CREATE_MODEL_CONFIGS = 'create_model_configs';
    const UPDATE_MODEL_CONFIGS = 'update_model_configs';
    const GET_MODEL_CONFIGS = 'get_model_configs';

    const MERCHANT_ID = "merchant_id";

    const PARAMS = [
        self::CREATE_MODEL_CONFIGS => [
            self::PATH => 'twirp/rzp.rto_prediction.mlmodel_configs.v1.MLmodelConfigsAPI/Create',
        ],
        self::UPDATE_MODEL_CONFIGS => [
            self::PATH => 'twirp/rzp.rto_prediction.mlmodel_configs.v1.MLmodelConfigsAPI/UpdateByName',
        ],
        self::GET_MODEL_CONFIGS => [
            self::PATH => 'twirp/rzp.rto_prediction.mlmodel_configs.v1.MLmodelConfigsAPI/GetByParams',
        ],
    ];

    public function __construct($app = null)
    {
        if ($app === null) {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function create($input)
    {
        $params = self::PARAMS[self::CREATE_MODEL_CONFIGS];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function update($input)
    {
        $params = self::PARAMS[self::UPDATE_MODEL_CONFIGS];

        return $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function get($input)
    {
        $params = self::PARAMS[self::GET_MODEL_CONFIGS];

        $response = $this->app['rto_prediction_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
        return $response;
    }
}
