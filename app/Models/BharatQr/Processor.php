<?php

namespace RZP\Models\BharatQr;

use RZP\Base\Luhn;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\QrCode\Entity as QrCode;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Processor extends VirtualAccount\Processor
{
    const RANDOM_CARD_PADDING = '12345';

    /**
     * Entry point for  BharatQr  process flow.
     * Check if the bharatQr was an expected one.
     * - BharatQr was expected?
     *   - Yes
     *     - unique merchant reference?
     *       - Yes
     *         - Process the payment towards the owner of the VA
     *       - No
     *         - Duplicate payment, save entity and ignore
     *   - No
     *     - Process payment toward demo merchant, auto-refund it later.
     *
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
            //
            // The transfer is an expected one, i.e. it is made to a valid account
            // but the merchant_reference is a duplicate, indicating that a payment is being processed
            // for a second time. In this case, we do not create anything but a
            // bharat_qr entity, marked as unexpected.
            //
            $bharatQr->setExpected(false);

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
            VirtualAccount\Entity::RECEIVER_TYPES  => [VirtualAccount\Receiver::QR_CODE]
        ];
    }

    protected function checkIfDuplicateNotification(Entity $bharatQr)
    {
        $providerReferenceId = $bharatQr->getProviderReferenceId();

        $bharatQrEntity = $this->repo->bharat_qr->findByProviderReferenceId($providerReferenceId);

        if ($bharatQrEntity === null)
        {
            return false;
        }

        $this->trace->info(
                TraceCode::BHARAT_QR_PAYMENT_DUPLICATE_NOTIFICATION,
                $bharatQr->toArray());

        return true;
    }

    protected function processBharatQr(Entity $bharatQr)
    {
        $paymentProcessor = new PaymentProcessor($this->merchant);

        $payment = $this->repo->transaction(function() use (
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

            return $payment;
        });

        if ($bharatQr->isExpected() === true)
        {
            $paymentProcessor->autoCapturePayment($payment);
        }
    }

    protected function setMerchant()
    {
        $this->merchant = $this->virtualAccount->merchant;
    }

    protected function getVirtualAccountFromEntity($bharatQr)
    {
        $qrCodeId = $bharatQr->getMerchantReference();

        (new QrCode)->stripSignWithoutValidation($qrCodeId);

        $qrCode = $this->repo->qr_code->find($qrCodeId);

        if ($qrCode === null)
        {
            return $qrCode;
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


        // TODO: find a better method to do this. This is done in order to bypass validation
        $paymentArray['card'] = $this->getDummyCardDetails($bharatQr);

        if ($this->virtualAccount->hasCustomer() === true)
        {
            $customer = $this->virtualAccount->customer;

            $paymentArray[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();
            $paymentArray[Payment\Entity::CONTACT]     = $customer->getContact();
            $paymentArray[Payment\Entity::EMAIL]       = $customer->getEmail();
        }

        return $paymentArray;
    }

    protected function getDummyCardDetails(Entity $bharatQr)
    {
        //TODO: Handle the null checks in card validation
        $card[Card\Entity::NUMBER] = $this->getLuhnValidCardNumberFromBharatQr($bharatQr);

        $card[Card\Entity::CVV] = '123';

        $card[Card\Entity::NAME] = 'Random';

        $card[Card\Entity::EXPIRY_MONTH] = '11';

        $card[Card\Entity::EXPIRY_YEAR] = '2037';

        return $card;
    }
}
