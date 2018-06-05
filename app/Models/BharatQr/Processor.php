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

    protected $terminal;

    public function __construct(array $gatewayResponse, string $provider = null)
    {
        parent::__construct($provider);

        $this->gatewayInput = $gatewayResponse['qr_data'];

        $this->callbackData = $gatewayResponse['callback_data'];
    }

    protected function isDuplicate(Base\PublicEntity $bharatQr)
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

    protected function processPayment(Base\PublicEntity $bharatQr)
    {
        $paymentProcessor = new PaymentProcessor($this->merchant);

        $payment = $this->repo->transaction(
                        function() use ($bharatQr, $paymentProcessor)
                        {
                            $paymentInput = $this->getPaymentArray($bharatQr);

                            // This is being done because we want
                            // to skip terminal selection on payment
                            // creation and use this terminal instead
                            // as the payment has already gone through
                            // this terminal.
                            $this->callbackData[Constants::RAZORPAY_TERMINAL_ID] = $this->getTerminal()->getId();

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

    /**
     * A receiver is expected if there exists an active VA
     * to receive it or if the terminal expected is set to
     * true. If such a VA does not exist, or exists but
     * has been closed/paid and terminal expected is also set to
     * false the payment is to be refunded.
     *
     * @param Base\PublicEntity $entity This is the receiver entity:
     *                                  qr_code
     *
     * @return bool
     */
    protected function checkPaymentExpectedAndSetVirtualAccount(Base\PublicEntity $entity): bool
    {
        $this->setVirtualAccount($entity);

        if ($this->virtualAccount === null)
        {
            if ($this->getTerminal()->isExpected() === false)
            {
                $this->trace->info(
                    TraceCode::VIRTUAL_ACCOUNT_UNEXPECTED_PAYMENT,
                    [
                        'entity' => $entity->toArray(),
                    ]);

                $this->virtualAccount = (new VirtualAccount\Core)->createOrFetchSharedVirtualAccount();

                return false;
            }
            else
            {
                // Here if there is no va but we received a payment and terminal
                // expected is set to true, we need to create a virtual account and
                // receiver with the reference received from bank.
                $input = [
                    VirtualAccount\Entity::RECEIVERS => [
                        VirtualAccount\Entity::TYPES => [
                            VirtualAccount\Receiver::QR_CODE,
                        ],
                        VirtualAccount\Receiver::QR_CODE => [
                            QrCode::REFERENCE => $this->gatewayInput[GatewayResponseParams::MERCHANT_REFERENCE]
                        ]
                    ],
                ];

                $this->virtualAccount = (new VirtualAccount\Core)->create($input, $this->terminal->merchant);

                return true;
            }
        }

        return true;
    }

    protected function getTerminal()
    {
        if ($this->terminal !== null)
        {
            return $this->terminal;
        }

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

        $this->terminal = $terminal;

        return $terminal;
    }


    protected function getVirtualAccountFromEntity(Base\PublicEntity $bharatQr)
    {
        $merchantReference = $bharatQr->getMerchantReference();

        // Here we use stripSignWithoutValidation because
        // we don't want to throw exception in case it is
        // unknown id. It will be accepted as unexpected payment
        (new QrCode)->stripSignWithoutValidation($merchantReference);

        $qrCode = $this->repo->qr_code->findByMerchantReference($merchantReference);

        if ($qrCode === null)
        {
            return null;
        }

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->getActiveVirtualAccountFromQrCodeId($qrCode->getId());

        return $virtualAccount;
    }

    protected function getPaymentArray(Base\PublicEntity $bharatQr): array
    {
        $parentPaymentArray = $this->getDefaultPaymentArray();

        $paymentArray = [
            Payment\Entity::CURRENCY    => Currency::INR,
            Payment\Entity::METHOD      => $bharatQr->getMethod(),
            Payment\Entity::AMOUNT      => $bharatQr->getAmount(),
            Payment\Entity::DESCRIPTION => 'Bharat Qr Payment',
        ];

        $paymentArray = array_merge($paymentArray, $parentPaymentArray);

        // TODO: find a better method to do this. This is done in order to bypass validation
        if ($this->gatewayInput[Entity::METHOD] === Method::CARD)
        {
            $paymentArray['card'] = $this->getDummyCardDetails();
        }
        else
        {
            $paymentArray['vpa'] = $this->gatewayInput[GatewayResponseParams::VPA];
        }

        return $paymentArray;
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

    protected function getDummyCardDetails()
    {
        $card = (new Card\Entity)->getDummyCardArray();

        $card[Card\Entity::NUMBER] = $this->getLuhnValidCardNumber();

        $cardHolderName = preg_replace("/[^ \w]+/", "", $this->gatewayInput[GatewayResponseParams::SENDER_NAME]);

        if (empty($cardHolderName) === false)
        {
            $card[Card\Entity::NAME] = $cardHolderName;
        }

        return $card;
    }

    protected function getReceiver()
    {
        return $this->virtualAccount->qrCode;
    }
}
