<?php

namespace RZP\Services;

use App;

use RZP\Base\ConnectionType;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Jobs\Job;
use RZP\Jobs\OneCCReviewCODOrder;
use RZP\Models\Order;
use RZP\Trace\TraceCode;
use RZP\Models\Order\OrderMeta\Order1cc;
use RZP\Models\Merchant\OneClickCheckout;

class RtoOrderActionRetryHandler extends Job
{
    const PROCESSING_DELAY = 100;

    protected $trace;

    public function __construct()
    {
        parent::__construct();
        $this->trace = App::getFacadeRoot()['trace'];
    }

    /**
     * Process the RTO pending orders
     *
     * @var string mode
     * @return mixed
     */
    public function process(string $mode = null)
    {
        $this->mode = $mode;

        parent::__construct($mode);

        parent::handle();
        $offset = 0;
        while (true)
        {
            try
            {
                $response = $this->uploadPendingOrdersToSQS($offset);
                if ($response === false)
                {
                    $this->trace->info(TraceCode::RTO_REVIEW_SQS_PUSH_REQUEST, ['message' => 'no more orders.']);
                    return;
                }
            }
            catch (\Exception $e)
            {
                $this->trace->info(TraceCode::RTO_REVIEW_ERROR_EXCEPTION, ["error" => $e->getMessage()]);
                return ;
            }

            //Adding delay between db calls
            usleep(self::PROCESSING_DELAY*1000);
            $offset+=500;
        }
    }

    /**
     *Fetch pending orders
     *Uploads to SQS
     **/
    public function uploadPendingOrdersToSQS(int $offset) :bool
    {
        $orders = $this->repoManager->order->fetchPendingOrders($offset,ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $orderArray = $orders->toArray();

        if(count($orderArray) == 0)
        {
            return false;
        }

        $this->trace->info(TraceCode::RTO_REVIEW_SQS_PUSH_START,[
            'message' => 'order push started',
            'offset' => $offset,
        ]);

        foreach ($orderArray as $order)
        {
            try
            {
                $value = [];
                $value[Order\Entity::ID] = 'order_'.$order[Order\Entity::ID];
                $value[Order\Entity::MERCHANT_ID] = $order[Order\Entity::MERCHANT_ID];
                $value[Order1cc\Constants::ACTION] = Order1cc\Constants::REVIEW_STATUS_ACTION_MAPPING[
                    $order[Order1cc\Fields::REVIEW_STATUS]
                ];
                $value[OneClickCheckout\Constants::PLATFORM] = $order[OneClickCheckout\Constants::PLATFORM];

                OneCCReviewCODOrder::dispatch(array_merge($value,
                        [
                            'mode' => $this->mode,
                        ])
                );

            }
            catch (\Exception $e)
            {
                $this->trace->error(
                    TraceCode::RTO_REVIEW_SQS_FAILED_COUNT, [
                        'error' => $e->getMessage(),
                        'order_id' => $order[Order\Entity::ID],
                    ]
                );
            }
        }

        return true;
    }
}

