<?php
namespace RZP\Jobs;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Entity as E;
use RZP\Gateway\GatewayManager;

class CorePaymentServiceSync extends Job
{
    const REDIS_KEY_PREFIX       = 'cps_sync_timestamp';
    const REDIS_KEY_TTL          = 30 * 60; // Seconds
    const MUTEX_KEY_PREFIX       = 'cps_sync:';
    const MUTEX_TIMEOUT          = 30;
    const RETRY_COUNT            = 10;
    const MIN_RETRY_DELAY        = 200;
    const MAX_RETRY_DELAY        = 400;
    const INPUT                  = 'input';
    const DEFAULT_GATEWAY_ENTITY = 'mozart';

    /**
     * @var string
     */
    protected $queueConfigKey = 'core_payment_service_sync';

    /**
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::CPS_GATEWAY_TRANSACTION_SYNC_REQUEST,
            [
                'mode' => $this->getMode(),
                'data' => $this->data,
            ]
        );

        try
        {
            $this->syncGatewayTransaction();

            $this->trace->info(
                TraceCode::CPS_GATEWAY_TRANSACTION_SYNC_SUCCESS,
                [
                    'data'        => $this->data,
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::CPS_GATEWAY_TRANSACTION_JOB_EXCEPTION,
                $this->data);
        }
        finally
        {
            $this->delete();
        }
    }

    protected function syncGatewayTransaction()
    {
        $app = App::getFacadeRoot();

        $gateway = $this->data['gateway'];

        $paymentId = $this->data[E::PAYMENT_ID];

        $action = $this->data[self::INPUT][E::ACTION];

        $namespace = (new GatewayManager($app))->getCpsServiceSyncDriver($gateway);

        $gatewaySync = $namespace . '\\CpsGatewayEntitySync';
        if (class_exists($gatewaySync) === false)
        {
            // We are storing all gateway entities in mozart table for now
            // This is temporary and all this will be part of payment container
            $gatewaySync = 'RZP\Gateway\\' . studly_case(self::DEFAULT_GATEWAY_ENTITY) . '\\Gateway';
        }

        $app['api.mutex']->acquireAndRelease(
            self::MUTEX_KEY_PREFIX . $paymentId,
            function () use ($gateway, $paymentId, $action, $gatewaySync)
            {
                $lastProcessedTime = $this->getLastProcessedTime($gateway, $paymentId, $action);

                if (empty($lastProcessedTime) === true)
                {
                    $lastProcessedTime = $this->data['timestamp'];
                }

                if ($lastProcessedTime <= $this->data['timestamp'])
                {
                    (new $gatewaySync)->syncGatewayTransaction($this->data['gateway_transaction'], $this->data[self::INPUT]);

                    $this->setLastProcessedTime($gateway, $paymentId, $action, $this->data['timestamp']);
                }
            },
            self::MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_CPS_ANOTHER_SYNC_IN_PROGRESS,
            self::RETRY_COUNT,
            self::MIN_RETRY_DELAY,
            self::MAX_RETRY_DELAY
        );
    }

    protected function getLastProcessedTime(string $gateway, string $paymentId, string $action)
    {
        $app = App::getFacadeRoot();

        $syncTimestampKey =  implode('_', [self::REDIS_KEY_PREFIX, $gateway, $paymentId, $action]);

        return $app['cache']->get($syncTimestampKey);
    }

    protected function setLastProcessedTime(string $gateway, string $paymentId, string $action, int $timestamp)
    {
        $app = App::getFacadeRoot();

        $syncTimestampKey =  implode('_', [self::REDIS_KEY_PREFIX, $gateway, $paymentId, $action]);

        $app['cache']->put($syncTimestampKey, $timestamp, self::REDIS_KEY_TTL);
    }
}
