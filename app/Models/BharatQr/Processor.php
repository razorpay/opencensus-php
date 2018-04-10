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

    protected $receiver;
    public function __construct( array $gatewayInput, string $provider = null)
    {
        parent::__construct($provider);

        $this->gatewayInput = $gatewayInput['qr_data'];

        $this->callbackData = $gatewayInput['gateway_input'];
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

    /**
     * A static qr code for all the unexpected payments is picked.
     *
     * @param int $amount
     */
    protected function createAndSetVirtualAccount(int $amount)
    {
        $virtualAccountId = Constants::SHARED_VIRTUAL_ACCOUNT;

        $this->virtualAccount = $this->repo->virtual_account->find($virtualAccountId);

        if ($this->virtualAccount === null)
        {
            $this->virtualAccount = $this->createSharedVirtualAccount();
        }

        $this->receiver = $this->virtualAccount->qrCode;
    }

    protected function createSharedVirtualAccount()
    {
        $customers = $this->repo->customer->fetchByMerchantId($this->merchant->getId());

        $input = [
            VirtualAccount\Entity::RECEIVERS => [
                VirtualAccount\Entity::TYPES => ['qr_code']
            ],
            'shared' => true,
        ];

        return (new VirtualAccount\Core)->create($input, $this->merchant, $customers[0]);
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

                            $payment = $this->repo
                                            ->payment
                                            ->findByPublicId($res['razorpay_payment_id']);

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

        $terminal = $this->repo->terminal->getByGatewayMerchantId($gatewayMerchantId, $gateway);

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'Terminal should not be null here',
                null,
                ['gateway_merchant_id' => $gatewayMerchantId]);
        }

        $this->callbackData['razorpay_terminal_id'] = $terminal->getId();
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

        (new QrCode)->stripSignWithoutValidation($qrCodeId);

        $qrCode = $this->repo->qr_code->find($qrCodeId);

        if ($qrCode === null)
        {
            return null;
        }

        $this->receiver = $qrCode;

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
            'id'   => $this->receiver->getId(),
            'type' => 'qr_code',
        ];

        $paymentArray[Payment\Entity::RECEIVER] = $receiverData;

        return $paymentArray;
    }

    protected function getDummyCardDetails()
    {
        // TODO: Handle the null checks in card validation
        $card = [
            Card\Entity::NUMBER       => $this->getLuhnValidCardNumber(),
            Card\Entity::CVV          => Constants::CARD_CVV,
            Card\Entity::NAME         => Constants::CARD_NAME,
            Card\Entity::EXPIRY_MONTH => Constants::CARD_EXPIRY_MONTH,
            Card\Entity::EXPIRY_YEAR  => Constants::CARD_EXPIRY_YEAR,
        ];

        return $card;
    }
}
