<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Consumer\Service;

class ConsumerController extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->service = new Service;
    }

    public function migrateInternalApplicationsToCredcase()
    {
        $result = $this->service()->migrateInternalApplicationsToCredcase($this->input);
        return ApiResponse::json($result);
    }
}
