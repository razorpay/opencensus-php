<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Metric;
use RZP\Models\Order\OrderMeta;

class RzpOrders extends Base\Core
{
    protected $monitoring;

    public function __construct()
    {
        parent::__construct();
        $this->monitoring = new Monitoring();
    }

    public function createOrder(array $input)
    {
        try
        {
            return (new Order\Service)->createOrder($input);
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_PG_ROUTER_FAILED,
                [
                    'type'  => 'order_create',
                    'error' => $e->getMessage(),
                ]);
            if ($e->getCode() === "BAD_REQUEST_ORDER_AMOUNT_EXCEEDS_MAX_AMOUNT")
            {
                $this->monitoring->addTraceCount(Metric::SHOPIFY_1CC_ORDER_AMOUNT_EXCEEDS_ERROR_COUNT, ['error_type'  => 'order_create']);
            }
            else
            {
                $this->monitoring->addTraceCount(Metric::SHOPIFY_1CC_PG_ROUTER_ERROR_COUNT, ['error_type'  => 'order_create']);
            }

            throw $e;
        }
    }

    public function findOrderByIdAndMerchant(string $orderId)
    {
        try
        {
            return $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_PG_ROUTER_FAILED,
                [
                    'type'  => 'order_fetch',
                    'error' => $e->getMessage(),
                ]);
            $this->monitoring->addTraceCount(Metric::SHOPIFY_1CC_PG_ROUTER_ERROR_COUNT, ['error_type'  => 'order_fetch']);
            throw $e;
        }
    }

    public function updateOrderNotes(string $orderId, array $notes): void
    {
        try
        {
            (new Order\Service)->update($orderId, ['notes' => $notes]);
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_PG_ROUTER_FAILED,
                [
                    'type'  => 'order_notes_update',
                    'error' => $e->getMessage(),
                ]);
            $this->monitoring->addTraceCount(Metric::SHOPIFY_1CC_PG_ROUTER_ERROR_COUNT, ['error_type'  => 'order_notes_update']);
            throw $e;
        }
    }

    public function updateReceipt(Order\Entity $order, string $receipt): void
    {
        try
        {
            (new Order\Core)->updateReceipt($order, $receipt);
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_PG_ROUTER_FAILED,
                [
                    'type'  => 'order_notes_update',
                    'error' => $e->getMessage(),
                ]);
            $this->monitoring->addTraceCount(Metric::SHOPIFY_1CC_PG_ROUTER_ERROR_COUNT, ['error_type'  => 'order_receipt_update']);
            throw $e;
        }
    }

    public function updateUtmParameters(string $orderId, array $utmParameters=[]):void
    {
        try
        {
            foreach($utmParameters as $key=>$value)
            {
                if(is_null($value) || $value === '')
                {
                    unset($utmParameters[$key]);
                }
            }
            if(count($utmParameters) > 0)
            {
                (new OrderMeta\Service)->updateUtmParametersFor1CCOrder($orderId,$utmParameters);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_PG_ROUTER_FAILED,
                [
                    'type'  => 'order_utm_parameter_update',
                    'error' => $e->getMessage(),
                ]);

            $this->monitoring->addTraceCount(Metric::SHOPIFY_1CC_PG_ROUTER_ERROR_COUNT, ['error_type'  => 'order_utm_parameter_update']);
        }
    }
}
