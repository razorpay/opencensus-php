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
}
