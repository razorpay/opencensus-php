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
    const RANDOM_CARD_PADDING = '00000';

    protected $gatewayInput;

    public function __construct( array $gatewayInput, string $provider = null)
    {
        parent::__construct($provider);

        $this->gatewayInput = $gatewayInput;
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
        parent::process($bharatQr);

        $this->processReceiver($bharatQr);

        $this->trace->info(
                TraceCode::BHARAT_QR_PAYMENT_PROCESSING_SUCCESSFUL,
                $bharatQr->toArray());

        return $bharatQr;
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

    protected function processReceiver(Base\PublicEntity $bharatQr)
    {
        $paymentProcessor = new PaymentProcessor($this->merchant);

        $payment = $this->repo->transaction(
                        function() use ($bharatQr, $paymentProcessor)
                        {
                            $paymentInput = $this->getPaymentArray($bharatQr);

                            $paymentProcessor->process($paymentInput);

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

    protected function getVirtualAccountFromEntity(Base\PublicEntity $bharatQr)
    {
        $qrCodeId = $bharatQr->getMerchantReference();

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

    protected function getPaymentArray(Base\PublicEntity $bharatQr): array
    {
        $paymentArray = [
            Payment\Entity::CURRENCY    => Currency::INR,
            Payment\Entity::METHOD      => $bharatQr->getMethod(),
            Payment\Entity::AMOUNT      => $bharatQr->getAmount(),
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

        return $this->getFinalPaymentArray($paymentArray);
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
        //
        // TODO: Card processor should be able to accept
        // null CVV and null expiry month and year
        //

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
