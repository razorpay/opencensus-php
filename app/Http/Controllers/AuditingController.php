<?php

namespace RZP\Http\Controllers;

use ApiResponse;

use RZP\Models\Base\Audit\Service as AuditingService;

class AuditingController extends Controller
{

    public function createAuditInfoPartition()
    {
        $response =  (new AuditingService())->createAuditInfoPartition();

        return ApiResponse::json($response);
    }

    public function getMerchantAuditInfo($id)
    {
        $response = (new AuditingService())->getMerchantAuditInfo($id);

        return $response;
    }

    public function getAuditInfo($entity,$id)
    {
        $response = (new AuditingService())->getAuditInfo($entity,$id);

        return $response;
    }
}
