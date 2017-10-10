<?php

namespace RZP\Models\Qr;

use RZP\Base\Luhn;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Processor extends VirtualAccount\Processor
{
    const RANDOM_CARD_PADDING = '12345';

    /**
     * @param Entity $bankTransfer
     *
     * @return Entity|null
     */
    public function process($qr)
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

            $payment->setGatewayViaQr(Payment\Gateway::BHARAT_QR);

            $qr->virtualAccount()->associate($this->virtualAccount);

            $this->repo->saveOrFail($qr);

            $this->updateVirtualAccount($qr);

            $paymentProcessor->autoCapturePayment($payment);
        });
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

    protected function getVirtualAccountFromEntity($qr)
    {
        $bharatQrId = $qr->getMerchantReference();

        $bharatQr = $this->repo->bharat_qr->findByPublicId($bharatQrId);

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->getActiveVirtualAccountFromBharatQrId($bharatQr->getId());

        return $virtualAccount;
    }

    protected function getLuhnValidCardNumberFromQr(Entity $qr)
    {
        $maskedCardNumber = $qr->getCardNumber();

        $firstSix = substr($maskedCardNumber, 0, 6);

        $lastFour = substr($maskedCardNumber, 12, 4);

        $part1 = $firstSix . self::RANDOM_CARD_PADDING;

        $part2 = $lastFour;

        $checksum = Luhn::computeCheckDigitWithPart($part1, $part2);

        $finalCardNumber =  $firstSix . self::RANDOM_CARD_PADDING . $checksum . $lastFour ;

        return $finalCardNumber;
    }

    protected function qrPaymentArray(Entity $qr): array
    {
        $paymentArray[Payment\Entity::CURRENCY] = Currency::INR;
        $paymentArray[Payment\Entity::METHOD]   = $qr->getMethod();

        //TODO :: Need to check amount format for hitachi side
        $paymentArray[Payment\Entity::AMOUNT]      = ($qr->getAmount()) * 100;
        $paymentArray[Payment\Entity::DESCRIPTION] = "";

        $paymentArray['card']['number'] = $this->getLuhnValidCardNumberFromQr($qr);

        $paymentArray['card']['cvv'] = '123';

        $paymentArray['card']['name'] = 'random';

        $paymentArray['card']['expiry_month'] = '11';

        $paymentArray['card']['expiry_year'] = '2037';

        if ($this->virtualAccount->hasCustomer() === true)
        {
            $customer = $this->virtualAccount->customer;

            $paymentArray[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();
            $paymentArray[Payment\Entity::CONTACT]     = $customer->getContact();
            $paymentArray[Payment\Entity::EMAIL]       = $customer->getEmail();
        }

        return $paymentArray;
    }
}
