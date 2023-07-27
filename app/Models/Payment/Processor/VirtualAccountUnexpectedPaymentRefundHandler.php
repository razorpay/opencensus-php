<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\RazorxTreatment;

trait VirtualAccountUnexpectedPaymentRefundHandler
{
    public static $demoAccountIds = [
        Merchant\Account::DEMO_PAGE_ACCOUNT,
        Merchant\Account::DEMO_ACCOUNT,
        Merchant\Account::TEST_ACCOUNT,
    ];

    public function handleVAUnExpectedPaymentRefundInCallback($payment)
    {
        if ((empty($payment) === true) or
            (in_array($payment->getMerchantId(), self::$demoAccounts) === false) or
            ($payment->isBankTransfer() === false) or
            (empty($payment->getRefundAt()) === true))
        {
            return;
        }

        $razorxResult = $this->app['razorx']->getTreatment($payment->getId(),
            RazorxTreatment::UNEXPECTED_VA_PAYMENT_REFUND_DELAY,
            $this->app['rzp.mode']);

        if ($razorxResult !== 'on')
        {
            return;
        }

        $payment->setRefundAt(null);

        $this->repo->saveOrFail($payment);

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_UNEXPECTED_PAYMENT_REFUND_BLOCK, $payment);

        return true;
    }

    public function handleVAUnExpectedPaymentRefundInRecon($payment)
    {
        if ((in_array($payment->getMerchantId(), self::$demoAccountIds) === false) or
            ($payment->isBankTransfer() === false))
        {
            return;
        }

        if (empty($payment->getRefundAt()) === true)
        {
            $this->setRefundAtToNow($payment);

            return;
        }

    }
}
