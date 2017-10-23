<?php

namespace RZP\Models\Qr;

use RZP\Base\Luhn;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Processor extends VirtualAccount\Processor
{
    const RANDOM_CARD_PADDING = '12345';

    /**
     * @param Entity $qr
     *
     * @return Entity|null
     */
    public function process($qr)
    {
        $isPaymentExpected = $this->isPaymentExpected($qr);

        $isDuplicateNotification = $this->checkIfDuplicateNotification($qr);

        if (($isPaymentExpected === true) and
            ($isDuplicateNotification === false))
        {
            $qr->setExpected(true);

            $this->setMerchant();
        }
        else if ($isPaymentExpected === false)
        {
            $this->preProcessUnexpectedPayment($qr);
        }
        else
        {
            $this->repo->saveOrFail($qr);

            return $qr;
        }

        $this->processQr($qr);

        $this->trace->info(
                TraceCode::QR_PAYMENT_PROCESSING_SUCCESSFUL,
                $qr->toArray());

        return $qr;
    }

     /**
     * Throwaway VAs for unexpected payments
     *
     * @param int $amount
     *
     * @return array
     */
    protected function virtualAccountCreationArray(int $amount): array
    {
        return [
            VirtualAccount\Entity::AMOUNT_EXPECTED => $amount,
            VirtualAccount\Entity::RECEIVER_TYPES  => [Receiver::BHARAT_QR]
        ];
    }

    protected function checkIfDuplicateNotification(Entity $qr)
    {
        $merchantReference = $qr->getMerchantReference();

        $qrEntity  = $this->repo->qr->findByMerchantReference($merchantReference);

        if ($qrEntity === null)
        {
            return false;
        }

        return true;
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

            if ($qr->isExpected() === true)
            {
                $paymentProcessor->autoCapturePayment($payment);
            }
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

        try
        {
            $bharatQr = $this->repo->bharat_qr->findByPublicId($bharatQrId);
        }
        catch (\Exception $e)
        {
            return null;
        }

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->getActiveVirtualAccountFromBharatQrId($bharatQr->getId());

        return $virtualAccount;
    }

    /**
     *@todo Need a better way to handle this
     */
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

        $paymentArray[Payment\Entity::AMOUNT]      = $qr->getAmount();
        $paymentArray[Payment\Entity::DESCRIPTION] = "";


        // @todo find a better method to do this. This is done in order to bypass validation
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
