<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;

use RZP\Models\Merchant;
use RZP\Models\Merchant\RazorxTreatment;

trait UpiUnexpectedPaymentRefundHandler
{
    public static $demoAccounts = [
        Merchant\Account::DEMO_PAGE_ACCOUNT,
        Merchant\Account::DEMO_ACCOUNT,
        Merchant\Account::TEST_ACCOUNT,
    ];

    public function handleUnExpectedPaymentRefundInCallback($payment, $isCallback)
    {
        if ((empty($payment) === true) or 
            ($payment->isUpi() === false) or 
             ($payment->isUpiOtm() === true) or
            (in_array($payment->getMerchantId(), self::$demoAccounts, false) === false) or 
            ($isCallback === false) or 
            (empty($payment->getRefundAt()) === true))
        {
            return;
        }

     
        /*
         * In case of unexpected payments created in callback we forcefuly set refundAt value to nil. This will be overridden in    
         * reconciliate 
         */

        $razorxResult = $this->app['razorx']->getTreatment($payment->getId(),
                                RazorxTreatment::UNEXPECTED_PAYMENT_REFUND_DELAY,
                                $this->app['rzp.mode']);

        if ($razorxResult !== 'on') 
        {
            return;
        }

        $payment->setRefundAt(null);

        $this->repo->saveOrFail($payment);
    }

    public function handleUnExpectedPaymentRefundInRecon($payment)
    {
        if ((in_array($payment->getMerchantId(), self::$demoAccounts, false) === false) or
            ($payment->isUpi() === false) or
            ($payment->isUpiOtm() === true) or
            (empty($payment->getRefundAt()) === false))
        {
            return;
        }

        $payment->setRefundAt(Carbon::now()->getTimestamp());

        $this->repo->saveOrFail($payment);
    }
}
