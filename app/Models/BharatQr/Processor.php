<?php

namespace RZP\Models\BharatQr;

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
     * @param Entity $bharatQr
     *
     * @return Entity|null
     */
    public function process($bharatQr)
    {
        $isPaymentExpected = $this->isPaymentExpected($bharatQr);

        $isDuplicateNotification = $this->checkIfDuplicateNotification($bharatQr);

        if (($isPaymentExpected === true) and
            ($isDuplicateNotification === false))
        {
            $bharatQr->setExpected(true);

            $this->setMerchant();
        }
        else if ($isPaymentExpected === false)
        {
            $this->preProcessUnexpectedPayment($bharatQr);
        }
        else
        {
            $this->repo->saveOrFail($bharatQr);

            return $bharatQr;
        }

        $this->processBharatQr($bharatQr);

        $this->trace->info(
                TraceCode::BHARAT_QR_PAYMENT_PROCESSING_SUCCESSFUL,
                $bharatQr->toArray());

        return $bharatQr;
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
            VirtualAccount\Entity::RECEIVER_TYPES  => [Receiver::QR_CODE]
        ];
    }

    protected function checkIfDuplicateNotification(Entity $bharatQr)
    {
        $merchantReference = $bharatQr->getMerchantReference();

        $bharatQrEntity  = $this->repo->bharat_qr->findByMerchantReference($merchantReference);

        if ($bharatQrEntity === null)
        {
            return false;
        }

        return true;
    }

    protected function processBharatQr(Entity $bharatQr)
    {
        $paymentProcessor = new PaymentProcessor($this->merchant);

        $this->repo->transaction(function() use (
            $bharatQr,
            $paymentProcessor)
        {
            $paymentInput = $this->bharatQrPaymentArray($bharatQr);

            $res = $paymentProcessor->process($paymentInput);

            $payment = $this->repo
                            ->payment
                            ->findByPublicId($res['razorpay_payment_id']);

            $bharatQr->payment()->associate($payment);

            $payment->setGatewayViaQr(Payment\Gateway::BHARAT_QR);

            $bharatQr->virtualAccount()->associate($this->virtualAccount);

            $this->repo->saveOrFail($bharatQr);

            $this->updateVirtualAccount($bharatQr);

            if ($bharatQr->isExpected() === true)
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

    protected function getVirtualAccountFromEntity($bharatQr)
    {
        $qrCodeId = $bharatQr->getMerchantReference();

        try
        {
            $qrCode = $this->repo->qr_code->findByPublicId($qrCodeId);
        }
        catch (\Exception $e)
        {
            return null;
        }

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->getActiveVirtualAccountFromQrCodeId($qrCode->getId());

        return $virtualAccount;
    }

    /**
     *@todo Need a better way to handle this
     */
    protected function getLuhnValidCardNumberFromBharatQr(Entity $bharatQr)
    {
        $maskedCardNumber = $bharatQr->getCardNumber();

        $firstSix = substr($maskedCardNumber, 0, 6);

        $lastFour = substr($maskedCardNumber, 12, 4);

        $part1 = $firstSix . self::RANDOM_CARD_PADDING;

        $part2 = $lastFour;

        $checksum = Luhn::computeCheckDigitWithPart($part1, $part2);

        $finalCardNumber =  $firstSix . self::RANDOM_CARD_PADDING . $checksum . $lastFour ;

        return $finalCardNumber;
    }

    protected function bharatQrPaymentArray(Entity $bharatQr): array
    {
        $paymentArray[Payment\Entity::CURRENCY] = Currency::INR;
        $paymentArray[Payment\Entity::METHOD]   = $bharatQr->getMethod();

        $paymentArray[Payment\Entity::AMOUNT]      = $bharatQr->getAmount();
        $paymentArray[Payment\Entity::DESCRIPTION] = "";


        // @todo find a better method to do this. This is done in order to bypass validation
        $paymentArray['card']['number'] = $this->getLuhnValidCardNumberFromBharatQr($bharatQr);

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
