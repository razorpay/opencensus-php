<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Order\OrderMeta;
use RZP\Exception\BaseException;
use RZP\Trace\TraceCode;
use function PHPUnit\Framework\isEmpty;

class OrderController extends Controller
{
    use Traits\HasCrudMethods;

    const BARRICADE_MERCHANT_INTEGRATION_ORDER_FETCH = 'merchant_integration_order_fetch';

    public function createOrder()
    {
        $orderRequest = Request::all();

        if (isset($orderRequest["payment"]["method"]) === true || isset($orderRequest["payment_config"]) === true)
        {
            $paymentRequest = $this->getPaymentRequest($orderRequest);
        }

        //If order id is passed in the request - retry payment request scenario
        if (isset($orderRequest["id"]) === true)
        {
            $orderResponse = $this->service()->fetchOrderCreateResponse($orderRequest["id"]);
        }
        else
        {
            $orderResponse = $this->service()->create($orderRequest);
        }

        $response = $orderResponse;

        //If payment request is not empty then create the payment after removing capture config
        if (isset($paymentRequest["method"]) === true)
        {
            $paymentRequest["order_id"] = $orderResponse["id"];

            if (isset($paymentRequest["amount"]) === false)
            {
                $paymentRequest["amount"] = $orderResponse["amount"];
            }

            $paymentRequest["currency"] = $orderResponse["currency"];

            $paymentResponse = (new PaymentCreateController)->createS2SPaymentFromOrderRequest($paymentRequest);

            //Updating the order response, to get the latest value of status and attempt field
            $response = $this->service()->fetchOrderCreateResponse($orderResponse["id"]);

            $response["payment_workflow"] = $paymentResponse;
        }

        return ApiResponse::json($response);
    }

    public function getOrders()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        try
        {
            //Event to be triggered only for PG Merchant Dashboard
            if (($this->ba->isMerchantDashboardApp() === true) and
                ($this->ba->isProductPrimary() === true))
            {
                $this->service()->sendSelfServeSuccessAnalyticsEventToSegmentForFetchingOrderDetails($input);
            }
        }

        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ORDER_SEGMENT_EVENT_PUSH_FAILED, []);
        }

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

        try
        {
            //Event to be triggered only for PG Merchant Dashboard
            if (($this->ba->isMerchantDashboardApp() === true) and
                ($this->ba->isProductPrimary() === true))
            {
                $this->service()->sendSelfServeSuccessAnalyticsEventToSegmentForFetchingOrderDetailsFromOrderId();
            }
        }

        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ORDER_SEGMENT_EVENT_PUSH_FAILED, []);
        }

        $this->pushForBarricade($data);

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

    public function fetchOrderDetailsForCheckout()
    {
        $input = Request::all();

        $data = $this->service()->fetchOrderDetailsForCheckout($input);

        return ApiResponse::json($data);
    }

    public function fetchPayments($id)
    {
        $input = Request::all();

        $payments = $this->service()->fetchPaymentsFor($id, $input);

        return ApiResponse::json($payments);
    }

    public function fetchInternalPayments($id)
    {
        $input = Request::all();

        $payments = $this->service()->fetchInternalPaymentsFor($id, $input);

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

    public function internalOrderRelationsFetch()
    {
        $input = Request::all();

        $data = $this->service()->internalOrderRelationsFetch($input);

        return ApiResponse::json($data);
    }

    public function internalOrderAssociationsFetch()
    {
        $input = Request::all();

        $data = $this->service()->internalOrderAssociationsFetch($input);

        return ApiResponse::json($data);
    }

    public function internalCreateOrderBankAccountRelations()
    {
        $input = Request::all();

        $data = $this->service()->internalCreateOrderBankAccountRelations($input);

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

        try
        {
            (new OrderMeta\Service())->updateCustomerDetailsFor1CCOrder($orderId, $input);

            return ApiResponse::json([], 200);
        }
        catch (\Throwable $ex)
        {
            if (($ex instanceof BaseException) === true)
            {
                switch ($ex->getError()->getInternalErrorCode())
                {
                    case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                    case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                    case ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE:
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 503);
                }
            }
            throw $ex;
        }
    }

    public function reset1CCOrder(string $orderId)
    {
        try
        {
            (new OrderMeta\Service())->reset1CCOrder($orderId);
            return ApiResponse::json([], 200);
        }
        catch (\Throwable $ex)
        {
            if (($ex instanceof BaseException) === true)
            {
                switch ($ex->getError()->getInternalErrorCode())
                {
                    case ErrorCode::GATEWAY_ERROR_REQUEST_ERROR:
                    case ErrorCode::GATEWAY_ERROR_TIMED_OUT:
                    case ErrorCode::SERVER_ERROR_PGROUTER_SERVICE_FAILURE:
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 503);
                }
            }
            throw $ex;
        }

    }

    public function update1CCOrderNotes(string $orderId)
    {
        $input = Request::all();

        (new OrderMeta\Service())->update1CCOrderNotes($orderId, $input);

        return ApiResponse::json([], 200);
    }

    public function getCODOrders(){

        $input = Request::all();

        $data = $this->service()->getCODOrders($input);

        return ApiResponse::json($data);
    }

    public function getPrepayOrders(){

        $input = Request::all();

        $data = $this->service()->getPrepayOrders($input);

        return ApiResponse::json($data);
    }

    public function getPrepayOrder(string $orderId){

        $input = Request::all();

        $data = $this->service()->getPrepayOrder($orderId, $input);

        return ApiResponse::json($data);
    }

    public function updateActionFor1ccOrder(){

        $input = Request::all();

        $merchant = $this->ba->getMerchant();

        $userEmail = $this->ba->getUser()->getEmail();

        $data = (new OrderMeta\Service())->updateActionFor1ccOrders($input,$merchant,$userEmail);

        return ApiResponse::json($data);
    }

    public function review1ccOrder(){

        $input = Request::all();

        $data = (new OrderMeta\Service())->review1ccOrder($input);

        return ApiResponse::json($data);

    }

    public function getOffersForOrder(string $orderId)
    {
        $input = Request::all();

        $data = (new OrderMeta\Service())->getOffersForOrder($orderId, $input);

        return ApiResponse::json($data, 200);
    }


    protected function pushForBarricade($data): void
    {

        if ($this->app['rzp.mode'] !== Mode::LIVE ){
            return;
        }

        $data['action'] = [
            'action' => self::BARRICADE_MERCHANT_INTEGRATION_ORDER_FETCH
        ];

        try {

            $waitTime = 600;
            $queueName = $this->app['config']->get('queue.barricade_verify.' . $this->app['rzp.mode']);
            $this->app['queue']->connection('sqs')->later($waitTime, "Barricade Queue Push", json_encode($data), $queueName);

        } catch (\Throwable $ex) {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::BARRICADE_SQS_PUSH_FAILURE,
                [
                    'Data' => $data,
                ]);
        }
    }

    protected function getPaymentRequest(array &$orderRequest)
    {
        $paymentRequest = $orderRequest["payment"];

        //Removing the payment request body from the order request
        //Will refill the capture config details in the order request
        unset($orderRequest["payment"]);

        $captureConfig = [];

        if (isset($paymentRequest["capture"]) === true)
        {
            $captureConfig["capture"] = $paymentRequest["capture"];

            unset($paymentRequest["capture"]);
        }

        if (isset($paymentRequest["capture_options"]) === true)
        {
            $captureConfig["capture_options"] = $paymentRequest["capture_options"];

            unset($paymentRequest["capture_options"]);
        }

        //Adding capture config in the order request only if capture config is not empty
        if (empty($captureConfig) === false)
        {
            $orderRequest["payment"] = $captureConfig;
        }

        // payment capture config is accepted either in the payment_config key as per the new contract
        // or in the payment struct as the per old struct.
        if (isset($orderRequest["payment_config"]) === true && empty($orderRequest["payment_config"]) === false)
        {
            $orderRequest["payment"] = $orderRequest["payment_config"];

            unset($orderRequest["payment_config"]);
        }

        //If payment key is not empty after removing capture config
        // then it is consolidate api
        if (empty($paymentRequest) === false)
        {
            //for handling unique receipt condition ir-respective of merchant features
            if (isset($orderRequest["receipt"]) === true)
            {
                $orderRequest["unique_receipt"] = true;
            }
        }

        return $paymentRequest;
    }

}
