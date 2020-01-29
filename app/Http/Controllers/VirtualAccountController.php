<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class VirtualAccountController extends Controller
{
    use Traits\HasCrudMethods;

    public function createForOrder(string $id)
    {
        $input = Request::all();

        $response = $this->service()->createForOrder($id, $input);

        return ApiResponse::json($response);
    }

    public function closeVirtualAccount(string $id)
    {
        $response = $this->service()->closeVirtualAccount($id);

        return ApiResponse::json($response);
    }

    public function closeVirtualAccountsByCloseBy()
    {
        $response = $this->service()->closeVirtualAccountsByCloseBy();

        return ApiResponse::json($response);
    }

    public function getPayments(string $id)
    {
        $input = Request::all();

        $response = $this->service()->fetchPayments($id, $input);

        return ApiResponse::json($response);
    }

    public function addReceiver(string $id)
    {
        $input = Request::all();

        $data = $this->service()->addReceiver($id, $input);

        return ApiResponse::json($data);
    }

    /**
     * This function is used for the offline payments
     * We create an order first and then create a VA using that
     */
    public function createOfflineQr()
    {
        $input = Request::all();

        $va = $this->service()->createOfflineQr($input);

        return ApiResponse::json($va);
    }
}
