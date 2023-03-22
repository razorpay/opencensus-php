<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicAddressProvider;

use Razorpay\Trace\Logger as Trace;
use RZP\Exception\ServerErrorException;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class Service
{
    protected $app;

    const ADDRESS_INGESTION_JOB_CONFIG = 'address_ingestion_job_config';
    const PATH = "path";
    const BODY = 'body';
    const STATUS_CODE = 'status_code';
    const MERCHANT_ID = 'merchant_id';
    const SOURCE = 'source';

    const PARAMS = [
        self::ADDRESS_INGESTION_JOB_CONFIG  =>   [
            self::PATH   => 'v1/1cc/merchant/%s/address_ingestion/job/config',
        ]
    ];

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function getJobConfig($input, $merchantId)
    {
        $params = self::PARAMS[self::ADDRESS_INGESTION_JOB_CONFIG];

        $url = sprintf($params[self::PATH], $merchantId);

        try
        {
            return  $this->app['magic_address_service_client']->sendRequest($url, Requests::GET, $input);
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MAGIC_ADDRESS_SERVICE_GET_JOB_CONFIG_FAILED,
                []
            );

            return [];
        }
    }

    public function push1ccAddresses($input)
    {
        try
        {
            $input[self::MERCHANT_ID] = $this->app['basicauth']->getMerchant()->getId();

            $queueName = $this->app['config']->get('queue.one_cc_address_ingestion_standardization');

            $this->app['queue']->connection('sqs')->pushRaw(json_encode($input), $queueName);

            $this->app['trace']->info(
                TraceCode::ONE_CC_ADDRESS_INGESTION_STANDARDIZATION_SQS_PUSH_SUCCESS,
                [
                    self::MERCHANT_ID    => $input[self::MERCHANT_ID],
                    self::SOURCE => $input[self::SOURCE]
                ]);
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->error(
                TraceCode::ONE_CC_ADDRESS_INGESTION_STANDARDIZATION_SQS_PUSH_FAILED,
                [
                    self::MERCHANT_ID    => $input[self::MERCHANT_ID],
                    self::SOURCE => $input[self::SOURCE],
                    'message' => $e->getMessage()
                ]);
            throw $e;
        }
    }
}
