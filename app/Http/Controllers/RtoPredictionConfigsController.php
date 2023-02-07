<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class RtoPredictionConfigsController extends Controller
{

    protected function updateMerchantConfigs()
    {
        $input = Request::all();

        $response = $this->app['rto_prediction_merchant_model_configs']->updateConfigs($input);

        return ApiResponse::json($response);
    }

    protected function updateMerchantModel()
    {
        $input = Request::all();

        $response = $this->app['rto_prediction_merchant_model_configs']->updateModel($input);

        return ApiResponse::json($response);
    }

    protected function getMerchantConfigs()
    {
        $input = Request::all();

        $response = $this->app['rto_prediction_merchant_model_configs']->get($input);

        return ApiResponse::json($response);
    }

    protected function deleteMerchantConfigs()
    {
        $input = Request::all();

        $response = $this->app['rto_prediction_merchant_model_configs']->delete($input);

        return ApiResponse::json($response);
    }

    protected function createMLModelConfigs()
    {
        $input = Request::all();

        $response = $this->app['rto_prediction_mlmodel_configs']->create($input);

        return ApiResponse::json($response);
    }

    protected function updateMLModelConfigs()
    {
        $input = Request::all();

        $response = $this->app['rto_prediction_mlmodel_configs']->update($input);

        return ApiResponse::json($response);
    }

    protected function getMLModelConfigs()
    {
        $input = Request::all();

        $response = $this->app['rto_prediction_mlmodel_configs']->get($input);

        return ApiResponse::json($response);
    }

}
