<?php

namespace RZP\Reconciliator\Base\SubReconciliator;

use App;
use Carbon\Carbon;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Ledger\ReverseShadow\Payments\Core as ReverseShadowPaymentsCore;
use RZP\Models\QrCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Card\IIN;
use RZP\Models\Transaction;
use RZP\Reconciliator\Base;
use RZP\Models\Batch\Entity;
use RZP\Jobs\CardsPaymentRecon;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Models\Base\PublicCollection;
use RZP\Reconciliator\RequestProcessor;
use RZP\Exception\ReconciliationException;
use RZP\Models\Payment\Processor\UpiUnexpectedPaymentRefundHandler;
use Neves\Events\TransactionalClosureEvent;
use RZP\Models\Ledger\CaptureJournalEvents;
use RZP\Models\Batch\Processor\Reconciliation;
use RZP\Models\Payment\Verify\Result as VerifyResult;
use RZP\Reconciliator\RequestProcessor\Base as ReqBase;
use RZP\Jobs\Ledger\CreateLedgerJournal as LedgerEntryJob;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\Foundation\SubReconciliate
{
    use UpiUnexpectedPaymentRefundHandler;

    const GATEWAY_FEES_ABSENT_GATEWAYS = [
        RequestProcessor\Base::KOTAK,
        RequestProcessor\Base::NETBANKING_AXIS,
        RequestProcessor\Base::NETBANKING_ICICI,
        RequestProcessor\Base::NETBANKING_FEDERAL,
        RequestProcessor\Base::NETBANKING_RBL,
        RequestProcessor\Base::NETBANKING_INDUSIND,
        RequestProcessor\Base::NETBANKING_CORPORATION,
        RequestProcessor\Base::NETBANKING_ALLAHABAD,
        RequestProcessor\Base::NETBANKING_CANARA,
        RequestProcessor\Base::NETBANKING_IDFC,
        RequestProcessor\Base::NETBANKING_SIB,
        RequestProcessor\Base::NETBANKING_CBI,
        RequestProcessor\Base::NETBANKING_SCB,
        RequestProcessor\Base::NETBANKING_YESB,
        RequestProcessor\Base::NETBANKING_KVB,
        RequestProcessor\Base::NETBANKING_CUB,
        RequestProcessor\Base::NETBANKING_IDBI,
        RequestProcessor\Base::NETBANKING_IBK,
        RequestProcessor\Base::NETBANKING_SVC,
        RequestProcessor\Base::NETBANKING_FSB,
        RequestProcessor\Base::NETBANKING_IOB,
        RequestProcessor\Base::NETBANKING_DCB,
        RequestProcessor\Base::JIOMONEY,
        RequestProcessor\Base::VIRTUAL_ACC_KOTAK,
        RequestProcessor\Base::VIRTUAL_ACC_YESBANK,
        RequestProcessor\Base::VIRTUAL_ACC_RBL,
        RequestProcessor\Base::VIRTUAL_ACC_ICICI,
        RequestProcessor\Base::NETBANKING_PNB,
        RequestProcessor\Base::NETBANKING_BOB,
        RequestProcessor\Base::UPI_SBI,
        RequestProcessor\Base::NETBANKING_OBC,
        RequestProcessor\Base::NETBANKING_CSB,
        RequestProcessor\Base::NETBANKING_HDFC,
        RequestProcessor\Base::NETBANKING_EQUITAS,
        RequestProcessor\Base::NETBANKING_VIJAYA,
        RequestProcessor\Base::NETBANKING_SBI,
        RequestProcessor\Base::NETBANKING_JSB,
        RequestProcessor\Base::HITACHI,
        RequestProcessor\Base::UPI_HDFC,
        RequestProcessor\Base::UPI_ICICI,
        RequestProcessor\Base::UPI_AXIS,
        RequestProcessor\Base::UPI_HULK,
        RequestProcessor\Base::AIRTEL,
        RequestProcessor\Base::AMEX,
        RequestProcessor\Base::CARDLESS_EMI_FLEXMONEY,
        RequestProcessor\Base::NETBANKING_BOB_V2,
        RequestProcessor\Base::PAYPAL,
        RequestProcessor\Base::BAJAJFINSERV,
        RequestProcessor\Base::GETSIMPL,
        RequestProcessor\Base::EMANDATE_AXIS,
        RequestProcessor\Base::HDFC_DEBIT_EMI,
        RequestProcessor\Base::INDUSIND_DEBIT_EMI,
        RequestProcessor\Base::KOTAK_DEBIT_EMI,
        RequestProcessor\Base::UPI_JUSPAY,
        RequestProcessor\Base::UPI_YESBANK,
        RequestProcessor\Base::NETBANKING_JKB,
        RequestProcessor\Base::UPI_AIRTEL,
        RequestProcessor\Base::CRED,
        RequestProcessor\Base::NETBANKING_UBI,
        RequestProcessor\Base::NETBANKING_AUSF,
        RequestProcessor\Base::NETBANKING_AUSF_CORP,
        RequestProcessor\Base::NETBANKING_KOTAK_V2,
        RequestProcessor\Base::NETBANKING_HDFC_CORP,
        RequestProcessor\Base::NETBANKING_DLB,
        RequestProcessor\Base::NETBANKING_TMB,
        RequestProcessor\Base::NETBANKING_NSDL,
        RequestProcessor\Base::WALNUT369,
        RequestProcessor\Base::TWID,
        RequestProcessor\Base::FULCRUM,
        RequestProcessor\Base::CHECKOUT_DOT_COM,
        RequestProcessor\Base::NETBANKING_BDBL,
        RequestProcessor\Base::NETBANKING_UCO,
        RequestProcessor\Base::NETBANKING_UJJIVAN,
        RequestProcessor\Base::PAYLATER_LAZYPAY,
        RequestProcessor\Base::CARDLESS_EMI_EARLYSALARY,
        RequestProcessor\Base::NETBANKING_SARASWAT,
        RequestProcessor\Base::EMERCHANTPAY,
        RequestProcessor\Base::NETBANKING_DBS,
    ];

    /**
     * Not receiving proper IIN information from Hitachi Recon,
     * will not update database based on Hitachi Recon
     */
    const SKIP_IIN_SAVING_GATEWAYS = [
        RequestProcessor\Base::HITACHI
    ];
    const GATEWAY_FEES_MISSING_GATEWAYS = [
        // For HDFC, record gateway fees of payments before 7th Nov
        RequestProcessor\Base::HDFC         => 1509993000,

        // For CardFssBob, record gateway fees of payments before 15th Oct 2018 00:00
        RequestProcessor\Base::CARD_FSS_BOB => 1539541800,
    ];

    // This will need to be overridden in each gateway's payment recon.
    // This contains domestic amount of transaction
    const COLUMN_PAYMENT_AMOUNT = '';

    // This will need to be overridden in each gateway's payment recon.
    // This contains international amount of transaction
    const COLUMN_INTERNATIONAL_PAYMENT_AMOUNT = '';

    //
    // FinOps adds this column when they want to force auth the payment.
    // This flag is needed bcoz, with batch service flow, we can not add
    // more than 4-5 payment_ids in force auth field due to limitation
    // of 240 chars in settings json.
    //
    const RZP_FORCE_AUTH_PAYMENT = 'rzp_force_auth_payment';

    /*******************
     * Instance objects
     *******************/

    protected $paymentRepo;
    protected $iinRepo;
    protected $cardRepo;
    protected $transactionRepo;

    /**
     * @var Payment\Entity;
     */
    protected $payment;
    protected $reconciled;
    protected $paymentIin;
    protected $gatewayPayment;
    protected $paymentTransaction;

    /**
     * It tells whether we should attempt force authorize for failed payments on the gateway.
     * If force authorize is enabled, we do not make gateway call and mark payments as authorized.
     */
    protected $allowForceAuthorization = false;

    /**
     * For some gateway we will revalidate the payment id if do not find that in Payment Table.
     * 1. This is required because gateways by default consider 14 char id to be payment id.
     * 2. We do not want to put unnecessary check on any 14 char id.
     * 3. Once gateway checks if validated or not, we will not revalidate
     *
     * @var bool
     */
    protected $isPaymentIdRevalidatedOnGateway;

    /**
     * This flag is updated when the  payment is updated or when the new unexpected payment is created for amount mismatch.
     * @var bool
     */
    protected $shouldPaymentBeReloaded = false;

    public function __construct(string $gateway = null, Entity $batch = null)
    {
        parent::__construct($gateway);

        $this->messenger->batch = $batch;

        $this->batch = $batch;

        $this->paymentRepo     = $this->repo->payment;
        $this->iinRepo         = $this->repo->iin;
        $this->transactionRepo = $this->repo->transaction;
        $this->cardRepo        = $this->repo->card;
    }

    public function runReconciliate($row)
    {
        //
        // Resetting row various attributes here which could have been set during
        // reconciliation of a particular row.
        //
        $this->resetRowProcessingAttributes();

        $this->insertRowInOutputFile($row, Base\Reconciliate::PAYMENT);

        $rowDetails = $this->getRowDetailsStructured($row);

        if (empty($rowDetails) === true)
        {
            $this->handleUnprocessedRow($row);

            return;
        }

        $paymentId = $rowDetails[BaseReconciliate::PAYMENT_ID];

        $this->setMiscEntityDetailsInOutput($this->payment);

        $this->setTerminalDetailsInOutput($this->payment->terminal);

        $this->calculateAndSetNetAmountInOutputFile($row, $rowDetails);

        try
        {
            //
            // Note : Recon process halts for upi_axis gateway when encountered with gateway mismatch.
            $this->checkForGatewayMismatch();

            $this->processReconciliationRow($row, $rowDetails, $paymentId);

            $this->releaseResourceForRecon($paymentId);
        }
        catch (\Exception $ex)
        {
            // Ideally, there shouldn't be any exceptions thrown. They should be handled
            // in the respective reconciliation steps.

            // Increment the failure count for the summary.
            $this->setSummaryCount(self::FAILURES_SUMMARY, $paymentId);

            $this->trace->info(
                TraceCode::RECON_FAILURE,
                [
                    'message'       => 'Unable to perform one of the reconciliation actions -> ' . $ex->getMessage(),
                    'payment_id'    => $paymentId,
                    'extra_details' => $this->extraDetails,
                    'gateway'       => $this->gateway,
                    'batch_id'      => $this->batchId,
                ]);

            $this->trace->traceException($ex);

            if (empty(static::$reconOutputData[static::$currentRowNumber][self::RECON_STATUS]) === true)
            {
                // if the status is not set, then set it to failure
                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED,'Unable to perform one of the reconciliation actions -> ' . $ex->getMessage());
            }

            throw $ex;
        }
    }

    /**
     * Calculates Net amount from the MIS row and
     * sets it in the output file
     *
     * @param array $row
     * @param array $rowDetails
     */
    protected function calculateAndSetNetAmountInOutputFile(array $row, array $rowDetails)
    {
        $grossAmt = intval($this->getReconPaymentAmount($row));

        $gatewayFee = intval($rowDetails[Base\Reconciliate::GATEWAY_FEE]);

        $gst = intval($rowDetails[Base\Reconciliate::GATEWAY_SERVICE_TAX]);

        $netAmount = ($grossAmt - ($gatewayFee + $gst)) / 100;

        $this->setReconNetAmountInOutput($netAmount);
    }

    /**
     * Adds trace if there is mismatch between the recon gateway
     * for this recon file upload and the payment entity gateway.
     */
    protected function checkForGatewayMismatch()
    {
        $paymentGateway = $this->getGatewayNameFromPayment($this->payment);

        if ($this->gateway !== $paymentGateway)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'             => Base\InfoCode::RECON_PAYMENT_GATEWAY_MISMATCH,
                    'payment_id'            => $this->payment->getId(),
                    'recon_gateway'         => $this->gateway,
                    'payment_gateway'       => $paymentGateway,
                    'payment_terminal_id'   => $this->payment->getTerminalId(),
                    'batch_id'              => $this->batchId,
                ]);

            if (array_key_exists($this->gateway, ReqBase::HALT_RECON_ON_GATEWAY_MISMATCH) === true)
            {
                throw new ReconciliationException(Base\InfoCode::RECON_PAYMENT_GATEWAY_MISMATCH,
                    [
                        'recon_gateway'     => $this->gateway,
                        'payment_gateway'   => $paymentGateway,
                        'payment_id'        => $this->payment->getId(),
                        'batch_id'          => $this->batchId,
                    ]);
            }
        }

        return;
    }

    /**
     * Core payment reconciliation functionalities
     *
     * @param $row
     * @param $rowDetails
     * @param $paymentId
     * @throws \RZP\Exception\LogicException
     */
    protected function processReconciliationRow($row, $rowDetails, $paymentId)
    {
        // Setting reconciled attribute before pre Reconciled check to check for duplicate row
        $this->reconciled = $this->checkIfAlreadyReconciled($this->payment);

        // Increment the total count for the summary
        $this->setSummaryCount(self::TOTAL_SUMMARY, $paymentId);

        $this->runPreReconciledAtCheckRecon($rowDetails);

        $resource = (new Transaction\Service())->getTransactionMutexresource($this->payment);

        $this->mutex->acquireAndRelease(
            $resource,
            function () use ($row, $rowDetails, $paymentId)
            {
                if ($this->reconciled === true)
                {
                    $this->handleAlreadyReconciled($paymentId, $this->getPaymentTransaction()->getReconciledAt());

                    //
                    // Record gateway fee and service tax for reconciled payments
                    //
                    $this->recordMissingGatewayFeeAndServiceTax($rowDetails);

                    $this->createGatewayCapturedEntityIfApplicable($row);
                }
                else
                {
                    $validate = $this->validatePaymentDetails($row);

                    if ($validate === true)
                    {
                        $persistSuccess = $this->persistReconciliationData($rowDetails, $row);

                        if ($persistSuccess === false)
                        {
                            $this->handlePersistReconciliationDataFailure($paymentId);
                        }
                    }
                    else
                    {
                        $this->handleFailedValidation($paymentId);
                    }
                }

            });

        $this->setTransactionDetailsInOutput($this->getPaymentTransaction());

        //
        // Payment can be updated from setPaymentAcquirerData before validation or
        // from markGatewayCapturedAsTrue after validation, for both cases we are
        // saving payment entity here from single location to save update queries
        //

        $this->repo->saveOrFail($this->payment);

        $this->handleUnExpectedPaymentRefundInRecon($this->payment);
    }

    public function resetRowProcessingAttributes()
    {
        $this->payment                         = null;
        $this->reconciled                      = false;
        $this->paymentIin                      = null;
        $this->gatewayPayment                  = null;
        $this->paymentTransaction              = null;
        $this->isPaymentIdRevalidatedOnGateway = false;

        parent::resetRowProcessingAttributes();
    }

    protected function runPreReconciledAtCheckRecon($rowDetails)
    {
        // Setting acquirer data, will be persist from persistPaymentData method
        $this->setPaymentAcquirerData($rowDetails);

        //
        // Persisting gateway data in Pre Reconciled-At check to identify duplicate row.
        // If reference number is already set, identify for duplicate row or data mismatch.
        //
        $this->persistGatewayData($rowDetails);

        $this->persistGatewaySettledAt($this->payment, $rowDetails);

        $this->persistGatewayAmount($this->payment, $rowDetails);

        if ($this->payment->isRoutedThroughCardPayments() === true)
        {
            $this->cardsPaymentServiceDispatch($rowDetails);
        }
    }

    /**
     * Here we will call CPS endpoint to fetch Auth data, match with
     * MIS data and persist again by pushing to CPS queue.
     *
     * @param array $rowDetails
     */
    protected function cardsPaymentServiceDispatch(array $rowDetails)
    {
        $data = [
            'payment_id' => $this->payment->getId(),
            'params'     => [
                Base\Constants::RRN                    => $rowDetails[BaseReconciliate::REFERENCE_NUMBER],
                Base\Constants::AUTH_CODE              => $rowDetails[BaseReconciliate::AUTH_CODE],
                Base\Constants::GATEWAY_TRANSACTION_ID => $rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID],
                Base\Constants::GATEWAY_REFERENCE_ID1  => $rowDetails[BaseReconciliate::GATEWAY_REFERENCE_ID1],
            ],
            'mode'       => $this->mode,
            'gateway'    => $this->gateway,
            'batch_id'   => $this->batchId,
        ];

        if ($this->payment->getGateway() === Payment\Gateway::CYBERSOURCE && $this->payment->terminal->getGatewayAcquirer() === 'axis')
        {
            $data['params'][ Base\Constants::GATEWAY_REFERENCE_ID2] = $rowDetails[BaseReconciliate::GATEWAY_REFERENCE_ID2];
        }

        // Adding below check as we need to send fulcrum payment ids to cps service
        // to update the status further in fulcrum db
        // https://razorpay.slack.com/archives/CRVCT80KW/p1630389589039000
        if ((strtolower($this->payment->gateway) === Payment\Gateway::FULCRUM) and
            ($this->payment->getGatewayCaptured() !== true))
        {
            $data[Reconciliation::IS_GATEWAY_CAPTURED_MISMATCH] = 1;
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'  => Base\InfoCode::GATEWAY_CAPTURED_MISMATCH,
                    'payment_id' => $this->payment->getId(),
                    'data'       => $data
                ]
            );
        }

        CardsPaymentRecon::dispatch($data);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code'  => Base\InfoCode::RECON_CPS_JOB_DISPATCH,
                'payment_id' => $this->payment->getId(),
                'data'       => $data
            ]
        );
    }

    protected function validatePaymentDetails(array $row)
    {
        $validPaymentAmount = $this->validatePaymentAmountEqualsReconAmount($row);

        if ($validPaymentAmount === false)
        {
            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::AMOUNT_MISMATCH);
        }

        $validCurrencyCode  = $this->validatePaymentCurrencyEqualsReconCurrency($row);

        if ($validCurrencyCode === false)
        {
            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::CURRENCY_MISMATCH);
        }

        if (($validPaymentAmount === false) or ($validCurrencyCode === false))
        {
            return false;
        }

        //
        // As validatePaymentStatus() may end up force-authorizing the failed payment,
        // we keep it after payment amount and currency match check.
        //
        $validPaymentStatus = $this->validatePaymentStatus($row);

        return ($validPaymentStatus === true);
    }

    /**
     * Validates that the payment status is not failed.
     *
     * @param $row
     *
     * @return bool
     */
    protected function validatePaymentStatus($row)
    {
        $reconPaymentStatus = $this->getReconPaymentStatus($row);

        //
        // In some cases the recon file contains failed payments,
        // too, In this case we do not want to reconcile them
        //
        if ($reconPaymentStatus === Payment\Status::FAILED)
        {
            $isApiPaymentSuccess = $this->payment->hasBeenAuthorized();

            if ($isApiPaymentSuccess === true)
            {
                //
                // Recon status is failed, and payment has been authorized.
                // This would be an error, as there's a status mismatch.
                //

                $this->trace->info(
                    TraceCode::RECON_CRITICAL_ALERT,
                    [
                        'info_code'  => Base\InfoCode::MIS_FILE_PAYMENT_FAILED,
                        'message'    => 'Recon status is failed, but authorized_at is set in API',
                        'payment_id' => $this->payment->getId(),
                        'gateway'    => $this->gateway
                    ]);
            }

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::MIS_FILE_PAYMENT_FAILED);

            return false;
        }

        //
        // If payment status is authorized, captured or refunded, provided that recon status is not failed,
        // we can safely return that the payment status is valid for reconciliation of row.
        //

        if ($this->payment->isFailed() === false)
        {
            return true;
        }

        $this->traceRazorpayFailedPayment();

        $success = $this->tryAuthorizeFailedPayment($row);

        if ($success === false)
        {
            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::RECON_AUTHORIZE_FAILED_PAYMENT_UNSUCCESSFUL);
        }

        return $success;
    }

    protected function traceRazorpayFailedPayment()
    {
        //
        // In case the recon row's status field is a success, and api payment status is failed or created,
        // we would need to run the flow below, where we try to authorize the payment forcefully or via verify.
        //
        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code'     => Base\InfoCode::RAZORPAY_FAILED_PAYMENT_RECON,
                'message'       => 'Payment status is failed but present in MIS. Trying to authorize.',
                'id'            => $this->payment->getId(),
                'gateway'       => $this->gateway,
                'payment'       => $this->payment->toArrayPublic()
            ]);
    }

    /**
     * Override in child class
     *
     * @param array $row
     * @return null
     */
    protected function getReconPaymentStatus(array $row)
    {
        //
        // The return value of this method must be mapped
        // to one of the statuses in Payment\Status
        //
        return null;
    }

    protected function getReconPaymentAmount(array $row)
    {
        //
        // If this constant is not defined in the gateway classes,
        // we don't do any recon on the payment amount at all.
        //
        if ((static::COLUMN_PAYMENT_AMOUNT === '') and
            (static::COLUMN_INTERNATIONAL_PAYMENT_AMOUNT === ''))
        {
            return null;
        }

        $amountColumn = ($this->isInternationalPayment($row) === true) ?
                        static::COLUMN_INTERNATIONAL_PAYMENT_AMOUNT :
                        static::COLUMN_PAYMENT_AMOUNT;

        $paymentAmountColumns = (is_array($amountColumn) === false) ?
                                [$amountColumn] :
                                 $amountColumn;

        $paymentAmountColumn = array_first(
            $paymentAmountColumns,
            function ($amount) use ($row)
            {
                return (array_key_exists($amount, $row) === true);
            });

        if ($paymentAmountColumn === null)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => Base\InfoCode::AMOUNT_ABSENT,
                    'payment_id'        => $this->payment->getId(),
                    'expected_column'   => $amountColumn,
                    'amount'            => $this->payment->getBaseAmount(),
                    'currency'          => $this->payment->getCurrency(),
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return Helper::getIntegerFormattedAmount($row[$paymentAmountColumn]);
    }

    /**
     * Gets amount of payment entity based on transaction currency
     */
    protected function getPaymentEntityAmount()
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        return ($convertCurrency === true) ? $this->payment->getBaseAmount() : $this->payment->getGatewayAmount();
    }

    /**
     * This function has to be overriden in child classes.
     * This will return true of current transaction is domestic or international
     * @param array $row
     * @return bool
     */
    protected function isInternationalPayment(array $row)
    {
        return false;
    }

    protected function tryAuthorizeFailedPayment($row)
    {
        //
        // If a gateway has implemented force authorization,
        // always use that, instead of verify. There's no
        // need for running verify if force authorization is present.
        //
        // Disabling force auth if request from mailgun because of
        // vulnerability mentioned in SBB-330.
        if ((($this->allowForceAuthorization === true) or
             ($this->isforceAuthFlagSetInRow($row) === true)) and
            ($this->source !== RequestProcessor\Base::MAILGUN) and
            (in_array($this->payment->getGateway(), Payment\Gateway::FORCE_AUTHORIZE_GATEWAYS, true) === true) and
            ($this->payment->isUpiRecurring() === false))
        {
            return $this->handleForceAuthorization($row);
        }
        else
        {
            return $this->handleVerifyPayment();
        }
    }

    public function handleVerifyPayment()
    {
        $paymentService = new Payment\Service;

        try
        {
            if ($this->payment->isExternal() === true)
            {
                $verifyResponse = $this->handleRearchPaymentVerification();
            }
            else
            {
                // Try to make it authorized
                $verifyResponse = $paymentService->verifyPayment($this->payment);
            }

        }
        catch(\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_FAILED_VERIFY,
                [
                    'message'    => 'Verification/Authorization threw an exception. -> ' . $ex->getMessage(),
                    'payment_id' => $this->payment->getId(),
                    'amount'     => $this->payment->getAmount(),
                    'gateway'    => $this->gateway
                ]);

            $this->trace->traceException($ex);

            return false;
        }

        switch($verifyResponse)
        {
            case VerifyResult::AUTHORIZED:

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'message'    => 'Verify returned authorized.',
                        'payment_id' => $this->payment->getId(),
                        'gateway'    => $this->gateway
                    ]
                );

                $authorizeSuccess = $this->handleVerifyAuthorized();

                break;

            case VerifyResult::SUCCESS:

                $this->trace->info(
                    TraceCode::RECON_FAILED_VERIFY,
                    [
                        'message'    => 'Verify returned failed. Payment is still in failed state.',
                        'payment_id' => $this->payment->getId(),
                        'amount'     => $this->payment->getAmount(),
                        'gateway'    => $this->gateway
                    ]);

                $authorizeSuccess = false;

                break;

            case VerifyResult::ERROR:
            case VerifyResult::TIMEOUT:
            case VerifyResult::UNKNOWN:

                $this->trace->info(
                    TraceCode::RECON_FAILED_VERIFY,
                    [
                        'message'       => 'Verify command failed or unable to recognize the response.',
                        'payment_id'    => $this->payment->getId(),
                        'amount'        => $this->payment->getAmount(),
                        'verify_status' => $verifyResponse,
                        'gateway'       => $this->gateway
                    ]);

                $authorizeSuccess = false;

                break;

            case VerifyResult::REARCH_CAPTURED:
                $this->trace->info(
                    TraceCode::RECON_REARCH_PAYMENT_CAPTURED,
                    [
                        'message'       => 'CPS has already captured the payment and txn will get created',
                        'payment_id'    => $this->payment->getId(),
                        'amount'        => $this->payment->getAmount(),
                        'gateway'       => $this->gateway,
                        'captured_at'   => $this->payment->getCapturedAt(),
                    ]);

                $authorizeSuccess = true;
                break;
            // If payment is already being authorized by other thread
            // or any unexpected gateway error comes, null is returned. No slack
            // message in this case, happens for all the payments in the file.
            default:

                $this->trace->info(
                    TraceCode::RECON_FAILED_VERIFY,
                    [
                        'message'       => 'Verify command failed or unable to recognize the response.',
                        'payment_id'    => $this->payment->getId(),
                        'amount'        => $this->payment->getAmount(),
                        'gateway'       => $this->gateway,
                        'verify_status' => $verifyResponse,
                    ]);

                $authorizeSuccess = false;

                break;
        }

        return $authorizeSuccess;
    }

    protected function handleRearchPaymentVerification()
    {
        $status = $this->payment->getStatus();

        $response = $this->app['pg_router']->paymentVerify($this->payment->getId());

        $this->payment = $this->paymentRepo->findOrFail($this->payment->getId());

        if ($this->payment->hasBeenCaptured() === true)
        {
            return VerifyResult::REARCH_CAPTURED;
        }

        if (($status === Payment\Status::FAILED) and
            ($this->payment->hasBeenAuthorized() === true))
        {
            return VerifyResult::AUTHORIZED;
        }

        if ($status !== $this->payment->getStatus())
        {
            return VerifyResult::UNKNOWN;
        }

        return VerifyResult::SUCCESS;
    }

    protected function handleForceAuthorization(array $row)
    {
        $authorizeSuccess = $this->forceAuthorizeFailed($row);

        if ($authorizeSuccess === true)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => Base\InfoCode::PAYMENT_FORCE_AUTHORIZED,
                    'payment_id' => $this->payment->getId(),
                    'amount'     => $this->payment->getAmount(),
                    'gateway'    => $this->gateway,
                ]);

            $authResponse = $this->handleVerifyAuthorized();
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_FAILED_VERIFY,
                [
                    'info_code'  => Base\InfoCode::PAYMENT_FORCE_AUTHORIZE_FAILED,
                    'payment_id' => $this->payment->getId(),
                    'amount'     => $this->payment->getAmount(),
                    'gateway'    => $this->gateway
                ]);

            $authResponse = false;
        }

        return $authResponse;
    }

    /**
     * @param array $row
     * @return bool
     * Checks if finOps has added specific column/flag to
     * force authorize this payment.
     */
    protected function isforceAuthFlagSetInRow(array $row)
    {
        return (empty($row[self::RZP_FORCE_AUTH_PAYMENT]) === false);
    }

    /**
     * This function will be called if the gateway requires a force
     * authorization from failed state. If no force authorization,
     * it means that the payment is still in failed state and hence
     * should return back false.
     *
     * @param array $row
     *
     * @return bool
     */
    protected function forceAuthorizeFailed(array $row)
    {
        $paymentService = new Payment\Service;

        $paymentId = $this->payment->getPublicId();
        $amount    = $this->payment->getAmount();

        Base\Reconciliate::$forceAuthorizedPayments[] = [
            'id'        => $this->payment->getId(),
            'amount'    => $amount,
        ];

        $this->trace->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'message'         => 'Payment status is failed. Doing force authorize',
                'payment_id'      => $this->payment->getId(),
                'amount'          => $this->payment->getAmount(),
                'gateway'         => $this->gateway
            ]);

        $input = $this->getInputForForceAuthorize($row);

        // If there's any issue during authorize, the function throws an exception.
        $response = $paymentService->forceAuthorizeFailed($paymentId, $input);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code' => 'FORCE_AUTHORIZATION_RESPONSE',
                'message'   => 'Response received from force authorization',
                'response'  => $response
            ]
        );

        //
        // Checking the status of payment here, it should not be failed now.
        // Status can be authorized or captured (in case of auto-capture enabled)
        //
        if ((empty($response['status']) === false) and
            ($response['status'] !== Payment\Status::FAILED))
        {
            return true;
        }

        return false;
    }

    public function getPaymentTransaction()
    {
        if (($this->payment->isExternal() === false) or
            ($this->payment->hasTransaction() === true))
        {
            return $this->payment->transaction;
        }

        $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($this->payment);

        return $txn;
    }


    public function setPayment($payment)
    {
        $this->payment = $payment ;
    }

    public function setTransaction ()
    {
       $this->paymentTransaction = $this->getPaymentTransaction(); ;
    }

    public function setGateway ($gateway)
    {
       $this->gateway = $gateway; ;
    }


    public function handleVerifyAuthorized()
    {
        $this->payment = $this->paymentRepo->findOrFail($this->payment->getId());

        // Set the payment transaction for the row.
        $this->paymentTransaction = $this->getPaymentTransaction();

        if ($this->paymentTransaction !== null)
        {
            $success = true;
        }
        else
        {
            $createTransactionSuccess = $this->attemptToCreateMissingPaymentTransaction();

            if ($createTransactionSuccess === true)
            {
                $this->paymentTransaction = $this->getPaymentTransaction();

                $success = true;
            }
            else
            {
                $this->trace->info(
                    TraceCode::RECON_FAILURE,
                    [
                        'failure_code'  => Base\InfoCode::PAYMENT_TRANSACTION_ABSENT,
                        'message'       => 'Unable to create payment transaction after verifying',
                        'payment_id'    => $this->payment->getId(),
                        'gateway'       => $this->gateway
                    ]);

                $success = false;
            }
        }

        return $success;
    }

    protected function persistReconciliationData($rowDetails, $row)
    {
        //
        // If the row reaches this part of the code, that means that it is captured on the gateway's end.
        //
        $this->markGatewayCapturedAsTrue();

        $this->updatePaymentHoldIfApplicable();

        $this->createGatewayCapturedEntityIfApplicable($row);

        $recordSuccess = $this->recordGatewayFeeAndServiceTax($rowDetails);

        if ($recordSuccess === true)
        {
            $this->persistReconciledAt($this->payment);
        }

        $this->persistCardDetailsIfAbsent($rowDetails);

        $this->persistGatewayData($rowDetails);

        $this->persistGatewaySettledAt($this->payment, $rowDetails);

        $this->persistGatewayAmount($this->payment, $rowDetails);

        return $recordSuccess;
    }

    protected function updatePaymentHoldIfApplicable()
    {
        if (($this->payment->getOnHold() === false) or
            ($this->payment->merchant->canHoldPayment() === false))
        {
            return;
        }

        (new Payment\Core)->updatePaymentOnHold($this->payment, false);
    }

    protected function getRowDetailsStructured($row)
    {
        $this->traceReconRow($row);

        $paymentId = $this->getPaymentId($row);

        // If payment id is not present, return. No point of evaluating the row.
        if (empty($paymentId) === true)
        {
            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::PAYMENT_ID_NOT_FOUND);

            return null;
        }

        $this->setPaymentAndTransaction($row, $paymentId);

        //If payment is not found, dont throw. mark it as Unprocessed. returning null will do that.
        if ($this->payment === null)
        {
            return null;
        }

        //
        // Have to set allowForceAuthorization AFTER setting payment instance because this
        // attribute can be dependent on payment instance's attributes. For eg. payment's created_at
        //
        $this->setAllowForceAuthorization($this->payment);

        $referenceNumber = $this->getReferenceNumber($row);

        $gatewayPaymentDate = $this->getGatewayPaymentDate($row);

        $cardDetails = $this->getCardDetails($row);

        $serviceTax = $this->getGatewayServiceTax($row);

        $fee = $this->getGatewayFee($row);

        $gatewayTransactionId = $this->getGatewayTransactionId($row);

        $gatewayPaymentId = $this->getGatewayPaymentId($row);

        $gatewaySettledAt = $this->getGatewaySettledAt($row);

        $gatewayAmount = $this->getGatewayAmount($row);

        $customerDetails = $this->getCustomerDetails($row);

        $accountDetails = $this->getAccountDetails($row);

        $authCode = $this->getAuthCode($row);

        $arn = $this->getArn($row);

        $gatewayUtr = $this->getGatewayUtr($row);

        $gatewayUniqueId = $this->getGatewayUniqueId($row);

        $gatewayReferenceId1 = $this->getGatewayReferenceId1($row);

        $gatewayReferenceId2 = $this->getGatewayReferenceId2($row);

        $rowDetails = [
            BaseReconciliate::PAYMENT_ID             => $paymentId,
            BaseReconciliate::GATEWAY_SERVICE_TAX    => $serviceTax,
            BaseReconciliate::GATEWAY_FEE            => $fee,
            BaseReconciliate::GATEWAY_SETTLED_AT     => $gatewaySettledAt,
            BaseReconciliate::GATEWAY_AMOUNT         => $gatewayAmount,
            BaseReconciliate::GATEWAY_TRANSACTION_ID => trim($gatewayTransactionId),
            BaseReconciliate::GATEWAY_PAYMENT_ID     => trim($gatewayPaymentId),
            BaseReconciliate::REFERENCE_NUMBER       => trim($referenceNumber),
            BaseReconciliate::GATEWAY_PAYMENT_DATE   => trim($gatewayPaymentDate),
            BaseReconciliate::AUTH_CODE              => trim($authCode),
            BaseReconciliate::ARN                    => trim($arn),
            BaseReconciliate::GATEWAY_UTR            => trim($gatewayUtr),
            BaseReconciliate::GATEWAY_UNIQUE_ID      => $gatewayUniqueId,
            BaseReconciliate::GATEWAY_REFERENCE_ID1  => trim($gatewayReferenceId1),
            BaseReconciliate::GATEWAY_REFERENCE_ID2  => trim($gatewayReferenceId2)
        ];

        // For wallets and netbanking, $cardDetails would be empty.
        // We do an array_filter because sometimes, due to parsing errors,
        // we may not be able to get some card details which we would have
        // expected to get, due to which their corresponding values would be null.
        if (empty(array_filter($cardDetails)) === false)
        {
            $rowDetails[BaseReconciliate::CARD_DETAILS] = array_filter($cardDetails);
        }

        // We set customer details only for netbanking
        if (empty(array_filter($customerDetails)) === false)
        {
            $rowDetails[BaseReconciliate::CUSTOMER_DETAILS] = array_filter($customerDetails);
        }

        // We set account details only for netbanking
        if (empty(array_filter($accountDetails)) === false)
        {
            $rowDetails[BaseReconciliate::ACCOUNT_DETAILS] = array_filter($accountDetails);
        }

        return $rowDetails;
    }

    /**
     * To be overridden in the child gateway
     * @param array $row
     * @throws \BadMethodCallException
     * @return null
     */
    protected function getPaymentId(array $row)
    {
        throw new \BadMethodCallException(
            'getPaymentId method needs to be implemented by child PaymentReconciliate class'
        );
    }

    public function setPaymentAndTransaction($row, $paymentId)
    {
        if (UniqueIdEntity::verifyUniqueId($paymentId, false) === false)
        {
            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::PAYMENT_ID_NOT_AS_EXPECTED);

            return null;
        }
        $acquire = $this->lockResourceForRecon($paymentId);

        if($acquire === false)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => Base\InfoCode::UNABLE_TO_ACQUIRE_LOCK,
                    'payment_id'    => $paymentId,
                    'gateway'       => $this->gateway,
                    'batch_id'      => $this->batchId,
                ]);
        }

        try
        {
            $this->payment = $this->fetchOrGetPaymentById($paymentId);
            $this->paymentTransaction = $this->getPaymentTransaction();

            //
            // It's possible that the payment is in failed state and hence the transaction
            // is not present. While validating the payment status, we check for failed status
            // and try to verify and authorize. We handle an empty transaction there.
            //
            if ($this->paymentTransaction === null)
            {
                // The row details are already traced and can be retrieved from Splunk.
                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'info_code'  => Base\InfoCode::PAYMENT_TRANSACTION_ABSENT,
                        'payment_id' => $paymentId,
                        'gateway'    => $this->gateway,
                        'batch_id'   => $this->batchId,
                    ]);
            }
        }
        catch (\Exception $ex)
        {
            $validatedPaymentId = $this->revalidatePaymentId($row, $paymentId);

            if (empty($validatedPaymentId) === false)
            {
                return $this->setPaymentAndTransaction($row, $validatedPaymentId);
            }

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::PAYMENT_ABSENT);

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'  => Base\InfoCode::PAYMENT_ABSENT,
                    'message'    => 'Payment not found in DB. -> ' . $ex->getCode(),
                    'payment_id' => $paymentId,
                    'gateway'    => $this->gateway,
                    'batch_id'   => $this->batchId,
                ]);
        }
    }

    /**
     * Will return payment id if gateway has implementation
     * @param $row
     * @param $paymentId
     * @return |null
     */
    protected function revalidatePaymentId($row, $paymentId)
    {
        if ($this->isPaymentIdRevalidatedOnGateway === true)
        {
            return null;
        }

        $paymentId = $this->revalidatePaymentIdOnGateway($row, $paymentId);

        $this->isPaymentIdRevalidatedOnGateway = true;

        return $paymentId;
    }

    protected function revalidatePaymentIdOnGateway($row, $paymentId)
    {
        // We can first by default check for QrCode if Gateway is Qr Code Enabled
        $gateways = Payment\Gateway::$upiQrGateways;

        // It will either return Sting or false
        $terminalGateway = array_search($this->gateway, RequestProcessor\Base::GATEWAY_NAME_MAPPING, true);

        // `false` will not be in $gateways
        if (in_array($terminalGateway, $gateways, true) === true)
        {
            // For UPI QR gateways, the recon payment id will be from QrCode Entity
            $qrCode = $this->repo->qr_code->find($paymentId);

            if (empty($qrCode) === false)
            {
                // This is QrCode payment, the recon payment id
                // will be saved in UPI Entity as Merchant Reference.
                // Note : We can not use QrCode entity to find payment id rather
                //      : we will call UPI entity to find payment, now this way
                //      : if callback was missed for QrCode will
                $upiEntity = $this->repo->upi->fetchByMerchantReference($paymentId);

                if (empty($upiEntity) === false)
                {
                    return $upiEntity->getPaymentId();
                }

                // Now, there might be case where UPI Entity is not created for QrCode
                // either because we missed callback or some exception occurred in callback
                // In this case we will create the UPI QR payment right way with recon row
                $this->createUpiQrPayment($row, $paymentId, $terminalGateway);

                // Now UPI entity must be created by the processor
                $upiEntity = $this->repo->upi->fetchByMerchantReference($paymentId);

                if (empty($upiEntity) === false)
                {
                    $paymentId = $upiEntity->getPaymentId();

                    $this->trace->info(
                        TraceCode::RECON_INFO,
                        [
                            'infoCode'      => InfoCode::RECON_UNEXPECTED_UPI_QR_VA_PAYMENT_CREATED,
                            'payment_id'    => $paymentId,
                            'gateway'       => $this->gateway,
                            'batch_id'      => $this->batchId,
                        ]);

                    return $paymentId;
                }

                // Even now if for any reason the payment was not created, we can return null
                // PAYMENT_ABSENT will be traced.
                return null;
            }
        }
    }

    /**
     * Method will create a UPI QR payment for referenceId which is in fact a QrCode Id.
     *
     * @param array $row
     * @param string $referenceId
     * @param string $gateway
     */
    protected function createUpiQrPayment(array $row, string $referenceId, string $gateway)
    {
        try
        {
            $callbackData = $this->generateCallbackData($row);

            // UPI QR processor can take care of duplicate payments, thus no issue of parallel uploads
            $response = (new QrCode\Upi\Service)->processPayment($callbackData, $referenceId, $gateway);

            // Must return true if the payment was created successfully
            assertTrue($response);
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode::RECON_ALERT,
                [
                    'message'       => 'Failed to create UPI QR payment',
                    'refrence_id'   => $referenceId,
                    'gateway'       => $gateway,
                ]);
        }
    }

    /**
     * Set payment entity according to row details
     * 1. Update ARN if found and was not updated before
     * 2. Update AuthCode if found and was not updated before
     *
     * @param $rowDetails
     */
    protected function setPaymentAcquirerData($rowDetails)
    {
        if (empty($rowDetails[BaseReconciliate::ARN]) === false)
        {
            $this->setPaymentReference1($rowDetails[BaseReconciliate::ARN]);
        }

        if (empty($rowDetails[BaseReconciliate::AUTH_CODE]) === false)
        {
            $this->setPaymentReference2($rowDetails[BaseReconciliate::AUTH_CODE]);
        }

        if ((empty($rowDetails[BaseReconciliate::REFERENCE_NUMBER]) === false) and
            (($this->payment->getMethod() === Payment\Method::UPI) or
             ($this->payment->getMethod() === Payment\Method::CARD)))
        {
            $this->setPaymentReference16($rowDetails[BaseReconciliate::REFERENCE_NUMBER]);
        }
    }

    /**
     * If IIN is missing, a new IIN is created with the card type (debit/credit)
     * and card locale (domestic/international).
     * If IIN is already present, we persist the card type and the card locale.
     *
     * @param array $rowDetails
     */
    protected function persistCardDetailsIfAbsent($rowDetails)
    {
        if (empty($rowDetails[BaseReconciliate::CARD_DETAILS]) === true)
        {
            return;
        }

        $cardDetails = $rowDetails[BaseReconciliate::CARD_DETAILS];

        $this->paymentIin = $this->payment->card->iinRelation;
        $gatewayFee = $rowDetails[BaseReconciliate::GATEWAY_FEE];
        $gatewayServiceTax = $rowDetails[BaseReconciliate::GATEWAY_SERVICE_TAX];

        if ($this->paymentIin === null)
        {
            if (empty($this->payment->card->getIin()) === true)
            {
                $this->trace->info(TraceCode::RECON_INFO_ALERT, [
                    'message'             => 'Payment card iin is absent.',
                    'info_code'           => TraceCode::PAYMENT_CARD_IIN_MISSING,
                    'payment_id'          => $this->payment->getId(),
                    'gateway'             => $this->gateway,
                    'batch_id'            => $this->batchId,
                ]);

                return;
            }

            $this->createMissingIin($cardDetails);

            return;
        }

        // if iin is locked for editing, skip persisting recon data for iin
        if ($this->paymentIin->isLocked() === true)
        {
            // add trace ?
            return;
        }

        if (empty($cardDetails[BaseReconciliate::CARD_TYPE]) === false)
        {
            $this->traceCardType($cardDetails[BaseReconciliate::CARD_TYPE], $gatewayFee, $gatewayServiceTax);
        }

        if (empty($cardDetails[BaseReconciliate::CARD_LOCALE]) === false)
        {
            $this->traceCardLocale($cardDetails[BaseReconciliate::CARD_LOCALE], $gatewayFee, $gatewayServiceTax);
        }

        if (empty($cardDetails[BaseReconciliate::CARD_TRIVIA]) === false)
        {
            $this->traceCardTrivia($cardDetails[BaseReconciliate::CARD_TRIVIA], $gatewayFee, $gatewayServiceTax);
        }

        if (empty($cardDetails[BaseReconciliate::ISSUER]) === false)
        {
            $this->traceIssuer($cardDetails[BaseReconciliate::ISSUER], $gatewayFee, $gatewayServiceTax);
        }

        $this->repo->saveOrFail($this->paymentIin);
    }

    protected function persistAccountDetails(array $rowDetails, PublicEntity $gatewayPayment)
    {
        if (empty($rowDetails[BaseReconciliate::ACCOUNT_DETAILS]) === true)
        {
            return;
        }

        $accountDetails = $rowDetails[BaseReconciliate::ACCOUNT_DETAILS];

        $this->persistDebitAccount($accountDetails, $gatewayPayment);

        $this->persistAccountType($accountDetails, $gatewayPayment);

        $this->persistAccountSubType($accountDetails, $gatewayPayment);

        $this->persistAccountBranchcode($accountDetails, $gatewayPayment);

        $this->persistCreditAccount($accountDetails, $gatewayPayment);
    }

    /**
     * Saving Gateway Data into DB
     *
     * Calling this again because payment status can change after verify.
     * Gateway payment can be in failed state earlier and hence gateway data won't be set
     * in preReconciledAtCheckRecon method. After verification, it may have changed to success
     * and now we can set gateway data.
     *
     * @param array $rowDetails
     */
    protected function persistGatewayData(array $rowDetails)
    {
        $gatewayPayment = $this->updateAndFetchGatewayPayment();

        if ($gatewayPayment === null)
        {
            return;
        }

        $this->persistReferenceNumber($rowDetails, $gatewayPayment);

        $this->persistAccountDetails($rowDetails, $gatewayPayment);

        $this->persistGatewayTransactionId($rowDetails, $gatewayPayment);

        $this->persistGatewayPaymentId($rowDetails, $gatewayPayment);

        $this->persistGatewayUtr($rowDetails, $gatewayPayment);

        $this->persistGatewayPaymentDate($rowDetails, $gatewayPayment);

        $this->persistCustomerDetails($rowDetails, $gatewayPayment);

        $this->persistGatewayReconciledAt($rowDetails, $gatewayPayment);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function updateAndFetchGatewayPayment()
    {
        if ($this->gatewayPayment === null)
        {
            $gatewayPayment = $this->getGatewayPayment($this->payment->getId());

            $this->gatewayPayment = $gatewayPayment;
        }

        return $this->gatewayPayment;
    }

    /**
     * Saving the Bank Payment Id from reconciliator file
     *
     * @param array $rowDetails
     * @param PublicEntity $gatewayPayment
     */
    public function persistReferenceNumber(array $rowDetails, PublicEntity $gatewayPayment)
    {
        if (empty($rowDetails[BaseReconciliate::REFERENCE_NUMBER]) === true)
        {
            return;
        }

        $referenceNumber = $rowDetails[BaseReconciliate::REFERENCE_NUMBER];

        $this->setReferenceNumberInGateway($referenceNumber, $gatewayPayment);
    }

    /**
     * Saving the gateway transaction id from reconciliator file
     * Replacing existing value or adding it to the DB
     *
     * @param array $rowDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistGatewayTransactionId(array $rowDetails, PublicEntity $gatewayPayment)
    {
        if (empty($rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID]) === true)
        {
            return;
        }

        $gatewayTransactionId = $rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID];

        $this->setGatewayTransactionId($gatewayTransactionId, $gatewayPayment);
    }

    /**
     * Saving the gateway transaction id from reconciliator file
     * Replacing existing value or adding it to the DB
     *
     * @param array $rowDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistGatewayPaymentId(array $rowDetails, PublicEntity $gatewayPayment)
    {
        if (empty($rowDetails[BaseReconciliate::GATEWAY_PAYMENT_ID]) === true)
        {
            return;
        }

        $gatewayPaymentId = $rowDetails[BaseReconciliate::GATEWAY_PAYMENT_ID];

        $this->setGatewayPaymentId($gatewayPaymentId, $gatewayPayment);
    }


    /**
     * Updates the gateway payment entity with payment date from recon file
     *
     * @param array        $rowDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistGatewayPaymentDate(array $rowDetails, PublicEntity $gatewayPayment)
    {
        if (empty($rowDetails[BaseReconciliate::GATEWAY_PAYMENT_DATE]) === true)
        {
            return;
        }

        $gatewayPaymentDate = $rowDetails[BaseReconciliate::GATEWAY_PAYMENT_DATE];

        $this->setGatewayPaymentDateInGateway($gatewayPaymentDate, $gatewayPayment);
    }

    /**
     * Saving customer information into the DB
     *
     * @param array        $rowDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistCustomerDetails(array $rowDetails, PublicEntity $gatewayPayment)
    {
        if (empty($rowDetails[BaseReconciliate::CUSTOMER_DETAILS]) === true)
        {
            return;
        }

        $customerDetails = $rowDetails[BaseReconciliate::CUSTOMER_DETAILS];

        if (empty($customerDetails[BaseReconciliate::CUSTOMER_ID]) === false)
        {
            $this->persistNbCustomerId($customerDetails, $gatewayPayment);
        }

        if (empty($customerDetails[BaseReconciliate::CUSTOMER_NAME]) === false)
        {
            $this->persistCustomerName($customerDetails, $gatewayPayment);
        }
    }

    /**
     * Sets the Gateway Reconciled At with current time if applicable
     * This field is required for UPI as we get multiple credits for same payment id
     *
     * @param array $rowDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistGatewayReconciledAt(array $rowDetails, PublicEntity $gatewayPayment)
    {
        // If this is the UPI Entity, we can save reconciled at in the payment
        if ($gatewayPayment->getEntity() === Constants\Entity::UPI)
        {
            $reconciledAt = $gatewayPayment->getReconciledAt();

            // We do not need to update the reconciled at if it is already saved
            if (empty($reconciledAt) === false)
            {
                return;
            }

            $gatewayPayment->setReconciledAt($gatewayPayment->freshTimestamp());
        }
    }

    /**
     * Saving customer Id into the DB
     *
     * @param array        $customerDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistNbCustomerId(array $customerDetails, PublicEntity $gatewayPayment)
    {
        $customerId = $customerDetails[BaseReconciliate::CUSTOMER_ID];

        $gatewayPayment->setCustomerId($customerId);
    }

    /**
     * Saving customer Name into the DB
     *
     * @param array        $customerDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistCustomerName(array $customerDetails, PublicEntity $gatewayPayment)
    {
        $customerName = $customerDetails[BaseReconciliate::CUSTOMER_NAME];

        $gatewayPayment->setCustomerName($customerName);
    }

    /**
     * Saving debit account into the DB
     *
     * @param array        $accountDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistDebitAccount(array $accountDetails, PublicEntity $gatewayPayment)
    {
        if (empty($accountDetails[BaseReconciliate::ACCOUNT_NUMBER]) === true)
        {
            return;
        }

        $accountNumber = $accountDetails[BaseReconciliate::ACCOUNT_NUMBER];

        $gatewayPayment->setAccountNumber($accountNumber);
    }

    protected function persistAccountType(array $accountDetails, PublicEntity $gatewayPayment)
    {
        if (empty($accountDetails[BaseReconciliate::ACCOUNT_TYPE]) === true)
        {
            return;
        }

        $accountType = $accountDetails[BaseReconciliate::ACCOUNT_TYPE];

        $gatewayPayment->setAccountType($accountType);
    }

    protected function persistAccountSubType(array $accountDetails, PublicEntity $gatewayPayment)
    {
        if (empty($accountDetails[BaseReconciliate::ACCOUNT_SUBTYPE]) === true)
        {
            return;
        }

        $accountSubType = $accountDetails[BaseReconciliate::ACCOUNT_SUBTYPE];

        $gatewayPayment->setAccountSubType($accountSubType);
    }

    protected function persistAccountBranchcode(array $accountDetails, PublicEntity $gatewayPayment)
    {
        if (empty($accountDetails[BaseReconciliate::ACCOUNT_BRANCHCODE]) === true)
        {
            return;
        }

        $accountBranchcode = $accountDetails[BaseReconciliate::ACCOUNT_BRANCHCODE];

        $gatewayPayment->setAccountBranchcode($accountBranchcode);
    }

    /**
     * Saving credit account into the DB
     *
     * @param array        $accountDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistCreditAccount(array $accountDetails, PublicEntity $gatewayPayment)
    {
        if (empty($accountDetails[BaseReconciliate::CREDIT_ACCOUNT_NUMBER]) === true)
        {
            return;
        }

        $accountNumber = $accountDetails[BaseReconciliate::CREDIT_ACCOUNT_NUMBER];

        $gatewayPayment->setCreditAccountNumber($accountNumber);
    }

    protected function traceIssuer($reconIssuer, $gatewayFee, $gatewayServiceTax)
    {
        $iinIssuer = $this->paymentIin->getIssuer();

        if (empty($iinIssuer) === true)
        {
            $this->trace->info(
                TraceCode::RECON_INFO,
                [
                    'info_code'           => Base\InfoCode::IIN_ISSUER_ABSENT,
                    'message'             => 'IIN does not contain issuer.',
                    'payment_id'          => $this->payment->getId(),
                    'iin_id'              => $this->paymentIin->getKey(),
                    'recon_issuer'        => $reconIssuer,
                    'iin_issuer'          => $iinIssuer,
                    'amount'              => $this->payment->getAmount(),
                    'gateway_fee'         => $gatewayFee,
                    'gateway_service_tax' => $gatewayServiceTax,
                    'gateway'             => $this->gateway,
                    'batch_id'            => $this->batchId
                ]);
        }
    }

    protected function traceCardTrivia($reconCardTrivia, $gatewayFee, $gatewayServiceTax)
    {
        $iinTrivia = $this->paymentIin->getTrivia();

        if (empty($iinTrivia) === true)
        {
            $this->trace->info(
                TraceCode::RECON_INFO,
                [
                    'info_code'           => Base\InfoCode::IIN_TRIVIA_ABSENT,
                    'message'             => 'IIN does not contain trivia.',
                    'payment_id'          => $this->payment->getId(),
                    'iin_id'              => $this->paymentIin->getKey(),
                    'recon_card_trivia'   => $reconCardTrivia,
                    'iin_card_trivia'     => $iinTrivia,
                    'amount'              => $this->payment->getAmount(),
                    'gateway_fee'         => $gatewayFee,
                    'gateway_service_tax' => $gatewayServiceTax,
                    'gateway'             => $this->gateway,
                    'batch_id'            => $this->batchId,
                ]);
        }
    }

    /**
     * This function should be called only if the payment
     * is sure to have a corresponding entity for card.
     *
     * @param String $reconCardType
     * @throws ReconciliationException
     */
    protected function traceCardType($reconCardType, $gatewayFee, $gatewayServiceTax)
    {
        // Assumption: This function will not be called if IIN is missing.
        // If IIN is missing, it will be created and this function will not be called.

        $iinCardType = $this->paymentIin->getType();
        if ($iinCardType !== $reconCardType)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'             => 'Card types in recon file and db do not match.',
                    'info_code'           => Base\InfoCode::CARD_TYPE_MISMATCH,
                    'recon_card_type'     => $reconCardType,
                    'iin_card_type'       => $iinCardType,
                    'payment_id'          => $this->payment->getId(),
                    'iin_id'              => $this->paymentIin->getKey(),
                    'amount'              => $this->payment->getAmount(),
                    'gateway_fee'         => $gatewayFee,
                    'gateway_service_tax' => $gatewayServiceTax,
                    'gateway'             => $this->gateway,
                    'batch_id'            => $this->batchId,
                ]);
        }
    }

    protected function createMissingIin($reconCardDetails)
    {
        $this->trace->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'message'     => 'IIN absent for the card. Creating.',
                'info_code'   => 'IIN_CREATE',
                'card_id'     => $this->payment->card->getId(),
                'payment_id'  => $this->payment->getId(),
                'gateway'     => $this->gateway,
                'batch_id'    => $this->batchId,
            ]);

        $reconCardType = null;
        $reconCardLocale = null;

        //
        // Card type should always be set to create an IIN. Otherwise,
        // the IIN validator will throw an error.
        //
        $reconCardType = $reconCardDetails[BaseReconciliate::CARD_TYPE];

        if (empty($reconCardDetails[BaseReconciliate::CARD_LOCALE]) === false)
        {
            $reconCardLocale = $reconCardDetails[BaseReconciliate::CARD_LOCALE];
        }

        $card = $this->payment->card;

        $iinId = $card->getIin();
        $cardNetwork = $card->getNetwork();

        // If the reconCardLocale is not set (null), then we set
        // the country code to India.
        if ($reconCardLocale === BaseReconciliate::INTERNATIONAL)
        {
            $countryCode = null;
        }
        else
        {
            $countryCode = 'IN';
        }

        $entityAttributes = [
            IIN\Entity::IIN     => $iinId,
            IIN\Entity::NETWORK => $cardNetwork,
            IIN\Entity::TYPE    => $reconCardType,
            IIN\Entity::COUNTRY => $countryCode,
        ];

        if (empty($reconCardDetails[BaseReconciliate::ISSUER]) === false)
        {
            $entityAttributes[IIN\Entity::ISSUER] = $reconCardDetails[BaseReconciliate::ISSUER];
        }

        if (empty($reconCardDetails[BaseReconciliate::CARD_TRIVIA]) === false)
        {
            $entityAttributes[IIN\Entity::TRIVIA] = $reconCardDetails[BaseReconciliate::CARD_TRIVIA];
        }

        $iin = (new IIN\Entity())->build($entityAttributes);

        $this->repo->saveOrFail($iin);
    }

    protected function traceCardLocale($reconCardLocale, $gatewayFee, $gatewayServiceTax)
    {
        $shouldPersistCardLocale = $this->shouldPersistCardLocale();

        // Assumption: This function will not be called if IIN is missing.
        // If IIN is missing, it will be created and this function will not be called.

        //
        // If $reconCardLocale is not set/is null, we default it to domestic.
        //
        if ($reconCardLocale === BaseReconciliate::INTERNATIONAL)
        {
            $reconInternational = true;
        }
        else
        {
            $reconInternational = false;
        }

        $currentInternational = IIN\IIN::isInternational($this->paymentIin->getCountry(), $this->merchant->getCountry());

        if (($shouldPersistCardLocale === true) and
            ($currentInternational === false) and
            ($reconInternational === true))
        {
            $this->traceCardLocaleMismatch('DB says domestic but recon says international',
                $gatewayFee,
                $gatewayServiceTax);
        }
        else if (($currentInternational === true) and ($reconInternational === false))
        {
            $this->traceCardLocaleMismatch('DB says international but recon says domestic',
                $gatewayFee,
                $gatewayServiceTax);
        }
    }

    /**
     * If we want to update IIN metadata
     * based on current gateway's recon, return true.
     */
    protected function shouldPersistCardLocale(): bool
    {
        return (in_array($this->gateway, self::SKIP_IIN_SAVING_GATEWAYS, true) === false) ?: false;
    }

    /**
     * If reference1 is not already set in DB, set it from recon.
     * If reference1 is already set, then it must be the same as
     * what is present in recon. If it's not the same, and
     * force updated for it is false, raise an alert.
     *
     * @param string $reference1
     */
    protected function setPaymentReference1(string $reference1)
    {
        $dbReference1 = $this->payment->getReference1();

        if ((empty($dbReference1) === false) and
            ($dbReference1 !== $reference1) and
            ($this->shouldForceUpdate(RequestProcessor\Base::PAYMENT_ARN) === false))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference1 is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbReference1,
                    'recon_reference_number'    => $reference1,
                    'gateway'                   => $this->gateway,
                    'batch_id'                  => $this->batchId,
                ]);

            return;
        }

        $this->payment->setReference1($reference1);
    }

    /**
     * If reference2 is not already set in DB, set it from recon.
     * If reference2 is already set, then it must be the same as
     * what is present in recon. If it's not the same, and
     * force updated for it is false, raise an alert.
     *
     * Not already set is defined by either `empty` or `00`.
     * `00` is currently being stored for FirstData.
     *
     * @param string $reference2
     */
    protected function setPaymentReference2(string $reference2)
    {
        $dbReference2 = $this->payment->getReference2();

        $trimmedDbReference2 = ltrim($dbReference2, '0');
        $trimmedReconReference2 = ltrim($reference2, '0');

        if ((empty($dbReference2) === false) and
            ($dbReference2 !== '00') and
            ($dbReference2 !== $reference2) and
            ($trimmedDbReference2 !== $trimmedReconReference2) and
            (strtolower($dbReference2) !== strtolower($reference2)) and
            ($this->shouldForceUpdate(RequestProcessor\Base::PAYMENT_AUTH_CODE) === false))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference2 is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbReference2,
                    'recon_reference_number'    => $reference2,
                    'gateway'                   => $this->gateway,
                    'batch_id'                  => $this->batchId,
                ]);

            return;
        }

        $this->payment->setReference2($reference2);
    }

    protected function setPaymentReference16(string $reference16)
    {
        $dbReference16 = $this->payment->getReference16();

        if ((empty($dbReference16) === false) and ($dbReference16 !== $reference16))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference16 is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbReference16,
                    'recon_reference_number'    => $reference16,
                    'gateway'                   => $this->gateway,
                    'batch_id'                  => $this->batchId,
                ]);
            return;
        }

        $this->payment->setReference16($reference16);
    }


    protected function markGatewayCapturedAsTrue()
    {
        $currentGatewayCaptured = $this->payment->getGatewayCaptured();

        if ($currentGatewayCaptured === true)
        {
            return;
        }

        $this->trace->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'message'           => 'Gateway Captured not set for the payment',
                'info_code'         => Base\InfoCode::GATEWAY_CAPTURED_NOT_SET,
                'payment_id'        => $this->payment->getId(),
                'payment_refunded'  => ($this->payment->getRefundStatus() !== null),
                'gateway'           => $this->gateway,
                'batch_id'          => $this->batchId,
            ]);

        $this->payment->setGatewayCaptured(true);
    }

    public function recordGatewayFeeAndServiceTax($rowDetails)
    {
        $reconGatewayFee = $rowDetails[BaseReconciliate::GATEWAY_FEE];
        $reconGatewayServiceTax = $rowDetails[BaseReconciliate::GATEWAY_SERVICE_TAX];

        $nullTaxAndFeesAllowed = $this->isNullGatewayFeesAndTaxAllowed();

        if ((($reconGatewayFee === null) or ($reconGatewayServiceTax === null)) and
            ($nullTaxAndFeesAllowed === false))
        {
            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::RECON_GATEWAY_FEE_OR_TAX_IS_EMPTY);

            return false;
        }

        if ($this->paymentTransaction === null)
        {
            $createTransactionSuccess = $this->attemptToCreateMissingPaymentTransaction();

            if ($createTransactionSuccess === false)
            {
                $this->trace->info(
                    TraceCode::RECON_FAILURE,
                    [
                        'failure_code'  => Base\InfoCode::PAYMENT_TRANSACTION_ABSENT,
                        'row_details'   => $rowDetails,
                        'gateway'       => $this->gateway,
                        'batch_id'      => $this->batchId,
                    ]);

                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::RECON_RECORD_GATEWAY_FEE_TRANSACTION_ABSENT);

                return false;
            }

            // Refresh both payment and transaction to get latest changes.
            // Reload txn because relation are cached.
            if ($this->payment->isExternal() === true)
            {
                $this->payment = $this->payment->reload();
                $this->paymentTransaction = $this->getPaymentTransaction();
            }
            else
            {
                $this->paymentTransaction = $this->payment->reload()->transaction->reload();
            }

        }

        $currentGatewayFee = $this->paymentTransaction->getGatewayFee();
        $currentGatewayServiceTax = $this->paymentTransaction->getGatewayServiceTax();

        $recordGatewayFeeSuccess = $this->recordGatewayFee($reconGatewayFee, $currentGatewayFee);

        if ($recordGatewayFeeSuccess === true)
        {
            $recordGatewayServiceTaxSuccess = $this->recordGatewayServiceTax($reconGatewayServiceTax,
                                                                             $currentGatewayServiceTax);

            if ($recordGatewayServiceTaxSuccess === true)
            {
                $this->paymentTransaction->saveOrFail();

                if ($this->payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
                {
                    (new ReverseShadowPaymentsCore())->createLedgerEntryForCaptureGatewayCommissionReverseShadow($this->payment, $reconGatewayFee, $reconGatewayServiceTax);
                }

                // commenting this as currently there is issue with kafka flush resulting in increase in batch processing time.
                // Also, this data is not being used for dual comparison right now.
                //$this->createLedgerEntriesForCaptureGatewayCommission($this->payment, $this->paymentTransaction);

                return true;
            }
        }

        $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::RECON_RECORD_GATEWAY_FEE_FAILED);

        return false;
    }

    public function createLedgerEntriesForCaptureGatewayCommission(Payment\Entity $payment, Transaction\Entity $txn)
    {
        if($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false)
        {
            return;
        }

        try
        {
            $transactionMessage = CaptureJournalEvents::createTransactionMessageForCaptureGatewayCommission($payment, $txn);

            \Event::dispatch(new TransactionalClosureEvent(function () use ($txn, $transactionMessage) {
                // Job will be dispatched only if the transaction commits.
                LedgerEntryJob::dispatchNow($this->mode, $transactionMessage);
            }));

            $this->trace->info(
                TraceCode::PAYMENT_CAPTURED_GATEWAY_COMMISSION_EVENT_TRIGGERED,
                [
                    'payment_id'            => $payment->getId(),
                    'message'               => $transactionMessage
                ]);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_LEDGER_ENTRY_FAILED,
                []);
        }
    }

    protected function isNullGatewayFeesAndTaxAllowed()
    {
        if (in_array($this->gateway, self::GATEWAY_FEES_ABSENT_GATEWAYS, true) === true)
        {
            return true;
        }

        return false;
    }

    /*
     * Record gateway fee and service tax for already reconciled
     * payments.
     * HDFC : Because of code bug, fee and service tax of
     * payments reconciled before 7th Nov,17 are not filled.
     *
     * CardFssBob : Due to code bug, fee and service tax of payments
     * reconciled before 15th Oct 18 00:00:00 are filled with incorrect values.
     * so need to record them again with correct values.
     */
    protected function recordMissingGatewayFeeAndServiceTax(array $rowDetails)
    {
        if (in_array($this->gateway, array_keys(self::GATEWAY_FEES_MISSING_GATEWAYS), true) === true)
        {
            // Skipping slack messages here, only want to trace the errors
            $this->messenger->setSkipSlack(true);

            //
            // Check if payment is created after the given date for current gateway, don't proceed
            // Payment must have gateway fee already recorded with correct values
            //
            $paymentMaxCreatedAt = self::GATEWAY_FEES_MISSING_GATEWAYS[$this->gateway];

            if ($this->payment->getCreatedAt() > $paymentMaxCreatedAt)
            {
                return;
            }

            $reconGatewayFee = $rowDetails[BaseReconciliate::GATEWAY_FEE];

            $reconGatewayServiceTax = $rowDetails[BaseReconciliate::GATEWAY_SERVICE_TAX];

            if ($this->paymentTransaction === null)
            {
                $this->trace->warning(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'info_code'     => Base\InfoCode::PAYMENT_TRANSACTION_ABSENT,
                        'row_details'   => $rowDetails,
                        'gateway'       => $this->gateway,
                        'batch_id'      => $this->batchId,
                    ]);

                return;
            }

            $currentGatewayFee = $this->paymentTransaction->getGatewayFee();

            $currentGatewayServiceTax = $this->paymentTransaction->getGatewayServiceTax();

            $recordGatewayFeeSuccess = $this->recordGatewayFee($reconGatewayFee, $currentGatewayFee);

            if ($recordGatewayFeeSuccess === true)
            {
                $recordGatewayServiceTaxSuccess = $this->recordGatewayServiceTax($reconGatewayServiceTax,
                                                                                 $currentGatewayServiceTax);

                if ($recordGatewayServiceTaxSuccess === true)
                {
                    $this->paymentTransaction->saveOrFail();
                }
            }
        }
    }

    protected function attemptToCreateMissingPaymentTransaction()
    {
        $cardNetwork = null;

        $card = $this->payment->card;

        if ($card !== null)
        {
            $cardNetwork = $card->getNetworkCode();
        }

        return $this->mutex->acquireAndRelease(
            $this->payment->getId() . "_" . "transaction",
            function () use ($cardNetwork)
            {
                $isHDFCDICL = ($cardNetwork === Card\Network::DICL) and
                              ($this->payment->isGateway(Payment\Gateway::HDFC) === true);

                $isNotCapturedButAuthorized = ($this->payment->isCaptured() === false) and
                                              ($this->payment->hasBeenAuthorized() === true);

                if (($isHDFCDICL === true) or
                    ($isNotCapturedButAuthorized === true) or
                    ($this->payment->isExternal() === true))
                {
                    try
                    {
                        $this->createMissingPaymentTransaction();

                        return true;
                    }
                    catch (\Exception $ex)
                    {
                        $message = 'Payment transaction create failed with -> ' . $ex->getMessage();

                        $this->trace->info(
                            TraceCode::RECON_FAILURE,
                            [
                                'failure_code' => 'PAYMENT_TRANSACTION_CREATE_FAIL',
                                'message' => $message,
                                'is_hdfc_dicl' => $isHDFCDICL,
                                'is_not_captured_but_authorized' => $isNotCapturedButAuthorized,
                                'payment_id' => $this->payment->getId(),
                                'gateway' => $this->gateway,
                                'batch_id' => $this->batchId,
                            ]);

                        $this->trace->traceException($ex);

                        return false;
                    }
                }

                return false;
            });

        return false;
    }

    protected function createMissingPaymentTransaction()
    {
        assertTrue($this->getPaymentTransaction() === null);

        $this->trace->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'info_code'     => 'PAYMENT_TRANSACTION_CREATE',
                'message'       => 'Attempting to create payment transaction in recon',
                'payment_id'    => $this->payment->getId(),
                'gateway'       => $this->gateway,
                'batch_id'      => $this->batchId,
            ]);

        if (isset($this->merchant) === false )
        {
            $this->merchant = $this->repo->merchant->fetchMerchantFromEntity($this->payment); ;
        }

        $paymentProcessor = new Payment\Processor\Processor($this->merchant);

        //
        // We should always create a transaction if the payment comes in the recon file.
        // This is needed because currently nodal and merchant transactions are tracked via
        // a single transaction entity.
        // If the merchant has not captured the payment, we should create the transaction WITHOUT
        // the fees/service_tax.
        // If the merchant has captured the payment, we should create the transaction WITH fee/service_tax.
        //
        if ($this->payment->hasBeenCaptured() === true)
        {
            if ($this->payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
            {
                list($txn, $feesSplit) = (new Transaction\Core)->createOrUpdateFromPaymentCaptured($this->payment);
            }
            else
            {
                (new ReverseShadowPaymentsCore())->createLedgerEntryForGatewayCaptureReverseShadow($this->payment);

                $discount = $paymentProcessor->getDiscountIfApplicableForLedger($this->payment);

                [$fee, $tax] = (new ReverseShadowPaymentsCore())->createLedgerEntryForMerchantCaptureReverseShadow($this->payment, $discount);

                $this->trace->info(TraceCode::PAYMENT_MERCHANT_CAPTURED_REVERSE_SHADOW, [
                    'payment_id' => $this->payment->getId(),
                    "fee" => $fee,
                    "tax" =>$tax
                ]);
            }
        }
        else
        {
            list($txn, $feesSplit) = (new Transaction\Core)->createFromPaymentAuthorized($this->payment);
        }

        if ($this->payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
        {
            $this->repo->saveOrFail($txn);

            $this->saveFeeDetails($txn, $feesSplit);
        }

        // This is required to save the association of the transaction with the payment.
        $this->repo->saveOrFail($this->payment);

        $paymentProcessor->createLedgerEntriesForGatewayCapture($this->payment);

        if ($this->payment->hasBeenCaptured() === true)
        {
            $paymentProcessor->createLedgerEntriesForMerchantCapture($this->payment, $txn);
        }

        if ($this->payment->isExternal() === true)
        {
           (new Transaction\Core)->dispatchUpdatedTransactionToCPS($txn, $this->payment);
        }
    }

    protected function saveFeeDetails(Transaction\Entity $txn, PublicCollection $feesSplit)
    {
        foreach ($feesSplit as $feeSplit)
        {
            $feeSplit->transaction()->associate($txn);

            $this->repo->saveOrFail($feeSplit);
        }

        $this->trace->info(
            TraceCode::FEES_BREAKUP_CREATED,
            [
                'transaction_id'    => $txn->getId(),
                'payment_id'        => $txn->getEntityId(),
                'fee_split'         => $feesSplit->toArrayPublic(),
            ]);
    }

    protected function recordGatewayFee($reconGatewayFee, $currentGatewayFee)
    {
        if ($currentGatewayFee === 0)
        {
            $this->paymentTransaction->setGatewayFee($reconGatewayFee);
            return true;
        }
        else
        {
            if ($currentGatewayFee !== $reconGatewayFee)
            {
                $this->trace->info(
                    TraceCode::RECON_FAILURE,
                    [
                        'info_code'         => Base\InfoCode::GATEWAY_FEE_MISMATCH,
                        'recon_gateway_fee' => $reconGatewayFee,
                        'api_gateway_fee'   => $currentGatewayFee,
                        'gateway'           => $this->gateway,
                        'batch_id'          => $this->batchId,
                    ]);

                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::GATEWAY_FEE_MISMATCH);

                throw new ReconciliationException(
                    'Gateway fee in the recon file does not match with the one stored in API.',
                    [
                        'recon_gateway_fee' => $reconGatewayFee,
                        'api_gateway_fee'   => $currentGatewayFee,
                    ]
                );

                //return false;
            }

            return true;
        }
    }

    protected function recordGatewayServiceTax($reconGatewayServiceTax, $currentGatewayServiceTax)
    {
        if ($currentGatewayServiceTax === 0)
        {
            $this->paymentTransaction->setGatewayServiceTax($reconGatewayServiceTax);
            return true;
        }
        else
        {
            if ($currentGatewayServiceTax !== $reconGatewayServiceTax)
            {
                $this->trace->info(
                    TraceCode::RECON_FAILURE,
                    [
                        'info_code'                  => Base\InfoCode::GATEWAY_SERVICE_TAX_MISMATCH,
                        'recon_gateway_service_tax'  => $reconGatewayServiceTax,
                        'api_gateway_service_tax'    => $currentGatewayServiceTax,
                        'gateway'                    => $this->gateway,
                        'batch_id'                   => $this->batchId,
                    ]);

                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::GATEWAY_SERVICE_TAX_MISMATCH);

                throw new ReconciliationException(
                    'Gateway service tax in the recon file does not match with the one stored in API.',
                    [
                        'recon_gateway_service_tax' => $reconGatewayServiceTax,
                        'api_gateway_service_tax'   => $currentGatewayServiceTax,
                    ]
                );

                //return false;
            }
            return true;
        }
    }

    /**
     * For wallets and netbanking, there will be no card, hence we
     * send an empty array for these payment methods.
     *
     * @param $row
     * @return array
     */
    protected function getCardDetails($row)
    {
        return [];
    }

    /**
     * If this is being implemented in the child class, ensure that
     * the setter for storing the reference number is present
     * in the gateway entity.
     *
     * @param $row
     * @return null
     */
    protected function getReferenceNumber($row)
    {
        return null;
    }

    /**
     * If this is being implemented in the child class, ensure that
     * the setter for storing the gateway transaction id is present
     * in the gateway entity.
     *
     * @param $row
     * @return null
     */
    protected function getGatewayTransactionId(array $row)
    {
        return null;
    }

    /**
     * If this is being implemented in the child class, ensure that
     * the setter for storing the gateway transaction id is present
     * in the gateway entity.
     *
     * Note: For most gateway it will be same as gateway transaction id
     * but for EBS is it different and required.
     *
     * @param array $row
     */
    protected function getGatewayPaymentId(array $row)
    {
        return null;
    }


    /**
     * If this is being implemented in the child class, ensure that
     * the setter for storing the gateway payment date is present
     * in the gateway entity.
     * @param $row
     * @return null
     */
    protected function getGatewayPaymentDate($row)
    {
        return null;
    }

    /**
     * If this is being implemented in the child class, ensure that
     * the setters for customerId and customerName are present
     * for the gateway entity.
     *
     * @param $row
     * @return null
     */
    protected function getCustomerDetails($row)
    {
        return [];
    }

    /**
     * If this is being implemented in the child class, ensure that
     * the setters for accountNumber and creditAccountNumber are present
     * for the gateway entity.
     *
     * @param $row
     * @return null
     */
    protected function getAccountDetails($row)
    {
        return [];
    }

    /**
     * If present AuthCode will be mapped to Reference2 of Payment
     *
     * @param $row
     * @return null
     */
    protected function getAuthCode($row)
    {
        return null;
    }

    /**
     * If present ARN will be mapped to Reference1 of Payment
     *
     * @param $row
     * @return null
     */
    protected function getArn($row)
    {
        return null;
    }

    /**
     * A few netbanking gateways do not provide us with
     * gateway service tax in their reconciliation files.
     * For them, we mark the gateway service tax as null.
     *
     * @return null
     */
    protected function getGatewayServiceTax($row)
    {
        return null;
    }

    /**
     * A few netbanking gateways do not provide us with
     * gateway fees in their reconciliation files.
     * For them, we mark the gateway fees as null.
     *
     * @return null
     */
    protected function getGatewayFee($row)
    {
        return null;
    }

    /**
     * This function should be implemented in the child class
     * It will fetch the input required to do force authorize
     * on the gateway.
     *
     * @return array
     */
    protected function getInputForForceAuthorize($row)
    {
        return [];
    }

    /**
     * This function should be implemented in the child class if we ALWAYS
     * want to force authorize or NEVER want to force authorize or if there
     * are any custom requirements for force authorization like in nb_icici
     *
     * This base function will be used if we want to force authorize
     * only specific payments. This will be used only for the gateways
     * where force_authorize has been implemented already.
     *
     * @param Payment\Entity $payment
     */
    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        if (in_array($payment->getGateway(), Payment\Gateway::FORCE_AUTHORIZE_GATEWAYS, true) === true)
        {
            $this->allowForceAuthorization = $this->shouldForceAuthorize($payment);
        }
        else
        {
            $this->allowForceAuthorization = false;
        }
    }

    /**
     * Checks if given payment id is in input array of force authorize payments
     *
     * @param PaymentEntity $payment
     *
     * @return bool
     */
    protected function shouldForceAuthorize(Payment\Entity $payment) : bool
    {
        $forceAuthorizePayments = $this->extraDetails
            [RequestProcessor\Base::INPUT_DETAILS]
            [RequestProcessor\Base::FORCE_AUTHORIZE] ?? [];

        return in_array($payment->getPublicId(), $forceAuthorizePayments, true);
    }

    /**
     * Getting the gatewayPayment associated with payment entity.
     * It is implemented in the child class.
     *
     * NOTE: If this is being implemented in the child class,
     * ensure that the relevant setters are implemented in the entity.
     */
    public function getGatewayPayment($paymentId)
    {
        return null;
    }

    /**
     * Checks if amount in recon file matches the actual amount in payment entity
     * Implementation to be provided by child classes
     *
     * @param  array $row Row data
     *
     * @return bool
     */
    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        $reconPaymentAmount = $this->getReconPaymentAmount($row);

        //
        //  If payment amount column is expected in gateway recon but not present in MIS.
        //  this will return false and amount validation fails.
        //
        if ($reconPaymentAmount === false)
        {
            return false;
        }

        //
        // If payment column is not defined for the gateway recon, this will return
        // true. Because that means, either we are not receiving payment amount column in MIS or
        // we do not want to validate amount for this gateway, in such cases, validation
        // always returns true.
        //
        if ($reconPaymentAmount === null)
        {
            return true;
        }

        // To handle multi-currency, get amount/base amount of payment entity
        $paymentEntityAmount = $this->getPaymentEntityAmount();

        if ($paymentEntityAmount !== $reconPaymentAmount)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'        => $this->payment->getId(),
                    'expected_amount'   => $paymentEntityAmount,
                    'recon_amount'      => $reconPaymentAmount,
                    'currency'          => $this->payment->getCurrency(),
                    'gateway'           => $this->gateway,
                    'batch_id'          => $this->batchId,
                ]);

            return false;
        }

        return true;
    }

    /**
     * Checks if currency code in recon file matches the actual currency in payment entity
     * Implementation to be provided by child classes
     *
     * @param  array $row Row data
     *
     * @return bool
     */
    protected function validatePaymentCurrencyEqualsReconCurrency(array $row) : bool
    {
        return true;
    }

    /**
     * The reason that it is implemented this way is because different
     * gateway entities may have different attribute names to store the
     * reference number.
     * So, other gateways can implement this function with the
     * appropriate setter.
     *
     * @param string       $referenceNumber
     * @param PublicEntity $gatewayPayment
     */
    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $dbReferenceNumber = trim($gatewayPayment->getBankPaymentId());

        //
        // Sometimes we have db reference number saved as string 'null'.
        // (we encountered few cases in Atom). We don't want to raise data
        // mismatch alert in such cases. so adding a check to compare
        // string 'null'
        //
        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== 'null') and
            ($dbReferenceNumber !== $referenceNumber))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway,
                    'batch_id'                  => $this->batchId,
                ]);

            return;
        }

        $gatewayPayment->setBankPaymentId($referenceNumber);
    }

    /**
     * The reason that it is implemented this way is because different
     * gateway entities may have different attribute names to store the
     * gateway Transaction ID.
     * So, other gateways can implement this function with the
     * appropriate setter.
     *
     * @param string       $gatewayTransactionId
     * @param PublicEntity $gatewayPayment
     */
    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayPayment)
    {
        $dbGatewayTransactionId = trim($gatewayPayment->getGatewayTransactionId());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'                 => $infoCode,
                    'message'                   => 'Gateway Transaction ID in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway,
                    'batch_id'                  => $this->batchId,
                ]);

            return;
        }

        $gatewayPayment->setGatewayTransactionId($gatewayTransactionId);
    }

    /**
     * For most gateway it will be same as gateway transaction id
     * but for EBS is it different and required.
     *
     *  Other gateways can override this function with the
     * appropriate setter.
     *
     * @param string       $gatewayPaymentId
     * @param PublicEntity $gatewayPayment
     */
    protected function setGatewayPaymentId(string $gatewayPaymentId, PublicEntity $gatewayPayment)
    {
        $dbGatewayPaymentId = trim($gatewayPayment->getGatewayPaymentId());

        if ((empty($dbGatewayPaymentId) === false) and
            ($dbGatewayPaymentId !== $gatewayPaymentId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'                 => $infoCode,
                    'message'                   => 'Gateway Payment Id in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbGatewayPaymentId,
                    'recon_reference_number'    => $gatewayPaymentId,
                    'gateway'                   => $this->gateway,
                    'batch_id'                  => $this->batchId,
                ]);

            return;
        }

        $gatewayPayment->setGatewayPaymentId($gatewayPaymentId);
    }

    /**
     * Sets the given gateway payment date in gateway entity. Gateways storing this value
     * as a different attribute can override this function accordingly.
     *
     * @param string       $gatewayPaymentDate
     * @param PublicEntity $gatewayPayment
     */
    protected function setGatewayPaymentDateInGateway(string $gatewayPaymentDate, PublicEntity $gatewayPayment)
    {
        $gatewayPayment->setDate($gatewayPaymentDate);
    }

    /**
     * Traces and sends slack alert if mismatch in payment IIN found.
     * Will not send slack alert if we are not saving IIN metadata in recon
     */
    protected function traceCardLocaleMismatch($message, $gatewayFee, $gatewayServiceTax)
    {
        $this->trace->info(
            TraceCode::RECON_MISMATCH,
            [
                'info_code'           => Base\InfoCode::CARD_LOCALE_MISMATCH,
                'message'             => $message,
                'payment_id'          => $this->payment->getId(),
                'iin_id'              => $this->paymentIin->getKey(),
                'amount'              => $this->payment->getAmount(),
                'gateway_fee'         => $gatewayFee,
                'gateway_service_tax' => $gatewayServiceTax,
                'gateway'             => $this->gateway,
                'batch_id'            => $this->batchId,
            ]);
    }


    /**
     * Currently being done only for HDFC, as the capture request is getting timeout,
     * so we want to create gateway entity via recon using the MIS row data.
     *
     * It has been overridden in HDFC
     *
     * @param array $row
     */
    protected function createGatewayCapturedEntityIfApplicable(array $row)
    {
        return;
    }

    /**
     * Sometimes the Rrn we receive in MIS, is either more than 12 digits, with
     * extra 0s appended at the left of it, or they are already trimmed so the 0s are
     * removed from left part of string, making it unsearchable in the Upi repo.
     *
     * @param $rrn
     */
    protected function formatUpiRrn(&$rrn)
    {
        if(isset($rrn) === false)
        {
            return;
        }

        $rrn = ltrim($rrn, '0');

        $rrn = str_pad($rrn, 12, '0', STR_PAD_LEFT);
    }

    /**
     * Checks already set paymentEntity is valid and can avoid additional fetch operation
     * if the payment is already set in the context and
     * the passed paymentId should be same as the paymentEntity Id and
     * the shouldPaymentBeReloaded flag is not updated then
     * return payment Entity
     *
     * @param $paymentId
     * @return \RZP\Models\Base\Entity|Payment\Entity
     * @throws \Throwable
     */
    protected function fetchOrGetPaymentById($paymentId)
    {
        if (($this->payment instanceof Payment\Entity) and
            ($this->payment->getId() === $paymentId) and
            ($this->shouldPaymentBeReloaded === false))
        {
            return $this->payment;
        }

        // Now the payment entity is not set or the new unexpected payment might be created,
        // fetch the new paymentEntity by paymentId
        $this->payment = null;// we have to reset the payment to null, so that any undesired payment is not set previously
        $this->payment = $this->paymentRepo->findOrFail($paymentId);
        return $this->payment;

    }
}
