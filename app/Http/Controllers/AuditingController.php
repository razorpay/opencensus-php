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
}
