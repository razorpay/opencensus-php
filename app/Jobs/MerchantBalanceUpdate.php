<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Service as PaymentService;

class MerchantBalanceUpdate extends Job
{
    const RELEASE_WAIT_SECS    = 300;

    /**
     * @var string
     */
    protected $queueConfigKey = 'merchant_balance_update';

    /**
     * @var array
     */
    protected $input;


    public function __construct(array $input)
    {
        parent::__construct($input['mode']);

        $this->input = $input;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();
            $this->trace->info(
                TraceCode::MERCHANT_BALANCE_UPDATE_REQUEST,
                [
                    'input'       => $this->input,
                ]
            );

            $updated = (new PaymentService)->updateMerchantBalance($this->input['payment_id'], $this->input['transaction_id']);

            $this->trace->info(
                TraceCode::MERCHANT_BALANCE_UPDATE_SUCCESSFULL,[
                    'input'          => $this->input
                ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::MERCHANT_BALANCE_UPDATE_FAILURE,
                $this->input);

            $this->release(self::RELEASE_WAIT_SECS);
        }
    }
}
