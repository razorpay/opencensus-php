<?php

namespace RZP\Models\FundTransfer\Yesbank;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Base;
use RZP\Models\Settlement\Channel;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundTransfer\Base\Beneficiary\ApiProcessor;
use RZP\Models\FundTransfer\Yesbank\Request\Beneficiary as BeneficiaryRequest;

class Beneficiary extends ApiProcessor
{
    protected $channel = Channel::YESBANK;

    /**
     * Makes beneficiary addition request for the bank account ids provided
     * Slack notification will be sent as a summary
     *
     * @param PublicCollection $bankAccounts
     */
    public function process(PublicCollection $bankAccounts)
    {
        $this->count = $bankAccounts->count();

        $request = new BeneficiaryRequest;

        foreach ($bankAccounts as $bankAccount)
        {
            try
            {
                $request->init()
                        ->setEntity($bankAccount)
                        ->makeRequest();
            }
            catch (\Throwable $e)
            {
                $this->summary[] = $bankAccount->getId();

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::BENEFICIARY_REGISTRATION_FAILED,
                    [
                        'bank_account_id' => $bankAccount->getId(),
                        'error'           => $e->getMessage()
                    ]);
            }
        }

        $this->notify();
    }
}
