<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class FundAccountValidationController extends Controller
{
    use Traits\HasCrudMethods;

    public function getFavByMerchantIdAndFavId($merchantId, $favId)
    {
        $entity = $this->service()->getFavByMerchantIdAndFavId($favId, $merchantId);

        return ApiResponse::json($entity);
    }

    public function bulkPatchFavAsFailed()
    {
        $input = Request::all();

        $entity = $this->service()->bulkPatchFavAsFailed($input);

        return ApiResponse::json($entity);
    }

    public function validateVpaInternal()
    {
        $input = Request::all();

        $response = $this->service()->validateVpaInternal($input);

        return ApiResponse::json($response);
    }


    public function getFundAccountValidation($id)
    {
        $input = Request::all();

        $entity = $this->service()->fetchFAV($id, $input);

        return ApiResponse::json($entity);
    }

    public function getFundAccountValidations()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }


    public function validateBankAccountInternal()
    {
        $input = Request::all();

        $response = $this->service()->validateBankAccountInternal($input);

        return ApiResponse::json($response);
    }

    public function fetchPricingInfoForFavService()
    {
        $input = Request::all();

        $response = $this->service()->fetchPricingInfoForFavService($input);

        return ApiResponse::json($response);
    }

}
