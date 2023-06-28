<?php
namespace RZP\Jobs;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\OneClickCheckout\Shopify\Webhooks\Webhooks as ShopifyWebhooks;

class OneCCShopifyCreateOrder extends Job
{
    const BASE_RETRY_INTERVAL_SEC = 60;
    const BACKOFF_FACTOR = 5;
    const MAX_RETRY_ATTEMPTS = 7;

    /**
     * @var string
     */
    protected $queueConfigKey = 'one_cc_shopify_create_order';

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

        if ($this->data['type'] === 'webhook')
        {
            $this->processWebhook();
        }
        else
        {
            $this->placeShopifyOrder();
        }
    }

    // processWebhook is used to process incoming webhooks received from Shopify for auto refunds.
    protected function processWebhook()
    {
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_PROCESS_WEBHOOK_JOB,
            array_merge($this->data, ['attempts' => $this->attempts()]));
        try
        {
            (new ShopifyWebhooks())->processWebhookWithLock($this->data);
            $this->delete();
        }
        catch (BadRequestException $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SHOPIFY_1CC_PROCESS_WEBHOOK_JOB_EXCEPTION,
                ['error' => 'bad_request', 'code' => $e->getCode()]);
            // In case we do not have access to the account there is no use in retrying the job as it will always fail.
            if ($e->getCode() === ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_ACCESS_DENIED)
            {
                $this->delete();
            }
            // This scenario covers cases of Shopify downtime or getting rate limited in which retrying can succeed.
            $this->checkRetry('webhook');
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_COUNT, ['status' => 'failed']);
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SHOPIFY_1CC_PROCESS_WEBHOOK_JOB_EXCEPTION,
                []);
            $this->checkRetry('webhook');
        }
    }

    protected function placeShopifyOrder()
    {
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_PLACE_ORDER_JOB,
            array_merge($this->data, ['attempts' => $this->attempts()]));

        $app = App::getFacadeRoot();

        try
        {
            // skipping test env as shopify has a max limit on test orders and
            // running the job will hit the limit very quickly
            if ($app->environment(Environment::PRODUCTION) === true)
            {
                (new OneClickCheckout\Shopify\Service)->completeCheckoutWithLock($this->data, false);
            }
            $this->handlePrepayCODFlow();
            $this->delete();
        }
        catch (BadRequestException $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SHOPIFY_1CC_PLACE_ORDER_JOB_EXCEPTION,
                ['error' => 'BadRequestException']);
            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SHOPIFY_1CC_PLACE_ORDER_JOB_EXCEPTION,
                []);
            $this->delete();
            // $this->checkRetry('create_order');
        }
    }

    protected function checkRetry(string $event): void
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPTS)
        {
            $trace = $event === 'webhook' ? TraceCode::SHOPIFY_1CC_PROCESS_WEBHOOK_JOB_FAILED: TraceCode::SHOPIFY_1CC_PLACE_ORDER_JOB_FAILED;
            $this->trace->error(
                $trace,
                [
                    'attempts' => $this->attempts(),
                    'message'  => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]);
            $this->trace->count(Metric::SHOPIFY_1CC_SQS_JOB_EXCEEDED_MAX_RETRY_COUNT, ['job' => $event]);
            $this->delete();
        }
        else
        {
            $trace = $event === 'webhook' ? TraceCode::SHOPIFY_1CC_PROCESS_WEBHOOK_JOB_RELEASED: TraceCode::SHOPIFY_1CC_PLACE_ORDER_JOB_RELEASED;
            $delay = self::BASE_RETRY_INTERVAL_SEC + pow($this->attempts() + 1, self::BACKOFF_FACTOR);

            $this->trace->info(
                $trace,
                [
                    'attempts' => $this->attempts(),
                    'delay'    => $delay,
                ]);
            $this->trace->count(Metric::SHOPIFY_1CC_SQS_JOB_RETRY_COUNT, ['job' => $event]);
            $this->release($delay);
        }
    }

    protected function handlePrepayCODFlow()
    {
        try
        {
            (new OneClickCheckout\Shopify\Service)->checkPrepayCODFlow($this->data, false);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::ONE_CC_PREPAY_COD_CREATE_PL_FAILED,
                [
                    'order_id'    =>  $this->data['razorpay_order_id'],
                    'merchant_id' => $this->data['merchant_id'],
                    'error'       => $e->getMessage(),
                ]);
        }
    }
}
