<?php

namespace RZP\Models\QrPayment;

use App;
use Carbon\Carbon;
use RZP\Base\Luhn;
use RZP\Models\Card;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use Illuminate\Support\Str;
use RZP\Models\VirtualAccount;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Currency\Currency;
use RZP\Models\Feature\Constants;
use RZP\Exception\LogicException;
use RZP\Gateway\Upi\Icici\Fields;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\QrCode\Repository as QrRepo;
use RZP\Base\Database\DetectsLostConnections;
use RZP\Models\BharatQr\GatewayResponseParams;
use RZP\Models\QrCode\NonVirtualAccountQrCode;
use RZP\Models\QrPayment\Constants as QrConstants;
use RZP\Models\QrCodeConfig\Keys as QrCodeConfigKeys;
use RZP\Models\QrCodeConfig\Repository as QrConfigRepo;
use RZP\Models\Payment\Processor\UpiUnexpectedPaymentRefundHandler;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;
use RZP\Models\Checkout\Order as CheckoutOrder;

class Processor extends Base\Core
{
    use DetectsLostConnections;
    use UpiUnexpectedPaymentRefundHandler;

    const RANDOM_CARD_PADDING = '00000';

    protected $gatewayInput;

    protected $callbackData;

    protected $terminal;

    /** @var NonVirtualAccountQrCode\Entity */
    protected $qrCode;

    /** @var CheckoutOrder\Entity */
    protected $checkoutOrder;

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

        if ($this->qrCode->isCheckoutQrCode()) {
            // 3.1 Set Merchant in auth as it would be NULL in callback flow
            $this->app['basicauth']->setMerchant($this->merchant);
            // 3.2 Fetch Key for this Merchant. It gets used in forming
            //     signature for payment authorize response
            $key = $this->repo->key->getLatestActiveKeyForMerchant($this->merchant->getId());
            // 3.3 Set Key Entity in AuthCreds
            $this->app['basicauth']->authCreds->setKeyEntity($key);
        }

        // 4. process payment
        try
        {
            $qrPayment = $this->processPayment($qrPayment);
        }
        catch(\Exception $ex) {
            $this->trace->traceException($ex, Trace::INFO, TraceCode::QR_PAYMENT_PROCESSING_FAILED,
                                         [
                                             'error_message' => $ex->getMessage(),
                                             'rrn'           => $qrPayment->getProviderReferenceId()
                                         ]);

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

            $qrPayment = $this->retryProcessPayment($ex, $qrPayment);
        }

        $this->trace->info(TraceCode::QR_CODE_V2_PAYMENT_SUCCESSFUL, $qrPayment->toArrayTrace());

        return $qrPayment;
    }

    protected function processPayment(Entity $qrPayment)
    {
        $paymentProcessor = $this->getPaymentProcessor();

        $isUpiQrV1Hdfc = $this->merchant->isFeatureEnabled(Constants::UPIQR_V1_HDFC);

        $payment = null;

        $paymentInput = [];

        $shouldCreateQrPayment = ($isUpiQrV1Hdfc === false) and (isset($this->gatewayInput['payment_id']) === false);
        if ($shouldCreateQrPayment === true)
        {
            $paymentInput = $this->getPaymentArray($qrPayment);

            // This is being done because we want
            // to skip terminal selection on payment
            // creation and use this terminal instead
            // as the payment has already gone through
            // this terminal.
            $this->callbackData[Payment\Entity::TERMINAL_ID] = $this->getTerminal()->getId();
        }
        else
        {
            try
            {
                $payment = $this->repo->payment->findOrFail($this->gatewayInput['payment_id']);
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException($exception);
            }
        }

        // Adding the order id mutex for solving multiple captured payment on same order
        // If qr payment has order id then resource will contain order id else ProviderReferenceIdProviderReferenceId
        $orderMutex = 'callback_order_id_' . $qrPayment->getProviderReferenceId();

        if (isset($paymentInput[Payment\Entity::ORDER_ID]) === true)
        {
            $orderId = Order\Entity::silentlyStripSign($paymentInput[Payment\Entity::ORDER_ID]);

            $orderMutex =  'callback_order_id_' . $orderId;
        }

        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutex->acquireAndRelease($orderMutex,
            function() use ($qrPayment, $paymentProcessor, $paymentInput, $payment, $shouldCreateQrPayment)
            {
                $this->repo->transaction(
                    function () use ($qrPayment, $paymentProcessor, $paymentInput, $payment, $shouldCreateQrPayment) {

                        if ($shouldCreateQrPayment === true) {
                            $this->createPayment($paymentInput, $this->callbackData);

                            $payment = $paymentProcessor->getPayment();

                            $this->trace->info(TraceCode::QR_CODE_PAYMENT_PROCESSED,
                                [
                                    'payment' => $payment->getId(),
                                    'rrn' => $qrPayment->getProviderReferenceId()
                                ]);
                        }

                        $qrPayment->payment()->associate($payment);

                        $qrPayment->qrCode()->associate($this->qrCode);

                        if ($qrPayment->isBankTransfer()) {
                            $this->createAndAssociatePayerBankAccount($this->callbackData, $qrPayment);
                        }

                        $this->repo->saveOrFail($qrPayment);

                        $this->trace->info(TraceCode::QR_PAYMENT_SAVED,
                            [
                                'qr_id' => $this->qrCode->getId(),
                                'payment' => $payment->getId(),
                                'rrn' => $qrPayment->getProviderReferenceId()
                            ]);


                        $this->repo->saveOrFail($payment);

                        $this->trace->info(TraceCode::PAYMENT_ENTITY_UPDATE,
                            [
                                'qr_id' => $this->qrCode->getId(),
                                'payment' => $payment->getId(),
                                'rrn' => $qrPayment->getProviderReferenceId()
                            ]);

                        $this->updateQrCode($qrPayment);

                        return $payment;
                    }, 3);
            });

        if (
            $qrPayment->qrCode->isCheckoutQrCode() &&
            $qrPayment->isExpected()
        ) {
            (new CheckoutOrder\Core())->markCheckoutOrderPaid($this->checkoutOrder);

            (new Service())->setQrCodeStatusAndPaymentIdInCache(
                $qrPayment->qrCode,
                $qrPayment->payment
            );
        }

        $this->refundOrCapturePayment($qrPayment);

        return $qrPayment;
    }


    protected function refundOrCapturePayment(Base\PublicEntity $entity)
    {
        $paymentProcessor = $this->getPaymentProcessor();

        if($this->merchant->isFeatureEnabled(Constants::UPIQR_V1_HDFC) === true)
        {
            $payment = null;

            try
            {
                $payment = $this->repo->payment->findOrFail($entity->payment_id);
            }
            catch (\Throwable $exception){}
        }
        else
        {
            $payment = $paymentProcessor->getPayment();
        }

        if ($entity->isExpected() === true)
        {
            if ((! $this->qrCode->isCheckoutQrCode()) &&
                ($entity->payment->hasBeenCaptured() === false))
            {

                // Adding the order id mutex for solving multiple captured payment on same order
                // If payment has order id then resource will contain order id else payment id
                $orderMutex = $paymentProcessor->getCallbackOrderMutexResource($payment);

                $mutex = App::getFacadeRoot()['api.mutex'];

                $mutex->acquireAndRelease($orderMutex,
                    function() use ($paymentProcessor, $payment)
                    {
                        $paymentProcessor->autoCapturePayment($payment);
                    });
            }
        }
        else
        {
            $blockUnexpectedRefund = $this->handleUnExpectedPaymentRefundInCallback($payment, true);

            if ($blockUnexpectedRefund === false)
            {
                $attributeArray = $entity->attributesToArray();

                $refundNotes = [
                    'notes' => [
                        'refund_reason' =>  $attributeArray['unexpected_reason']
                    ]
                ];

                // based on experiment, refund request will be routed to Scrooge
                $paymentProcessor->refundAuthorizedPayment($payment, $refundNotes);
            }
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
                                         [
                                             'input' => $this->removePiiForLogging($input, ['card', 'vpa'])
                                         ]);
            throw $e;
        }
    }

    public function removePiiForLogging(array $array, array $fields = [])
    {
        foreach ($fields as $field)
        {
            if (isset($array[$field]) === false)
            {
                continue;
            }
            unset($array[$field]);
        }

        return $array;
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

        if ($this->qrCode->getRequestSource() === NonVirtualAccountQrCode\RequestSource::EZETAP)
        {
            $paymentArray[Payment\Entity::NOTES] = array_merge($paymentArray[Payment\Entity::NOTES],
                [QrConstants::PAYMENT_TYPE_KEY => QrConstants::PAYMENT_TYPE_OFFLINE]);
        }

        $paymentArray = array_merge($paymentArray, $parentPaymentArray);

        // set payer account type, if present.
        if (array_key_exists(GatewayResponseParams::PAYER_ACCOUNT_TYPE, $this->gatewayInput) === true)
        {
            $paymentArray[Payment\Entity::PAYER_ACCOUNT_TYPE] = $this->gatewayInput[GatewayResponseParams::PAYER_ACCOUNT_TYPE];
        }

        if ($this->qrCode->hasOrder() === true)
        {
            $order = $this->qrCode->source;

            $paymentArray[Payment\Entity::ORDER_ID] = $order->getPublicId();
        }

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

        if ($this->checkoutOrder !== null && $qrPayment->isExpected())
        {
            $paymentArrayFromCheckoutOrder = (new CheckoutOrder\Core())->getPaymentArrayFromCheckoutOrder($this->checkoutOrder);

            $paymentArray = array_merge($paymentArray, $paymentArrayFromCheckoutOrder);

            if ($this->checkoutOrder->isOfferApplied()) {
                // Reset the payment amount to order amount if an offer is applied
                // as payment create process does offer related calculation &
                // reduces the amount accordingly.
                $paymentArray[Payment\Entity::AMOUNT] = $this->checkoutOrder->getAmount();
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

    public function checkIfCutoffTimeIsExceeded($qrPayment)
    {
        //The time format we get from the input is YYYYMMDDHHMMSS (datetime format),
        //the time format we need in the code is epoch, the method strtotime converts datetime format to epoch
        if (array_key_exists(Fields::TXN_COMPLETION_DATE, $this->callbackData) == true)
        {
            $transactionTime = strtotime($this->callbackData[Fields::TXN_COMPLETION_DATE]);
        }
        elseif (array_key_exists(Fields::TXN_INIT_DATE, $this->callbackData) == true)
        {
            $transactionTime = strtotime($this->callbackData[Fields::TXN_INIT_DATE]);
        }
        else
        {
            return false;
        }

        $currentTimeStamp = Carbon::now()->getTimestamp();

        $cutOffConfig = (new QrConfigRepo())->findQrCodeConfigsByMerchantIdAndKey($this->qrCode->merchant->getId(),
                                                                                  QrCodeConfigKeys::CUT_OFF_TIME);

        if (($cutOffConfig == null) or ($cutOffConfig->getValue() == null))
        {
            return false;
        }

        if ($currentTimeStamp - $transactionTime > $cutOffConfig->getValue())
        {
            return true;
        }

        return false;
    }

    public function checkIfExperimentEnabled($qrPayment)
    {
        $merchantId = $this->qrCode->merchant->getId();

        $variant = $this->app->razorx->getTreatment($merchantId, RazorxTreatment::QR_CODE_CUTOFF_CONFIG, $this->mode);

        if ($variant !== 'on')
        {
            return false;
        }

        return true;
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

        if ($this->qrCode->isCheckoutQrCode())
        {
            $this->setCheckoutOrder();

            if ($this->checkoutOrder === null)
            {
                $qrPayment->setUnexpectedReason(UnexpectedPaymentReason::CHECKOUT_ORDER_NOT_PRESENT);

                return;
            }

            if ($this->checkoutOrder->isClosed())
            {
                $qrPayment->setUnexpectedReason(UnexpectedPaymentReason::CHECKOUT_ORDER_CLOSED);

                return;
            }
        }

        if ($this->qrCode->isClosed())
        {
            $isPaymentExpected = false;

            if ($this->checkIfExperimentEnabledForExpiry($this->qrCode) === true and
                $qrPayment->getTransactionTime() !== null)
            {

                if (($this->qrCode->getClosedAt() !== null) and
                    ($this->qrCode->getClosedAt() > $qrPayment->getTransactionTime()))
                {
                    $isPaymentExpected = true;
                }

                if (($this->qrCode->getUsageType() === NonVirtualAccountQrCode\UsageType::SINGLE_USE) and
                    ($this->qrCode->getPaymentsCountReceived() > 0))
                {
                    $isPaymentExpected = false;
                }
            }
            if ($isPaymentExpected === false)
            {
                $qrPayment->setUnexpectedReason(UnexpectedPaymentReason::QR_PAYMENT_ON_CLOSED_QR_CODE);
                return;
            }
        }


        if (($this->qrCode->hasFixedAmount() === true) and
            ($qrPayment->getAmount() !== $this->qrCode->getAmount()))
        {
            $qrPayment->setUnexpectedReason(UnexpectedPaymentReason::QR_PAYMENT_AMOUNT_MISMATCH);

            return;
        }

        if (($this->checkIfExperimentEnabled($qrPayment) == true)
            and ($this->checkIfCutoffTimeIsExceeded($qrPayment) == true))
        {
            $qrPayment->setUnexpectedReason(UnexpectedPaymentReason::QR_CODE_CUTOFF_TIME_EXCEEDED);

            return;
        }

        $qrPayment->setExpected(true);
    }

    public function checkIfExperimentEnabledForExpiry($qrCode)
    {
        $variant = $this->app['razorx']->getTreatment($qrCode->getMerchantId(),
                                                      RazorxTreatment::QR_PAYMENT_AUTO_CAPTURE_FOR_CLOSED_QR,
                                                      $this->mode
        );
        if (strtolower($variant) === RazorxTreatment::RAZORX_VARIANT_ON)
        {
            return true;
        }

        return false;
    }

    /**
     * @param Entity $qrPayment
     *
     * @return |null
     */
    protected function setQrCode(Entity $qrPayment)
    {
        $merchantReference = $qrPayment->getMerchantReference();

        if (strlen($merchantReference)>14 and starts_with($merchantReference,'STQ') === true )
        {
            $this->qrCode = (new QrRepo)->findByMerchantReference(substr($merchantReference,3,14));
        }
    else
        {
            $this->qrCode = (new NonVirtualAccountQrCode\Repository())->find($merchantReference);
        }


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
            $this->trace->info(TraceCode::QR_CODE_PAYMENT_INFO_UPDATE,
                               [
                                   'qr_payment' => $qrPayment->getId(),
                                   'qr_id'      => $this->qrCode->getId(),
                                   'rrn'        => $qrPayment->getProviderReferenceId()
                               ]);

            $this->qrCode->incrementTotalPaymentCount();

            $this->qrCode->incrementPaymentAmountReceived($qrPayment->getAmount());

            if ($this->qrCode->getUsageType() === 'single_use')
            {
                (new NonVirtualAccountQrCode\Core())->close($this->qrCode, NonVirtualAccountQrCode\CloseReason::PAID);
            }

            $this->trace->info(TraceCode::QR_CODE_PAYMENT_INFO_UPDATE_COMPLETE,
                               [
                                   'qr_payment' => $qrPayment->getId(),
                                   'qr_id'      => $this->qrCode->getId(),
                                   'rrn'        => $qrPayment->getProviderReferenceId()
                               ]);
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
            BankAccount\Entity::ACCOUNT_NUMBER   => self::computeBankAccountNumber($callbackArray),
            BankAccount\Entity::BENEFICIARY_NAME => self::getLabel($qrPayment, $callbackArray)
        ];
    }

    private function computeBankAccountNumber($callbackArray)
    {
        $account = preg_replace('/[^a-zA-Z0-9]+/', '', $callbackArray['payer_account']);

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

    protected function setCheckoutOrder(): void
    {
        $checkoutOrderId = $this->qrCode->getEntityId();

        $this->checkoutOrder = (new CheckoutOrder\Repository())->find($checkoutOrderId);
    }

    /**
     * This function will retry creating payments which failed due to DB connection issues.
     *
     * @param \Exception $ex
     * @param Entity     $qrPayment
     *
     * @return Entity
     * @throws \Exception
     */
    protected function retryProcessPayment(\Exception $ex, Entity $qrPayment)
    {
        $this->trace->info(TraceCode::QR_CODE_PAYMENT_RETRY_CHECK,
                           [
                               'qr_payment' => $qrPayment->getId()
                           ]);

        $variant = $this->app->razorx->getTreatment($this->qrCode->merchant->getId(),
                                                    RazorxTreatment::QR_PAYMENT_PROCESS_RETRY,
                                                    $this->mode);

        if (strtolower($variant) !== RazorxTreatment::RAZORX_VARIANT_ON)
        {
            throw $ex;
        }

        if ($this->causedByLostConnection($ex) === false)
        {
            throw $ex;
        }

        $payment = null;

        // This check is added for the case where payment entity is created and authorised
        // but the process after it failed due to any reason.
        // For these cases, we should not create another payment as this will lead to duplicate payment creation
        if ($qrPayment->getPaymentId() !== null)
        {
            $payment = $this->repo->payment->find($qrPayment->getPaymentId());
        }

        if ($payment !== null)
        {
            throw $ex;
        }
        
        $this->trace->info(TraceCode::QR_CODE_PAYMENT_RETRY_STARTED,
                           [
                               'qr_payment' => $qrPayment->getId()
                           ]);

        return $this->processPayment($qrPayment);
    }
}
