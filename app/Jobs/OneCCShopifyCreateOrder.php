<?php
namespace RZP\Jobs;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;
use RZP\Models\Merchant\OneClickCheckout;

class OneCCShopifyCreateOrder extends Job
{
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

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_PLACE_ORDER_JOB,
            $this->data
        );

        $app = App::getFacadeRoot();

        try
        {
            // skipping test env as shopify has a max limit on test orders and
            // running the job will hit the limit very quickly
            if ($app->environment(Environment::PRODUCTION) === true)
            {
                (new OneClickCheckout\Shopify\Service)->completeCheckoutWithLock($this->data, false);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SHOPIFY_1CC_PLACE_ORDER_JOB_EXCEPTION,
                $this->data
            );
        }
        finally
        {
            $this->delete();
        }
    }
}
