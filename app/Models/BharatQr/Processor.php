<?php

namespace RZP\Models\BharatQr;

use RZP\Exception;
use RZP\Base\Luhn;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\VirtualAccount;
use RZP\Models\Currency\Currency;
use RZP\Models\QrCode\Entity as QrCode;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Processor extends VirtualAccount\Processor
{
    const RANDOM_CARD_PADDING = '00000';

    protected $gatewayInput;

    protected $callbackData;

    public function __construct(array $gatewayResponse, string $provider = null)
    {
        parent::__construct($provider);

        $this->gatewayInput = $gatewayResponse['qr_data'];

        $this->callbackData = $gatewayResponse['callback_data'];
    }

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
     * @param Base\PublicEntity|Entity $bharatQr
     *
     * @return Entity
     */
    public function process(Base\PublicEntity $bharatQr)
    {
        $isDuplicateNotification = $this->checkIfDuplicateNotification($bharatQr);

        if ($isDuplicateNotification === true)
        {
            // If we get a duplicate notification, just ignore.

            return null;
        }

        $isPaymentExpected = $this->isPaymentExpected($bharatQr);

        $bharatQr->setExpected($isPaymentExpected);

        $this->setMerchant($isPaymentExpected);

        if ($isPaymentExpected === false)
        {
            $this->createAndSetVirtualAccount($this->gatewayInput[GatewayResponseParams::AMOUNT]);
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
            VirtualAccount\Entity::RECEIVERS  => [
                VirtualAccount\Entity::TYPES => [
                    VirtualAccount\Receiver::QR_CODE,
                ],
            ]
        ];
    }

    protected function checkIfDuplicateNotification(Base\PublicEntity $bharatQr)
    {
        $providerReferenceId = $this->gatewayInput[GatewayResponseParams::PROVIDER_REFERENCE_ID];

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

    protected function processBharatQr(Base\PublicEntity $bharatQr)
    {
        $paymentProcessor = new PaymentProcessor($this->merchant);

        $payment = $this->repo->transaction(
                        function() use ($bharatQr, $paymentProcessor)
                        {
                            $paymentInput = $this->getBharatQrPaymentArray();

                            $this->setTerminalIdInCallback();

                            $res = $paymentProcessor->process($paymentInput, $this->callbackData);

                            $payment = $paymentProcessor->getPayment();

                            $bharatQr->payment()->associate($payment);

                            $payment->setGatewayForBharatQr($this->gatewayInput[GatewayResponseParams::GATEWAY]);

                            $bharatQr->virtualAccount()->associate($this->virtualAccount);

                            $this->repo->saveOrFail($bharatQr);

                            $this->repo->saveOrFail($payment);

                            $this->updateVirtualAccount($bharatQr);

                            return $payment;
                        });

        if ($bharatQr->isExpected() === true)
        {
            $paymentProcessor->autoCapturePayment($payment);
        }
    }

    protected function setTerminalIdInCallback()
    {
        $gatewayMerchantId = $this->gatewayInput[GatewayResponseParams::GATEWAY_MERCHANT_ID];

        $gateway = $this->gatewayInput[GatewayResponseParams::GATEWAY];

        $terminal = $this->repo->terminal->findByGatewayMerchantId($gatewayMerchantId, $gateway);

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'Terminal should not be null here',
                null,
                ['gateway_merchant_id' => $gatewayMerchantId]);
        }

        $this->callbackData[Constants::RAZORPAY_TERMINAL_ID] = $terminal->getId();
    }

    protected function setMerchant(bool $paymentExpected)
    {
        if ($paymentExpected === true)
        {
            $this->merchant = $this->virtualAccount->merchant;
        }
        else
        {
            $defaultMerchantId = self::getDefaultMerchantId();

            $this->merchant = $this->repo->merchant->findByPublicId($defaultMerchantId);
        }
    }

    protected function getVirtualAccountFromEntity(Base\PublicEntity $bharatQr)
    {
        $qrCodeId = $bharatQr->getMerchantReference();

        // Here we use stripSignWithoutValidation because
        // we don't want to throw exception in case it is
        // unknown id. It will be accepted as unexpected payment
        (new QrCode)->stripSignWithoutValidation($qrCodeId);

        $qrCode = $this->repo->qr_code->find($qrCodeId);

        if ($qrCode === null)
        {
            return null;
        }

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->getActiveVirtualAccountFromQrCodeId($qrCode->getId());

        return $virtualAccount;
    }

    /**
     * TODO: Need a better way to handle this
     *
     * @return string
     */
    protected function getLuhnValidCardNumber()
    {
        $firstSix = $this->gatewayInput[GatewayResponseParams::CARD_FIRST6];

        $lastFour = $this->gatewayInput[GatewayResponseParams::CARD_LAST4];

        $part1 = $firstSix . self::RANDOM_CARD_PADDING;

        $part2 = $lastFour;

        $checksum = Luhn::computeCheckDigitWithPart($part1, $part2);

        $finalCardNumber =  $part1 . $checksum . $part2;

        return $finalCardNumber;
    }

    protected function getBharatQrPaymentArray(): array
    {
        $paymentArray = [
            Payment\Entity::CURRENCY    => Currency::INR,
            Payment\Entity::METHOD      => $this->gatewayInput[GatewayResponseParams::METHOD],
            Payment\Entity::AMOUNT      => $this->gatewayInput[GatewayResponseParams::AMOUNT],
            Payment\Entity::DESCRIPTION => 'Bharat Qr Payment',
        ];

        // TODO: find a better method to do this. This is done in order to bypass validation
        if ($this->gatewayInput[Entity::METHOD] === Method::CARD)
        {
            $paymentArray['card'] = $this->getDummyCardDetails();
        }
        else
        {
            $paymentArray['vpa'] = $this->gatewayInput[GatewayResponseParams::VPA];
        }

        if ($this->virtualAccount->hasCustomer() === true)
        {
            $customer = $this->virtualAccount->customer;

            $paymentArray[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();
            $paymentArray[Payment\Entity::CONTACT]     = $customer->getContact();
            $paymentArray[Payment\Entity::EMAIL]       = $customer->getEmail();
        }

        $receiverData = [
            'id'   => $this->virtualAccount->qrCode->getPublicId(),
            'type' => VirtualAccount\Receiver::QR_CODE,
        ];

        $paymentArray[Payment\Entity::RECEIVER] = $receiverData;

        return $paymentArray;
    }

    protected function getDummyCardDetails()
    {
        $card = (new Card\Entity)->getDummyCardArray();

        $card[Card\Entity::NUMBER] = $this->getLuhnValidCardNumber();

        return $card;
    }
}
