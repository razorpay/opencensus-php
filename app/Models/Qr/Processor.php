<?php

namespace RZP\Models\Qr;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Processor extends Base\Core
{
    protected $virtualAccount;
    protected $provider;
    protected $merchant;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

        // These flows are initiated by the provider bank hitting
        // our APIs. Provider banks are currently authenticated by
        // registering them as apps, and using AppAuth.
        $this->provider = $this->app['basicauth']->getInternalApp();
    }

    /**
     * @param Entity $bankTransfer
     *
     * @return Entity|null
     */
    public function process(Entity $qr)
    {
        $isPaymentExpected = $this->isPaymentExpected($qr);

        if ($isPaymentExpected === true)
        {
            $this->setMerchant();
        }
        else if ($isPaymentExpected === false)
        {
            //Will it ever happen? Need to confirm
        }
        else
        {
            //Need to check for duplicate API call here?

            $this->repo->saveOrFail($qr);

            return $qr;
        }

        $this->processQr($qr);

        $this->trace->info(
                TraceCode::QR_PAYMENT_PROCESSING_SUCCESSFUL,
                $qr->toArray());

        return $qr;
    }

    protected function processQr(Entity $qr)
    {
        $paymentProcessor = new PaymentProcessor($this->merchant);

        $this->repo->transaction(function() use (
            $qr,
            $paymentProcessor)
        {
            $paymentInput = $this->qrPaymentArray($qr);

            $res = $paymentProcessor->process($paymentInput);

            $payment = $this->repo
                            ->payment
                            ->findByPublicId($res['razorpay_payment_id']);

            $qr->payment()->associate($payment);

            $payment->setGateway(Payment\Gateway::BHARAT_QR);

            $qr->merchant()->associate($this->merchant);

            $qr->virtualAccount()->associate($this->virtualAccount);

            $this->createAndAssociateCard($qr);

            $this->repo->saveOrFail($qr);

            $this->updateVirtualAccount((int) 100 * $qr->getAmount());

            if ($bankTransfer->isExpected() === true)
            {
                $paymentProcessor->autoCapturePayment($payment);
            }
        });
    }


    protected function isPaymentExpected(Entity $qr): bool
    {
        $this->setVirtualAccount($qr);

        return true;
    }

    protected function setVirtualAccount(Entity $qr)
    {
        $this->virtualAccount = $this->getVirtualAccountFromQr($qr);
    }

    protected function setMerchant()
    {
        $this->merchant = $this->virtualAccount->merchant;
    }

    protected function updateVirtualAccountAmount($amount)
    {
        $this->virtualAccount->incrementAmountPaid($amount);

        $this->virtualAccount->incrementAmountReceived($amount);

        $this->repo->saveOrFail($this->virtualAccount);
    }

    protected function getVirtualAccountFromQr(Entity $qr)
    {
        $bharatQrId = $qr->getMerchantReference();

        $bharatQr = $this->repo->bharat_qr->findOrFailPublic($bharatQrId);

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->getActiveVirtualAccountFromBharatQrId($bharatQr->getId());

        return $virtualAccount;
    }

    protected function createAndAssociateCard(Entity $qr)
    {
        //Need to create card entity
    }

    protected function qrPaymentArray(Entity $qr): array
    {
        $paymentArray[Payment::CURRENCY] = Currency::INR;
        $paymentArray[Payment::METHOD]   = $qr->getMethod();

        //TODO :: Need to check amount format for hitachi side
        $paymentArray[Payment::AMOUNT]      = ($qr->getAmount()) * 100;
        $paymentArray[Payment::DESCRIPTION] = "";

        if ($this->virtualAccount->hasCustomer() === true)
        {
            $customer = $this->virtualAccount->customer;

            $paymentArray[Payment::CUSTOMER_ID] = $customer->getPublicId();
            $paymentArray[Payment::CONTACT]     = $customer->getContact();
            $paymentArray[Payment::EMAIL]       = $customer->getEmail();
        }

        return $paymentArray;
    }
}
