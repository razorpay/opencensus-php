<?php

namespace RZP\Models\VirtualAccount;

use App;
use Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Models\Payment;
use RZP\Diag\EventCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\BankTransfer;
use RZP\Models\VirtualAccount;
use RZP\Exception\LogicException;
use RZP\Models\FundAccount\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Models\BankTransfer\HdfcEcms\StatusCode;
use RZP\Models\BankTransfer\Entity as BankTransferEntity;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

abstract class Processor extends Base\Core
{
    /**
     * @var Entity
     */
    protected $virtualAccount;

    /**
     * @var Validator
     */
    protected $validator;

    protected $receiver;

    /**
     * @var PaymentProcessor
     */
    protected $paymentProcessor;

    const VIRTUAL_ACCOUNT_NOT_FOUND = 'VIRTUAL_ACCOUNT_NOT_FOUND';
    const VIRTUAL_ACCOUNT_DUE_TO_BE_CLOSED = 'VIRTUAL_ACCOUNT_DUE_TO_BE_CLOSED';
    const VIRTUAL_ACCOUNT_MERCHANT_NOT_LIVE = 'VIRTUAL_ACCOUNT_MERCHANT_NOT_LIVE';
    const VIRTUAL_ACCOUNT_PAYMENT_TPV_FAILED = 'VIRTUAL_ACCOUNT_PAYMENT_TPV_FAILED';
    const VIRTUAL_ACCOUNT_PAYMENT_AMOUNT_DOES_NOT_MATCH_ORDER_AMOUNT = 'VIRTUAL_ACCOUNT_PAYMENT_AMOUNT_DOES_NOT_MATCH_ORDER_AMOUNT';

    const REQUEST_FROM   = 'request_from';
    const REQUEST_SOURCE = 'request_source';

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;
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

    /**
     * Entry point for  virtual account  process flow.
     * Check if the payment was a duplicate.
     * - payment was duplicate?
     *   - Yes
     *    - Do nothing.
     *   - No
     *    - payment is expected?
     *     - Yes
     *      - Set VA to the recognized VA
     *     - No
     *      - Set VA to demo merchant's static VA
     *
     * @param Base\PublicEntity $entity
     *
     * @return Base\PublicEntity
     */
    public function process(Base\PublicEntity $entity)
    {
        if ($this->isDuplicate($entity) === true)
        {
            //
            // The payment is an expected one, i.e. it is made to a valid account
            // but the UTR is a duplicate, indicating that a payment is being processed
            // for a second time. In this case, we do nothing.
            //

            switch ($entity->getEntityName())
            {
                case Constants\Entity::BANK_TRANSFER:
                    throw new LogicException(TraceCode::BANK_TRANSFER_PROCESS_DUPLICATE_UTR);

                case Constants\Entity::UPI_TRANSFER:
                    throw new LogicException(TraceCode::UPI_TRANSFER_PAYMENT_DUPLICATE_NOTIFICATION);

                case Constants\Entity::BHARAT_QR:
                    throw new LogicException(TraceCode::BHARAT_QR_PAYMENT_DUPLICATE_NOTIFICATION);

                default:
                    return null;
            }
        }

        $paymentExpected = $this->checkPaymentExpectedAndSetVirtualAccount($entity);

        $entity->setExpected($paymentExpected);

        if (($entity->getEntityName() === Constants\Entity::BANK_TRANSFER) and
            ($entity->getGateway() === Provider::HDFC_ECMS) and
            ($entity->getUnexpectedReason() !== null))
        {
            return $entity;
        }

        $this->setMerchant();

        $entity = $this->processPayment($entity);

        // This will be null in case of
        // bank transfer payments if the payment
        // is made to reserved account
        if ($entity === null)
        {
            return null;
        }

        if (($entity->getEntityName() === Constants\Entity::BANK_TRANSFER) and
            empty($entity->getRequestSource()) === false)
        {
            $requestSource = json_decode($entity->getRequestSource(), true);

            $source = $requestSource[BankTransfer\Entity::SOURCE];

            $requestFrom = $requestSource[BankTransferEntity::REQUEST_FROM];

            $logData = array_merge($entity->toArrayTrace(), [
                BankTransferEntity::REQUEST_SOURCE => $source,
                BankTransferEntity::REQUEST_FROM   => $requestFrom,
            ]);
        }
        else
        {
            $logData = $entity->toArrayTrace();
        }

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_PAYMENT_SUCCESSFUL,
            $logData
        );

        return $entity;
    }

    abstract protected function isDuplicate(Base\PublicEntity $entity);

    abstract protected function processPayment(Base\PublicEntity $entity);

    abstract protected function getVirtualAccountFromEntity(Base\PublicEntity $entity);

    abstract protected function getReceiver();

    protected function shouldRefundOrderPayment(Base\PublicEntity $entity)
    {
        if ($entity->virtualAccount->hasOrder() === false)
        {
            return false;
        }

        // If Virtual Account has an Order but Bank Transfer/BharatQR Payment
        // doesn't have an order then this is probably because
        // Validations on Order are failing and Payment is created
        // without Order to refund that while further processing.
        if ($entity->payment->hasOrder() === false)
        {
            return true;
        }

        return false;
    }

    protected function refundOrCapturePayment(Base\PublicEntity $entity)
    {
        try
        {
            // For business banking flow there exists no payment, hence no refund/capture.
            if ($this->virtualAccount->isBalanceTypeBanking() === true)
            {
                return;
            }

            $paymentProcessor = $this->getPaymentProcessor();

            if ($entity->isExpected() === true)
            {
                if ($this->shouldRefundOrderPayment($entity) === true)
                {
                    $refundNotes = [
                        'notes' => [
                            'refund_reason' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH
                        ]
                    ];

                    // based on experiment, refund request will be routed to Scrooge
                    $paymentProcessor->refundAuthorizedPayment($paymentProcessor->getPayment(), $refundNotes);
                }
                else if ($this->verifyPayerUsingTpv($entity) === false)
                {
                    $refundNotes = [
                        'notes' => [
                            'refund_reason' => 'Bank Account Validation Failed'
                        ]
                    ];

                    // based on experiment, refund request will be routed to Scrooge
                    $paymentProcessor->refundAuthorizedPayment($paymentProcessor->getPayment(), $refundNotes);
                }
                else if ($entity->payment->hasBeenCaptured() === false)
                {
                    $paymentProcessor->autoCapturePayment($paymentProcessor->getPayment());
                }
            }
            else
            {
                if (($this->isVirtualAccountDueToBeClosed($entity) === true) or
                    ($this->virtualAccount->isClosed() === true))
                {
                    $refundNotes = [
                        'notes' => [
                            'refund_reason' => PublicErrorDescription::BAD_REQUEST_VIRTUAL_ACCOUNT_CLOSED
                        ]
                    ];

                    $paymentProcessor->refundAuthorizedPayment($paymentProcessor->getPayment(), $refundNotes);
                }
            }
        }
        catch(\Exception $ex)
        {
            throw new \Exception(TraceCode::REFUND_OR_CAPTURE_PAYMENT_FAILED);
        }
    }

    protected function createPaymentWithoutOrder(array $input, array $gatewayData = [])
    {
        if (isset($input[Payment\Entity::ORDER_ID]) === true)
        {
            $paymentInput = array_except($input, [Payment\Entity::ORDER_ID]);
        }

        $this->getPaymentProcessor()->process($paymentInput, $gatewayData);
    }

    protected function createPayment(array $input, array $gatewayData = [])
    {
        try
        {
            $this->getPaymentProcessor()->process($input, $gatewayData);
        }
        catch (\Exception $e)
        {
            /*
             * Exception might have been because of Validation Failure on Order.
             * In this case we will make the payment without Order and refund
             * it in later flow.
             */
            if (isset($input[Payment\Entity::ORDER_ID]) === false)
            {
                throw $e;
            }

            $this->trace->traceException($e, Trace::INFO,
                TraceCode::VIRTUAL_ACCOUNT_FAILED_FOR_ORDER, ['input' => $input]);

            if ($e->getMessage() === PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH)
            {
                $this->pushVaPaymentFailedDueToOrderAmountMismatchEventToLake($input, $e);
            }

            $this->createPaymentWithoutOrder($input, $gatewayData);
        }
    }

    protected function getDefaultPaymentArray(): array
    {
        $paymentArray = $this->getReceiverPaymentArray();

        if ($this->virtualAccount->hasCustomer() === true)
        {
            $customer = $this->virtualAccount->customer;

            $paymentArray[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();
            $paymentArray[Payment\Entity::CONTACT]     = $customer->getContact();
            $paymentArray[Payment\Entity::EMAIL]       = $customer->getEmail();
        }

        if ($this->virtualAccount->hasOrder() === true)
        {
            $order = $this->virtualAccount->entity;

            $paymentArray[Payment\Entity::ORDER_ID] = $order->getPublicId();
        }

        return $paymentArray;
    }

    protected function getReceiverPaymentArray(): array
    {
        $receiver = $this->getReceiver();

        $paymentArray = [
            Payment\Entity::RECEIVER => [
                'id'   => $receiver->getPublicId(),
                'type' => $receiver->getEntity(),
            ],
        ];

        return $paymentArray;
    }

    /**
     * Set the VA for future processing.
     *
     * @param Base\PublicEntity $entity
     */
    protected function setVirtualAccount(Base\PublicEntity $entity)
    {
        $this->virtualAccount = $this->getVirtualAccountFromEntity($entity);
    }

    /**
     * Post-processing, VA amount fields are to be updated.
     * Status change is done inside incrementAmountPaid.
     *
     * @param Base\PublicEntity $entity
     */
    protected function updateVirtualAccount(Base\PublicEntity $entity)
    {
        $this->virtualAccount->incrementAmountPaid($entity->getAmount());

        $this->virtualAccount->incrementAmountReceived($entity->getAmount());

        $this->repo->saveOrFail($this->virtualAccount);
    }

    protected function setMerchant()
    {
        $this->merchant = $this->virtualAccount->merchant;
    }

    /**
     * A receiver is expected if there exists an active VA
     * to receive it. If such a VA does not exist, or exists but
     * has been closed/paid, the payment is to be refunded.
     *
     * @param Base\PublicEntity $entity This is the receiver entity:
     *                                  bank_transfer, qr_code
     *
     * @return bool
     */
    protected function checkPaymentExpectedAndSetVirtualAccount(Base\PublicEntity $entity): bool
    {
        $this->setVirtualAccount($entity);

        if ($this->virtualAccount !== null)
        {
            if ($this->virtualAccount->isClosed() === true)
            {
                $this->setUnexpectedReason($entity, TraceCode::VIRTUAL_ACCOUNT_CLOSED);
                $this->trace->info(
                    TraceCode::VIRTUAL_ACCOUNT_CLOSED,
                    $entity->toArray());
                return false;
            }

            if ($this->isVirtualAccountDueToBeClosed($entity) === true)
            {
                return false;
            }

        }

        if ($this->useSharedVirtualAccount($entity) === true)
        {
            if (($entity->getEntityName() === Constants\Entity::BANK_TRANSFER) and
                ($entity->getGateway() === Provider::HDFC_ECMS))
            {
                $this->trace->info(
                    TraceCode::VIRTUAL_ACCOUNT_ECMS_PAYMENT_CALLBACK_UNEXPECTED,
                    $entity->toArray()
                );

                return false;
            }

            $data = [];

            switch ($entity->getEntityName())
            {
                case Constants\Entity::BANK_TRANSFER:
                    {
                        $data = [
                            'utr'                               => $entity->getUtr(),
                            BankTransferEntity::REQUEST_SOURCE  => $entity->getRequestSource() ?? '',
                            BankTransferEntity::GATEWAY         => $entity->getGateway() ?? '',
                        ];
                    }
                    break;

                case Constants\Entity::UPI_TRANSFER:
                    {
                        $data = [
                            'npci_reference_id' => $entity->getRrn(),
                        ];
                    }
            }

            $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_PAYMENT_REROUTED_TO_SHARED, $data);

            if ($entity->getEntityName() === Constants\Entity::BANK_TRANSFER)
            {
                return $this->createOrFetchSharedVirtualAccountBasedOnBalanceType($entity);
            }

            return $this->createOrFetchSharedVirtualAccount();
        }

        return true;
    }

    private function createOrFetchSharedVirtualAccount()
    {
        $this->virtualAccount = (new VirtualAccount\Core)->createOrFetchSharedVirtualAccount();

        return false;
    }

    protected function isVirtualAccountDueToBeClosed (Base\PublicEntity $entity) : bool
    {
        if ($this->virtualAccount->isDueToBeClosed() === true)
        {
            if (($entity->getEntityName() === Constants\Entity::BANK_TRANSFER) and
                ($entity->getGateWay() === Provider::HDFC_ECMS))
            {
                $this->setUnexpectedReason($entity, StatusCode::CHALLAN_EXPIRED);

                $this->trace->info(
                    TraceCode::VIRTUAL_ACCOUNT_ECMS_DUE_TO_BE_CLOSED,
                    $entity->toArray());
            }
            else
            {
                $this->setUnexpectedReason($entity, self::VIRTUAL_ACCOUNT_DUE_TO_BE_CLOSED);

                $this->trace->info(
                    TraceCode::VIRTUAL_ACCOUNT_CLOSED_PAYMENT_REROUTED,
                    $entity->toArray());
            }

            $this->pushVaPaymentFailedDueToClosedVaEventToLake($entity);

            return true;
        }
       return false;
    }

    protected function useSharedVirtualAccount(Base\PublicEntity $entity): bool
    {
        /** @var Merchant\Entity $merchant */
        $merchant = $this->virtualAccount->merchant;

        if (($merchant->isLive() === false) and
            ($this->isLiveMode() === true))
        {
            $this->setUnexpectedReason($entity, self::VIRTUAL_ACCOUNT_MERCHANT_NOT_LIVE);

            return true;
        }

        if ($this->isVirtualAccountDueToBeClosed($entity) === true)
        {
            return true;
        }

        if (($entity->getEntityName() === Constants\Entity::BANK_TRANSFER) and
            ($entity->getGateway() === Provider::HDFC_ECMS))
        {
            if ($this->virtualAccount->getStatus() === Status::PAID)
            {
                $this->setUnexpectedReason($entity, StatusCode::DUPLICATE_TRANSACTION);

                $this->trace->info(
                    TraceCode::VIRTUAL_ACCOUNT_ECMS_DUPLICATE_TRANSACTION,
                    $entity->toArray());

                return true;
            }

            if ($this->virtualAccount->getAmountExpected() !== $entity->getAmount())
            {
                $this->setUnexpectedReason($entity, StatusCode::AMOUNT_MISMATCH);

                $this->trace->info(
                    TraceCode::VIRTUAL_ACCOUNT_ECMS_AMOUNT_MISMATCH,
                    $entity->toArray());

                return true;
            }
        }

        $isBusinessBankingVa = $this->virtualAccount->isBalanceTypeBanking();

        $merchantMethods = $merchant->getMethods();

        $method = $entity->getMethod();

        // Check for method being enabled for non-banking balance type VAs
        if (($isBusinessBankingVa === false) and
            ($merchantMethods->isMethodEnabled($method) === false))
        {
            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_METHOD_DISABLED_PAYMENT_REROUTED,
                $entity->toArray());

            return true;
        }

        return false;
    }

    protected function pushVaPaymentFailedDueToOrderAmountMismatchEventToLake(array $input, \Exception $ex)
    {
        $method = $input[Payment\Entity::METHOD];

        $receiverType = $input[Payment\Entity::RECEIVER]['type'];

        $properties = [
            'error'                     => self::VIRTUAL_ACCOUNT_PAYMENT_AMOUNT_DOES_NOT_MATCH_ORDER_AMOUNT,
            Payment\Entity::RECEIVER    => $input[Payment\Entity::RECEIVER],
            Payment\Entity::ORDER_ID    => $input[Payment\Entity::ORDER_ID],
        ];

        if (($method === Payment\Method::BANK_TRANSFER) and
            ($receiverType === Receiver::BANK_ACCOUNT))
        {
            $this->app['diag']->trackBankTransferEvent(
                EventCode::BANK_TRANSFER_UNEXPECTED_PAYMENT,
                null,
                $ex,
                $properties
            );
        }
        else if (($method === Payment\Method::UPI) and
                 ($receiverType === Receiver::VPA))
        {
            $this->app['diag']->trackUpiTransferEvent(
                EventCode::UPI_TRANSFER_UNEXPECTED_PAYMENT,
                null,
                $ex,
                $properties
            );
        }
    }

    protected function pushVaPaymentFailedDueToClosedVaEventToLake($entity)
    {
        if ($entity->getEntityName() === Constants\Entity::BANK_TRANSFER)
        {
            $this->app['diag']->trackBankTransferEvent(
                EventCode::BANK_TRANSFER_UNEXPECTED_PAYMENT,
                $entity,
                null,
                ['error' => self::VIRTUAL_ACCOUNT_NOT_FOUND]
            );
        }
        else if ($entity->getEntityName() === Constants\Entity::UPI_TRANSFER)
        {
            $this->app['diag']->trackUpiTransferEvent(
                EventCode::UPI_TRANSFER_UNEXPECTED_PAYMENT,
                $entity,
                null,
                ['error' => self::VIRTUAL_ACCOUNT_NOT_FOUND]
            );
        }
    }

    protected function setUnexpectedReason(Base\PublicEntity $entity, string $unexpectedReason)
    {
        if (($entity->getEntityName() === Constants\Entity::BANK_TRANSFER) or
            ($entity->getEntityName() === Constants\Entity::UPI_TRANSFER))
        {
            $entity->setUnexpectedReason($unexpectedReason);
        }
    }

    protected function verifyPayerUsingTpv(Base\PublicEntity $entity) : bool
    {
        $allowedPayers = $this->virtualAccount->virtualAccountTpv()->get();

        if ($allowedPayers->count() === 0)
        {
            return true;
        }

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_PAYMENT_PAYER_VALIDATION_INITIATED,
            [
                'id' => $entity->getPublicId(),
            ]
        );

        switch ($entity->getEntityName())
        {
            case Constants\Entity::BANK_TRANSFER:
                $payerBankAccount = $entity->payerBankAccount;

                $payerDetails = [
                    BankAccount\Entity::IFSC            => substr($payerBankAccount->getIfscCode(), 0, 4),
                    BankAccount\Entity::ACCOUNT_NUMBER  => $payerBankAccount->getAccountNumber(),
                ];

                break;

            case Constants\Entity::UPI_TRANSFER:
                $payerDetails = [
                    BankAccount\Entity::IFSC            => substr($entity->getPayerIfsc(), 0, 4),
                    BankAccount\Entity::ACCOUNT_NUMBER  => $entity->getPayerAccount(),
                ];

                break;

            default:
                return true;
        }

        foreach ($allowedPayers as $allowedPayer)
        {
            $allowedPayerDetails = $allowedPayer->entity->getVirtualAccountTpvData(true);

            $allowedPayerDetails['account_number'] = ltrim($allowedPayerDetails['account_number'],'0');
            $payerDetails['account_number']        = ltrim($payerDetails['account_number'],'0');

            if (empty(array_diff($payerDetails, $allowedPayerDetails)) === true)
            {
                return true;
            }
        }

        $entity->setUnexpectedReason(self::VIRTUAL_ACCOUNT_PAYMENT_TPV_FAILED);

        $this->repo->saveOrFail($entity);

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_PAYMENT_PAYER_VALIDATION_FAILED,
            [
                'id' => $entity->getPublicId(),
            ]
        );

        return false;
    }

    protected function createPaymentOrUnexpected(Base\PublicEntity $entity, array $input, array $gatewayData = [])
    {
        try
        {
            return $this->createPayment($input, $gatewayData);
        }
        catch (Exception $ex)
        {
            if (UnexpectedPaymentReason::shouldCreateUnexpectedPayment($ex->getMessage()) === true)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::VIRTUAL_ACCOUNT_FAILED_PAYMENT_REROUTED_TO_SHARED
                );

                $entity->setExpected(false);

                $entity->setUnexpectedReason($ex->getMessage());

                return $this->createUnexpectedPayment($entity, $gatewayData);
            }

            throw $ex;
        }
    }

    protected function createOrFetchSharedVirtualAccountBasedOnBalanceType($entity)
    {
        $payeeAccount = $entity->getPayeeAccount();

        $isBanking = $this->getTransferTypeBasedOnPayeeAccount($payeeAccount);

        if ($isBanking === true)
        {
            // Have kept the redis call inside the banking part only to avoid unnecessary failures in PG flow
            // due to redis connection issues happening for this config key.
            $refundViaX = (new Admin\Service)->getConfigKey(
                ['key' => Admin\ConfigKey::RX_FUND_LOADING_REFUNDS_VIA_X]
            );

            if ($refundViaX === true)
            {
                $this->virtualAccount = (new VirtualAccount\Core)->fetchSharedBankingVirtualAccount();

                $this->trace->info(
                    TraceCode::RX_FUND_LOADING_FOR_ACCOUNT_NOT_FOUND_TRIGGERED_TO_COMMON_MERCHANT,
                    [
                        'payee_account'      => $payeeAccount,
                        'virtual_account_id' => $this->virtualAccount->getId(),
                        'merchant_id'        => $this->virtualAccount->getMerchantId(),
                    ]
                );

                return true;
            }
        }

        return $this->createOrFetchSharedVirtualAccount();
    }

    public function getTransferTypeBasedOnPayeeAccount(string $payeeAccount)
    {
        $firstFourDigitsOfAccountNumber = substr($payeeAccount, 0, 4);
        $firstSixDigitsOfAccountNumber = substr($payeeAccount, 0, 6);

        if ((in_array($firstFourDigitsOfAccountNumber, BankTransfer\Processor::PAYEE_ACCOUNT_PREFIXES_FOR_X) === true) or
            (in_array($firstSixDigitsOfAccountNumber, BankTransfer\Processor::PAYEE_ACCOUNT_PREFIXES_FOR_X) === true))
        {
            return true;
        }

        return false;
    }
}
