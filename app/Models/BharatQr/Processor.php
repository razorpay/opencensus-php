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
		$gateway = $this->gatewayInput[GatewayResponseParams::GATEWAY];

		if (isset($this->gatewayInput[GatewayResponseParams::GATEWAY_MERCHANT_ID]) === true)
		{
			$gatewayMerchantId = $this->gatewayInput[GatewayResponseParams::GATEWAY_MERCHANT_ID];

			$terminal = $this->repo->terminal->findByGatewayMerchantId($gatewayMerchantId, $gateway);
		}

		else
		{
			$gatewayMpan = $this->gatewayInput[GatewayResponseParams::MPAN];

			$terminal = $this->repo->terminal->findByGatewayMpan($gatewayMpan, $gateway);
		}

		if ($terminal === null)
		{
			throw new Exception\LogicException(
				'Terminal should not be null here',
				null,
				['gateway_merchant_id' => $gatewayMerchantId]);
		}

		$this->callbackData[Constants::RAZORPAY_TERMINAL_ID] = $terminal->getId();
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

		if (isset($this->gatewayInput[GatewayResponseParams::SENDER_NAME]) === true)
		{
			$cardHolderName = preg_replace("/[^ \w]+/", "",
				$this->gatewayInput[GatewayResponseParams::SENDER_NAME]);
		}

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
