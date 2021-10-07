<?php

namespace RZP\Models\QrPayment;

use RZP\Base\Luhn;
use RZP\Models\Card;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\VirtualAccount;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Currency\Currency;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BharatQr\GatewayResponseParams;
use RZP\Models\QrCode\NonVirtualAccountQrCode;
use RZP\Models\QrPayment\UnexpectedPaymentReason;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Processor extends Base\Core
{
    const RANDOM_CARD_PADDING = '00000';

    protected $gatewayInput;

    protected $callbackData;

    protected $terminal;

    protected $qrCode;

    /**
     * @var PaymentProcessor
     */
    protected $paymentProcessor;

    /**
     * Processor constructor.
     *
     * @param $gatewayResponse
     * @param $terminal
     */
    public function __construct($gatewayResponse, $terminal)
    {
        parent::__construct();

        $this->gatewayInput = $gatewayResponse['qr_data'];

        $this->callbackData = $gatewayResponse['callback_data'];

        $this->terminal = $terminal;
    }

    public function process(Entity $qrPayment)
    {
        // 1. check if payment is duplicate
        $this->isDuplicatePayment($qrPayment);

        // 2. check if payment is expected and set qr code or create shared qr
        $this->checkPaymentExpectedAndSetQrCode($qrPayment);

        // 3. set merchant based on qr code (shared or not)
        $this->merchant = $this->qrCode->merchant;

        // 4. process payment
        try
        {
            $qrPayment = $this->processPayment($qrPayment);
        }
        catch(\Exception $ex)
        {
            if (UnexpectedPaymentReason::shouldCreateUnexpectedPayment($ex->getMessage()) === true)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::QR_CODE_FAILED_PAYMENT_REROUTED_TO_SHARED
                );

                $qrPayment->setExpected(false);

                $qrPayment->setUnexpectedReason($ex->getMessage());

                return $this->createUnexpectedPayment($qrPayment);
            }

            throw $ex;
        }

        $this->trace->info(TraceCode::QR_CODE_V2_PAYMENT_SUCCESSFUL, $qrPayment->toArrayTrace());

        return $qrPayment;
    }

    protected function processPayment(Entity $qrPayment)
    {
        $paymentProcessor = $this->getPaymentProcessor();

        $this->repo->transaction(
            function() use ($qrPayment, $paymentProcessor) {
                $paymentInput = $this->getPaymentArray($qrPayment);

                // This is being done because we want
                // to skip terminal selection on payment
                // creation and use this terminal instead
                // as the payment has already gone through
                // this terminal.
                $this->callbackData[Payment\Entity::TERMINAL_ID] = $this->getTerminal()->getId();

                $this->createPayment($paymentInput, $this->callbackData);

                $payment = $paymentProcessor->getPayment();

                $qrPayment->payment()->associate($payment);

                $qrPayment->qrCode()->associate($this->qrCode);

                if ($qrPayment->isBankTransfer())
                {
                    $this->createAndAssociatePayerBankAccount($this->callbackData, $qrPayment);
                }

                $this->repo->saveOrFail($qrPayment);

                $this->repo->saveOrFail($payment);

                $this->updateQrCode($qrPayment);

                return $payment;
            });

        $this->refundOrCapturePayment($qrPayment);

        return $qrPayment;
    }


    protected function refundOrCapturePayment(Base\PublicEntity $entity)
    {
        $paymentProcessor = $this->getPaymentProcessor();

        if ($entity->isExpected() === true)
        {
            if ($entity->payment->hasBeenCaptured() === false)
            {
                $paymentProcessor->autoCapturePayment($paymentProcessor->getPayment());
            }
        }
        else
        {
            $paymentProcessor->refundAuthorizedPayment($paymentProcessor->getPayment());
        }

        $this->repo->qr_payment->syncToEs($entity, EsRepository::UPDATE);
    }

    protected function createPayment(array $input, array $gatewayData = [])
    {
        try
        {
            $this->getPaymentProcessor()->process($input, $gatewayData);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::INFO, TraceCode::QR_CODE_PAYMENT_FAILED,
                                         ['input' => $input]);
            throw $e;
        }
    }

    protected function createUnexpectedPayment(Entity $qrPayment)
    {
        $this->qrCode = (new NonVirtualAccountQrCode\Core)->createOrFetchSharedQrCode();

        $qrPayment->qrCode()->associate($this->qrCode);

        $this->merchant = $this->qrCode->merchant;

        $this->paymentProcessor = new Payment\Processor\Processor($this->merchant);

        return $this->processPayment($qrPayment);
    }

    protected function getTerminal()
    {
        return $this->terminal;
    }

    protected function getPaymentArray(Entity $qrPayment): array
    {
        $parentPaymentArray = $this->getDefaultPaymentArray();

        $paymentArray = [
            Payment\Entity::CURRENCY    => Currency::INR,
            Payment\Entity::METHOD      => $qrPayment->getMethod(),
            Payment\Entity::AMOUNT      => $qrPayment->getAmount(),
            Payment\Entity::DESCRIPTION => 'QRv2 Payment',
            Payment\Entity::NOTES       => $this->qrCode->getNotes()->toArray(),
        ];

        $paymentArray = array_merge($paymentArray, $parentPaymentArray);

        // TODO: find a better method to do this. This is done in order to bypass validation
        if ($this->gatewayInput[Entity::METHOD] === Payment\Method::CARD)
        {
            $paymentArray['card'] = $this->getDummyCardDetails();
        }
        else
        {
            if ($this->gatewayInput[Entity::METHOD] === Payment\Method::UPI)
            {
                $paymentArray['vpa'] = $this->gatewayInput[GatewayResponseParams::VPA];
            }
        }

        return $paymentArray;
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

        $finalCardNumber = $part1 . $checksum . $part2;

        return $finalCardNumber;
    }

    protected function getDefaultPaymentArray(): array
    {
        $paymentArray = $this->getReceiverPaymentArray();

        if ($this->qrCode->hasCustomer() === true)
        {
            $customer = $this->qrCode->customer;

            $paymentArray[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();
            $paymentArray[Payment\Entity::CONTACT]     = $customer->getContact();
            $paymentArray[Payment\Entity::EMAIL]       = $customer->getEmail();
        }

        return $paymentArray;
    }

    protected function getReceiverPaymentArray(): array
    {
        $receiver = $this->qrCode;

        return [
            Payment\Entity::RECEIVER => [
                'id'   => $receiver->getPublicId(),
                'type' => $receiver->getEntity(),
            ],
        ];
    }

    protected function getPaymentProcessor(bool $forceCreate = false): PaymentProcessor
    {
        if ((isset($this->paymentProcessor) === false) or
            ($forceCreate === true))
        {
            $this->paymentProcessor = new PaymentProcessor($this->merchant);
        }

        return $this->paymentProcessor;
    }

    private function checkPaymentExpectedAndSetQrCode($qrPayment)
    {
        $this->setQrCode($qrPayment);

        $qrPayment->setExpected(false);

        if ($this->qrCode === null)
        {
            $this->qrCode = (new NonVirtualAccountQrCode\Core)->createOrFetchSharedQrCode();

            $qrPayment->setUnexpectedReason(UnexpectedPaymentReason::QR_PAYMENT_QR_NOT_FOUND);

            return;
        }

        if ($this->qrCode->isClosed())
        {
            $qrPayment->setUnexpectedReason(UnexpectedPaymentReason::QR_PAYMENT_ON_CLOSED_QR_CODE);

            return;
        }

        if (($this->qrCode->hasFixedAmount() === true) and
            ($qrPayment->getAmount() !== $this->qrCode->getAmount()))
        {
            $qrPayment->setUnexpectedReason(UnexpectedPaymentReason::QR_PAYMENT_AMOUNT_MISMATCH);

            return;
        }

        $qrPayment->setExpected(true);
    }

    /**
     * @param Entity $qrPayment
     *
     * @return |null
     */
    protected function setQrCode(Entity $qrPayment)
    {
        $merchantReference = $qrPayment->getMerchantReference();

        $this->qrCode = (new NonVirtualAccountQrCode\Repository())->find($merchantReference);
    }

    public function isDuplicatePayment(Entity $qrPayment)
    {
        $providerReferenceId = $this->gatewayInput[Entity::PROVIDER_REFERENCE_ID];

        $qrPaymentEntity = $this->repo->qr_payment->findByProviderReferenceIdAndGatewayAndAmount($providerReferenceId,
                                                                                                $qrPayment->getGateway(),
                                                                                                $qrPayment->getAmount());

        if ($qrPaymentEntity === null)
        {
            return;
        }

        $this->trace->info(TraceCode::QR_PAYMENT_DUPLICATE_NOTIFICATION, $qrPayment->toArrayTrace());

        throw new LogicException(TraceCode::QR_PAYMENT_DUPLICATE_NOTIFICATION);
    }

    private function updateQrCode(Entity $qrPayment)
    {
        if ($qrPayment->isExpected() === true)
        {
            $this->qrCode->incrementTotalPaymentCount();

            $this->qrCode->incrementPaymentAmountReceived($qrPayment->getAmount());

            if ($this->qrCode->getUsageType() === 'single_use')
            {
                (new NonVirtualAccountQrCode\Core())->close($this->qrCode, NonVirtualAccountQrCode\CloseReason::PAID);
            }
        }
    }

    private function createAndAssociatePayerBankAccount($callbackArray, Entity $qrPayment)
    {
        $bankAccount = new BankAccount\Entity;

        $bankAccountInput = $this->computeBankAccountInput($callbackArray, $qrPayment);

        $bankAccount->build($bankAccountInput, 'addVirtualBankAccount');

        $bankAccount->merchant()->associate($qrPayment->qrCode->merchant);

        $bankAccount->source()->associate($qrPayment->qrCode);

        $qrPayment->payerBankAccount()->associate($bankAccount);

        $this->repo->saveOrFail($bankAccount);
    }

    private function computeBankAccountInput($callbackArray, $qrPayment)
    {
        $ifsc = self::getMappedIfsc($callbackArray, $qrPayment);

        return [
            BankAccount\Entity::IFSC_CODE        => $ifsc,
            BankAccount\Entity::ACCOUNT_NUMBER   => self::computeBankAccountNumber($callbackArray, $ifsc),
            BankAccount\Entity::BENEFICIARY_NAME => self::getLabel($qrPayment, $callbackArray)
        ];
    }

    private function computeBankAccountNumber($callbackArray, $ifsc)
    {
        $account = preg_replace('/[^a-zA-Z0-9]+/', '', $callbackArray['payer_account']);

        $account = BankCodes::modifyPayerAccountIfNeeded($account, $ifsc);

        return $account;
    }

    protected static function getLabel(Entity $qrPayment, $callbackArray)
    {
        $label = $callbackArray['payer_name'];

        $label = preg_replace('/[^a-zA-Z0-9 ]+/', '', $label);

        // Label could be empty AFTER the preg_replace step
        if (empty(trim($label)) === true)
        {
            if ($qrPayment->isExpected() === true)
            {
                $label = $qrPayment->merchant->getBillingLabel();

                // Still necessary to sanitize merchant name
                $label = preg_replace('/[^a-zA-Z0-9 ]+/', '', $label);
            }
            else
            {
                $label = 'Beneficiary';
            }
        }

        $label = trim($label);

        return substr($label, 0, 39);
    }

    public static function getMappedIfsc($callbackArray, $qrPayment)
    {
        $ifsc = $callbackArray['payer_ifsc'];
        $mode = $callbackArray['mode'];
        $gateway = $qrPayment->getGateway();

        if ((strlen($ifsc) !== BankAccount\Entity::IFSC_CODE_LENGTH) and
            ($mode === \RZP\Models\BankTransfer\Mode::IMPS))
        {
            if ($gateway === VirtualAccount\Provider::KOTAK)
            {
                /**
                 *  In can of Kotak, we get Bank Code followed by 10 digit Mobile number.
                 *  Bank Codes vary from 3 digits to 5 digits
                 *  but we are only taking first 3 digits into consideration.
                 */
                $impsBankCode = substr($ifsc, 0, 3);

                $ifsc = BankCodes::getIfscForImpsBankCode($impsBankCode);

                if($ifsc === null)
                {
                    \Razorpay\Trace\Facades\Trace::info(TraceCode::BANK_TRANSFER_BANK_CODE_MISSING, ['bank_code' => $impsBankCode]);
                }
            }
            else if ($gateway === VirtualAccount\Provider::YESBANK)
            {
                $nbin = $ifsc;

                $ifsc = BankCodes::getIfscForNbin($nbin);

                if($ifsc === null)
                {
                    Trace::info(TraceCode::BANK_TRANSFER_NBIN_CODE_MISSING, ['nbin' => $nbin]);
                }
            }
        }
        return $ifsc;
    }
}
