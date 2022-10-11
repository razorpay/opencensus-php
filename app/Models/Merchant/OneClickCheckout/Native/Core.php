<?php

namespace RZP\Models\Merchant\OneClickCheckout\Native;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Merchant1ccConfig;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\OneClickCheckout\Monitoring;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;

class Core extends Base\Core
{
    protected $monitoring;

    public function __construct()
    {
        parent::__construct();

        $this->monitoring = new Monitoring();
    }

    public function updateOrderStatus(string $orderId, string $merchantId,string $status)
    {
        $client = $this->getNativeClientByMerchant($merchantId);

        $body=  [
            Constants::STATUS => $status,
            Constants::ORDER_ID_KEY=>$orderId
        ];

        $method = Constants::POST;

        $resource = $this->getOrderStatusUpdateUrlByMerchant($merchantId)->getValue();

        try
        {
            $startTime = millitime();

            $response = $client->sendRequest(json_encode($body), $method, $resource);

            $elapsedTime = millitime() - $startTime;

            $this->monitoring->traceResponseTime(Metric::NATIVE_UPDATE_ORDER_STATUS_CALL_TIME, $elapsedTime, []);

            $this->trace->info(
                TraceCode::NATIVE_1CC_ORDER_STATUS_UPDATE_RES,
                [
                    'type' => 'update_order_status',
                    'body' => $body,
                    'response' => $response,
                    'time' => $elapsedTime
                ]
            );
            $updatedOrderStatus = [
                'status' => $status
            ];
            $this->monitoring->addTraceCount(Metric::NATIVE_UPDATE_ORDER_STATUS_SUCCESS_COUNT,  $updatedOrderStatus);
        }
        catch(\Exception $exception)
        {
            $this->monitoring->addTraceCount(Metric::NATIVE_UPDATE_ORDER_STATUS_ERROR_COUNT,['error_code' => TraceCode::NATIVE_1CC_API_ERROR]);

            $this->trace->error(
                TraceCode::NATIVE_1CC_ORDER_STATUS_UPDATE_API_ERROR,
                [
                    'merchant_id'=>$merchantId,
                    'body' => $body,
                    'error' => $exception->getMessage()
                ]
            );

            throw new Exception\ServerErrorException(
                $exception->getMessage(),
                $exception->getCode(),
                null,
                $exception
            );
        }
    }

    public function getNativeClientByMerchant(string $merchantId)
    {
        $credentials = $this->getNativeAuthByMerchant($merchantId);

        return new Client($credentials);
    }

    public function getNativeAuthByMerchant(string $merchantId)
    {
        return (new AuthConfig\Core())->ge1ccAuthConfigsByMerchantIdAndPlatform($merchantId,
            \RZP\Models\Merchant\OneClickCheckout\Constants::NATIVE
        );
    }

    private function getOrderStatusUpdateUrlByMerchant(string $merchantId)
    {
        return (new Merchant1ccConfig\Core())->get1ccConfigByMerchantIdAndType(
            $merchantId,
            Merchant1ccConfig\Type::ORDER_STATUS_UPDATE_URL
        );
    }

}
