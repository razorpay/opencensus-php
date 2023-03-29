<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;

use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Models\Admin;
use RZP\Models\Admin\ConfigKey;

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
            return false;
        }

     
        /*
         * In case of unexpected payments created in callback we forcefuly set refundAt value to nil. This will be overridden in    
         * reconciliate 
         */
        $result = $this->shouldDelayUnexpectedPaymentRefund();
      
        if ($result === false)
        {
            return false;
        }

        $payment->setRefundAt(null);

        $this->repo->saveOrFail($payment);

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_UNEXPECTED_PAYMENT_REFUND_BLOCK, $payment);

        return true;
    }

    public function handleUnExpectedPaymentRefundInRecon($payment)
    {
        if ((in_array($payment->getMerchantId(), self::$demoAccounts, false) === false) or
            ($payment->isUpi() === false) or
            ($payment->isUpiOtm() === true))
        {
            return;
        }

        if (empty($payment->getRefundAt()) === true)
        {
            $this->setRefundAtToNow($payment);

            return;
        }

        /* if the refund at is set for an unexpected payment we are delaying to 5 days since recon is happening via art and api systems there could be a 
         * collision that might lead to double refunds, hence this temporary step. In case of regular payments, status would be refunded before recon.
         */

        if (($this->shouldDelayUnexpectedPaymentRefund() === true) and 
            ($payment->isAuthorized() === true))
        {
            $this->delayUnexpectedPaymentRefund($payment);    
        }
    }

    // if the unexpected payment is created in callback refund at will be null, when the payment is reconcilied 
    // we can safely refund the payment immediately.
    protected function setRefundAtToNow($payment)
    {
        $payment->setRefundAt(Carbon::now()->getTimestamp());

        $this->repo->saveOrFail($payment);

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_UNEXPECTED_PAYMENT_REFUND_UNBLOCK, $payment);
    }

    protected function delayUnexpectedPaymentRefund($payment)
    {
        $payment->setRefundAt($this->getDelayedRefundAtValue($payment->getCreatedAt()));

        $this->repo->saveOrFail($payment);

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_UNEXPECTED_PAYMENT_REFUND_DELAY, $payment);
    }

    public function shouldDelayUnexpectedPaymentRefund()
    {
        return (bool) Admin\ConfigKey::get(Admin\ConfigKey::UNEXPECTED_PAYMENT_DELAY_REFUND, false);
    }

    public function getDelayedRefundAtValue($createdAt)
    {
        return Carbon::createFromTimestamp($createdAt)->addDays(1)->getTimestamp();
    }
}
