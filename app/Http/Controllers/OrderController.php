<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Order\OrderMeta;

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

    public function fetchOrderByIdWithOffer($id)
    {
        $input = Request::all();

        $data = $this->service()->fetchWithOffer($id, $input);

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
        $input = Request::all();

        $data = $this->service()->fetchByIdForAdmin($id, $input);

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

    public function internalOrderUpdate(string $id)
    {
        $input = Request::all();

        $data = $this->service()->internalOrderUpdate($id, $input);

        return ApiResponse::json($data);
    }

    public function internalOrderValidateTokenParams()
    {
        $input = Request::all();

        $data = $this->service()->internalOrderValidateTokenParams($input);

        return ApiResponse::json($data);
    }

    public function internalOrderValidateTransferParams()
    {
        $input = Request::all();

        $data = $this->service()->internalOrderValidateTransferParams($input);

        return ApiResponse::json($data);
    }

    public function internalOrderValidateBank()
    {
        $input = Request::all();

        $data = $this->service()->internalOrderValidateBank($input);

        return ApiResponse::json($data);
    }

    public function internalOrderValidateAmount()
    {
        $input = Request::all();

        $data = $this->service()->internalOrderValidateAmount($input);

        return ApiResponse::json($data);
    }

    public function internalOrderValidateCurrency()
    {
        $input = Request::all();

        $data = $this->service()->internalOrderValidateCurrency($input);

        return ApiResponse::json($data);
    }

    public function internalOrderValidateCheckoutConfig()
    {
        $input = Request::all();

        $data = $this->service()->internalOrderValidateCheckoutConfig($input);

        return ApiResponse::json($data);
    }

    public function internalOrderValidateTPV()
    {
        $input = Request::all();

        $data = $this->service()->internalOrderValidateTPV($input);

        return ApiResponse::json($data);
    }

    public function internalCreateOrderRelations()
    {
        $input = Request::all();

        $data = $this->service()->internalCreateOrderRelations($input);

        return ApiResponse::json($data);
    }

    public function updateCustomerDetailsFor1CCOrder(string $orderId)
    {
        $input = Request::all();

        if(isset($input['customer_details']['device']['id']))
        {
            $input['customer_details']['device']['user_agent'] = Request::header('X-User-Agent') ??
                Request::header('User-Agent') ?? null;

            $input['customer_details']['device']['ip'] = $this->app['request']->ip();
        }

        (new OrderMeta\Service())->updateCustomerDetailsFor1CCOrder($orderId, $input);

        return ApiResponse::json([], 200);
    }

    public function reset1CCOrder(string $orderId)
    {
        (new OrderMeta\Service())->reset1CCOrder($orderId);
        return ApiResponse::json([], 200);
    }
}
