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
    const MAX_RETRY_ATTEMPTS = 9;

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
        catch (\Throwable $e)
        {
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
}
