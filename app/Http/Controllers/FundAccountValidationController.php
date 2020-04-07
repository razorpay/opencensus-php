<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class FundAccountValidationController extends Controller
{
    use Traits\HasCrudMethods;

    public function retry()
    {
        $input = Request::all();

        $entities = $this->service()->retry($input);

        return ApiResponse::json($entities);
    }

    public function retryAllFundAccountValidations()
    {
        $input = Request::all();

        $entities = $this->service()->retryAllFundAccountValidations($input);

        return ApiResponse::json($entities);
    }

    public function getFavByMerchantIdAndFavId($merchantId, $favId)
    {
        $entity = $this->service()->getFavByMerchantIdAndFavId($favId, $merchantId);

        return ApiResponse::json($entity);
    }
}
