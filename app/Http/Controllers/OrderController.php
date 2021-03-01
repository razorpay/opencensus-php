<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class OrderController extends Controller
{
    use Traits\HasCrudMethods;

    public function createOrder()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function getOrders()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function fetchOrderById($id)
    {
        $input = Request::all();

        $data = $this->service()->fetch($id, $input);

        return ApiResponse::json($data);
    }

    // This is used to fetch Order based on ID without validating MID
    public function fetchOrderDetailById($id)
    {
        $data = $this->service()->fetchById($id);

        return ApiResponse::json($data);
    }

    // This is used to fetch Order based on ID without validating MID. Will return all entities since it is for admin route
    public function fetchOrderDetailByIdAdmin($id)
    {
        $data = $this->service()->fetchByIdForAdmin($id);

        return ApiResponse::json($data);
    }

    public function fetchPayments($id)
    {
        $input = Request::all();

        $payments = $this->service()->fetchPaymentsFor($id, $input);

        return ApiResponse::json($payments);
    }

    public function fetchLineItems($id)
    {
        $lineItems = $this->service()->fetchLineItemsFor($id);

        return ApiResponse::json($lineItems);
    }

    public function bulkSyncOrderToPgRouter()
    {
        $input = Request::all();

        $data = $this->service()->bulkSyncOrderToPgRouter($input);

        return ApiResponse::json($data);
    }

    public function fetchProductDetailsForOrder($id)
    {
        $data = $this->service()->fetchProductDetailsForOrder($id);

        return ApiResponse::json($data);
    }
}
