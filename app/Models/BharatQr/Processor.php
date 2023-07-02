<?php

namespace RZP\Models\BharatQr;

use RZP\Exception;
use RZP\Base\Luhn;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\VirtualAccount;
use RZP\Models\Merchant\Account;
use RZP\Models\Currency\Currency;
use RZP\Models\QrCode\Entity as QrCode;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Processor extends VirtualAccount\Processor
{
    const RANDOM_CARD_PADDING = '00000';

    protected $gatewayInput;

    protected $callbackData;

    protected $terminal;

    public function __construct(array $gatewayResponse, $terminal)
    {
        parent::__construct();

        $this->gatewayInput = $gatewayResponse['qr_data'];

        $this->callbackData = $gatewayResponse['callback_data'];

        $this->terminal = $terminal;
    }

    protected function isDuplicate(Base\PublicEntity $bharatQr)
    {
        $providerReferenceId = $this->gatewayInput[GatewayResponseParams::PROVIDER_REFERENCE_ID];

        $amount = $this->gatewayInput[GatewayResponseParams::AMOUNT];

        $bharatQrEntity = $this->repo->bharat_qr->findByProviderReferenceIdAndAmount($providerReferenceId, $amount);

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
        $paymentProcessor = $this->getPaymentProcessor();

        $this->repo->transaction(
            function() use ($bharatQr, $paymentProcessor)
            {
                $paymentInput = $this->getPaymentArray($bharatQr);

                //
                // This is being done because we want
                // to skip terminal selection on payment
                // creation and use this terminal instead
                // as the payment has already gone through
                // this terminal.
                //

                $this->callbackData[Payment\Entity::TERMINAL_ID] = $this->getTerminal()->getId();

                $this->createPayment($paymentInput, $this->callbackData);

                $payment = $paymentProcessor->getPayment();

                $bharatQr->payment()->associate($payment);

                $bharatQr->virtualAccount()->associate($this->virtualAccount);

                $this->repo->saveOrFail($bharatQr);

                //
                // @todo: remove this after validating.
                //
                $this->repo->saveOrFail($payment);

                $this->updateVirtualAccount($bharatQr);

                return $payment;
            });

        $this->refundOrCapturePayment($bharatQr);

        return $bharatQr;
    }

    protected function useSharedVirtualAccount(Base\PublicEntity $bharatQr): bool
    {
        if ($this->virtualAccount === null)
        {
            if ($this->getTerminal()->isExpected() === true)
            {
                // For expected BQR terminals, we create a new VA and set it
                $this->handleExpectedTerminal();

                return false;
            }

            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_UNEXPECTED_PAYMENT,
                [
                    'entity' => $bharatQr->toArray(),
                ]);

            return true;
        }

        $amountExpected = $this->virtualAccount->getAmountExpected();

        $amountReceived = $bharatQr->getAmount();

        // If amount expected is not null or 0
        if (empty($amountExpected) === false)
        {
            // If amount is not same as expected, we will refund the payment
            if ($amountExpected !== $amountReceived)
            {
                return true;
            }
        }

        return parent::useSharedVirtualAccount($bharatQr);
    }

    protected function handleExpectedTerminal()
    {
        $gateway = $this->gatewayInput[GatewayResponseParams::GATEWAY];

        //
        // In case of sharp gateway merchant is not
        // taken from terminal but from the auth itself
        // as the test payments are made on private auth
        //
        if ($gateway === Payment\Gateway::SHARP)
        {
            $terminalMerchant = $this->merchant;
        }
        else
        {
            $terminalMerchant = $this->terminal->merchant;
        }

        if ($terminalMerchant->getId() === Account::SHARED_ACCOUNT)
        {
            throw new Exception\LogicException(
                'Bharat Qr terminal merchant with expected true can not be shared',
                null,
                ['terminal_id' => $this->terminal->getId()]);
        }

        //
        // Here if there is no va but we received a payment and terminal
        // expected is set to true, we need to create a virtual account and
        // receiver with the reference received from bank.
        //
        $this->createAndSetVirtualAccount($terminalMerchant);
    }

    protected function createAndSetVirtualAccount(Merchant\Entity $merchant)
    {
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

        $this->virtualAccount = (new VirtualAccount\Core)->create($input, $merchant);
    }

    protected function getTerminal()
    {
        return $this->terminal;
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
            Payment\Entity::NOTES       => $this->virtualAccount->getNotes()->toArray(),
        ];

        $paymentArray = array_merge($paymentArray, $parentPaymentArray);

        if (isset($this->gatewayInput[GatewayResponseParams::PAYER_ACCOUNT_TYPE]) === true)
        {
            $paymentArray[Payment\Entity::PAYER_ACCOUNT_TYPE] = $this->gatewayInput[GatewayResponseParams::PAYER_ACCOUNT_TYPE];
        }

        // TODO: find a better method to do this. This is done in order to bypass validation
        if ($this->gatewayInput[Entity::METHOD] === Method::CARD)
        {
            $paymentArray['card'] = $this->getDummyCardDetails();
        }
        else if ($this->gatewayInput[Entity::METHOD] === Method::UPI)
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

        if (isset($this->gatewayInput[GatewayResponseParams::SENDER_NAME]) === true)
        {
            $senderName = $this->gatewayInput[GatewayResponseParams::SENDER_NAME];

            $cardName = preg_replace('/[^ \w]+/', '', $senderName);

            $card[Card\Entity::NAME] = $cardName ?: $card[Card\Entity::NAME];
        }

        return $card;
    }

    protected function getReceiver()
    {
        return $this->virtualAccount->qrCode;
    }

    protected function setMerchant()
    {
        parent::setMerchant();

        try
        {
            // We need to set merchant and key in basic auth as for the
            // response signature generation, in case of Offline QR payment
            $this->app['basicauth']->setMerchant($this->merchant);

            $key = $this->repo->key->getFirstActiveKeyForMerchant($this->merchant->getId());

            $this->app['basicauth']->authCreds->setKeyEntity($key);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }
    }
}
