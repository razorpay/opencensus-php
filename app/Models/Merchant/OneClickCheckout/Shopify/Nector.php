<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use RZP\Models\Base;
use RZP\Models\Merchant\OneClickCheckout\Monitoring;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class Nector extends Base\Core
{
    const MAGIC_CHECKOUT_INTEGRATION_PATH                 = 'v1/magic/integrations';

    const DEDUCT_NECTOR_COINS                        = 'nector/coins/deduct';

    const REFUND_NECTOR_COINS                        = 'nector/coins/refund';

    protected $monitoring;

    public function __construct()
    {
        parent::__construct();

        $this->monitoring = new Monitoring();
    }

    public function deductNectorPayment($customerMobile, $orderAmountValue, $orderId)
    {
        try {

            $start = millitime();

            $body = [
                'order_id' => $orderId,
                'amount' => $orderAmountValue,
                'mobile' => $customerMobile,
            ];

            $path = self::MAGIC_CHECKOUT_INTEGRATION_PATH . '/' . self::DEDUCT_NECTOR_COINS;

            $this->trace->info(TraceCode::DEDUCT_NECTOR_COINS_STARTED,['amount' => $orderAmountValue]);

            $response = $this->app['magic_checkout_service_client']->sendRequest($path, $body, Requests::POST);

            $this->trace->info(TraceCode::DEDUCT_NECTOR_COINS_SUCCESS,['amount'=> $orderAmountValue]);

            $this->monitoring->traceResponseTime(TraceCode::DEDUCT_NECTOR_COINS_RESPONSE_TIME,$start);

            return $response;
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::DEDUCT_NECTOR_COINS_ERROR,['error'=> $e->getMessage()]);
            return [];
        }
    }

    public function refundNectorPayment($customerMobile, $orderAmountValue, $orderId)
    {
        try
        {
            $start = millitime();

            $body = [
                'order_id' => $orderId,
                'amount' => $orderAmountValue,
                'mobile' => $customerMobile,
            ];

            $path = self::MAGIC_CHECKOUT_INTEGRATION_PATH . '/' . self::REFUND_NECTOR_COINS;

            $this->trace->info(TraceCode::REFUND_NECTOR_COINS_STARTED,['amount' => $orderAmountValue]);

            $response = $this->app['magic_checkout_service_client']->sendRequest($path, $body, Requests::POST);

            $this->trace->info(TraceCode::REFUND_NECTOR_COINS_SUCCESS,['amount'=> $orderAmountValue]);

            $this->monitoring->traceResponseTime(TraceCode::REFUND_NECTOR_COINS_RESPONSE_TIME,$start);

            return $response;
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::REFUND_NECTOR_COINS_ERROR,['error'=> $e->getMessage()]);

            return [];
        }
    }
}
