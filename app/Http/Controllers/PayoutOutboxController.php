<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\PayoutOutbox\Service as PayoutOutboxService;

class PayoutOutboxController extends Controller
{
    use Traits\HasCrudMethods;

    public function createPayoutOutboxPartition()
    {
        $response =  (new PayoutOutboxService())->createPayoutOutboxPartition();

        return ApiResponse::json($response);
    }

    public function undoPayout(string $id) {
        $response = $this->service()->undoPayout($id);

        return ApiResponse::json($response);
    }

    public function resumePayout(string $id) {
        $response = $this->service()->resumePayout($id);

        return ApiResponse::json($response);
    }

    public function getOrphanedPayouts() {
        $response = $this->service()->getOrphanedPayoutsFromOutbox();

        return ApiResponse::json($response);
    }

    public function deleteOrphanedPayouts() {
        $input = Request::all();

        $response = $this->service()->deleteOrphanedPayouts($input);

        return ApiResponse::json($response);
    }
}
