<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\IntegrationException;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Report\Types\BankingInvoiceReport;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Models\Transfer\Service as TransferService;
use RZP\Models\Merchant\Invoice\EInvoice\DocumentTypes;
use RZP\Models\FundAccount\Validation\Entity as FAVEntity;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\Invoice\Constants as InvoiceConstant;

class Processor extends Base\Core
{
    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    protected $merchantId;

    protected $beginTimestamp = 0;

    protected $endTimestamp = 0;

    protected $gstin = null;

    protected $invoiceBreakup;

    protected $month;

    protected $year;

    // this is the Key under which the results are stored in cache this is unique for a merchant per month per year
    protected $cacheTag;

    // this is the key inside a cacheTag as cacheTag contains many queries
    const CACHE_KEY_RESOURCE = 'merchant_invoice_%s_%s_%s_%s_%s_%s';

//    Overall cache structure
//     cache: {
//        cacheTag: [
//                {
//                    cache_key_resource: query result
//                },
//                {
//                    cache_key_resource: query result
////              },
//            ]
//        }

    public $cacheKeyArr = [];

    protected $pgosProxyController;

    const CACHE_TTL = 86400; // 24 hours

    public function __construct(string $merchantId, int $month, int $year, string $cacheTag = '')
    {
        parent::__construct();

        $this->merchantId = $merchantId;

        $this->month = $month;

        $this->year = $year;

        $this->cacheTag = $cacheTag;

        $this->initializeVars();

        $this->pgosProxyController = (new MerchantOnboardingProxyController());
    }

    public function createInvoiceEntities()
    {
        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_REQUEST,
            [
                'merchant_id' => $this->merchantId,
                'month'       => $this->month,
                'year'        => $this->year,
            ]);

        foreach ($this->invoiceBreakup as $balanceId => & $details)
        {
            try
            {
                $existingInvoices = $this->checkInvoiceExists($this->merchantId, $balanceId);

                if ($existingInvoices->count() > 0)
                {
                    $this->trace->info(
                        TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_SKIPPED,
                        [
                            'merchant'    => $this->merchantId,
                            'month'       => $this->month,
                            'year'        => $this->year,
                            'invoice_ids' => $existingInvoices->getIds(),
                        ]);

                    continue;
                }
                /*
                 Merchants applicable for fee based gating initially when the payment will be done will not have entry in balance table.
                 Eventually once they get activated and start doing transactions they will have entry in this table. Hence they will be
                 picked up in the invoice cron during that time.
                 */

                // sum over fees & tax for different commission types
                /** @var Merchant\Balance\Entity $balance */
                $balance = $this->repo->balance->findByIdAndMerchantId($balanceId, $this->merchantId);

                if ($balance->isTypePrimary() === true)
                {
                    // details passed by reference
                    $this->calculateFeesForPrimaryBalance($balanceId, $details);
                }
                else if ($balance->isTypeBanking() === true)
                {
                    // details passed by reference
                    $this->calculateFeesForBankingBalance($balanceId, $details);
                }
                else
                {
                    // We just simply return here so that in case a new balance type
                    // is added, the merchant invoice generation does not fail.
                    return;
                }

                // create entities
                $this->createInvoiceBreakup($balance, $details);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_FAILED,
                    [
                        'merchant_id' => $this->merchant->getId(),
                        'month' => $this->month,
                        'year' => $this->year,
                        'balance_id' => $balanceId,
                    ]);

                $this->trace->count(Metric::MERCHANT_INVOICE_ENTITY_CREATION_FAILED);
             }
        }

        $allInvoicesEligibleForEInvoice = [];
        foreach ($this->invoiceBreakup as $balanceId => & $details)
        {
            $balance = $this->repo->balance->findByIdAndMerchantId($balanceId, $this->merchantId);

            $this->trace->info(
                TraceCode::IRN_NOT_GENERATED_DEBUG_LOGGING,
                [
                    'merchant'    => $this->merchantId,
                    'month'       => $this->month,
                    'year'        => $this->year,
                    'balance'     => $balance,
                    'details'     => $details
                ]);

            if (($balance->isTypeBanking() === true))
            {
                // If invoice not eligible for X
                if($this->checkEligibleLineItems($details) === false)
                {
                    //updating transaction details for balanceId
                    //This is a double check to ensure we have not skipped updating transaction details.
                    try
                    {
                        $invoice = $this->repo->merchant_invoice->fetchBankingInvoiceDataByBalanceIdAndMerchantId(
                                                    $balanceId,
                                                    $this->merchantId,
                                                    $this->month,
                                                    $this->year);

                        foreach ($invoice as $index => $lineItem)
                        {
                            $tax    = $lineItem[Entity::TAX];
                            $amount = $lineItem[Entity::AMOUNT];
                            $type   = $lineItem[Entity::TYPE];

                            $details[$type][Entity::TAX] = $tax;
                            $details[$type][Entity::AMOUNT] = $amount;
                        }

                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->traceException(
                            $e,
                            Trace::CRITICAL,
                            TraceCode::FEE_CALCULATION_FOR_BANKING_BALANCE_FAILED,
                            [
                                'merchant'      => $this->merchantId,
                                'month'         => $this->month,
                                'year'          => $this->year,
                                'balance_id'    => $balanceId,
                                'details'       => $details
                            ]);

                        $this->trace->count(Metric::FEE_CALCULATION_FOR_BANKING_BALANCE_FAILED);
                    }
                }

                // If invoice is eligible for X
                if($this->checkEligibleLineItems($details) === true)
                {
                    try
                    {
                        $this->trace->info(TraceCode::EINVOICE_ELIGIBLE_INVOICE_FOR_X,
                            [
                                'merchant_id' => $this->merchant->getId(),
                                'month' => $this->month,
                                'year' => $this->year,
                                'balance_id' => $balanceId,
                            ]
                        );
                        $xEInvoiceCore = new Merchant\Invoice\EInvoice\XEInvoice;

                        $merchant = $this->repo->merchant->findOrFailPublicWithRelations($this->merchantId, ['merchantDetail']);

                        $date = Carbon::createFromDate($this->year, $this->month, 1, Timezone::IST);

                        $shouldGenerateEInvoice = $xEInvoiceCore->shouldGenerateEInvoice($merchant, $date->getTimestamp());

                        $invoiceCore = new Core;

                        $data = $invoiceCore->getXEInvoiceData($this->month, $this->year, $merchant, $balance);

                        if (($shouldGenerateEInvoice === true))
                        {
                            $mismatchingSellerEntity = $this->isMismatchingSellerEntity($data);

                            if($mismatchingSellerEntity === false)
                            {
                                $this->checkIfCreditNoteAmountGreaterThanInvoiceAmount($data);

                                if(!empty($data[Entity::INVOICE_NUMBER]))
                                {
                                    array_push($allInvoicesEligibleForEInvoice,$data[Entity::INVOICE_NUMBER]);
                                }
                            }
                            else {
                                $this->trace->info(TraceCode::EINVOICE_MISMATCHING_SELLER_FOR_X,
                                    [
                                        'merchant_id' => $this->merchant->getId(),
                                        'month' => $this->month,
                                        'year' => $this->year,
                                    ]
                                );
                            }
                        }
                        else
                        {
                            $this->checkIfCreditNoteAmountGreaterThanInvoiceAmount($data);
                        }
                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::EINVOICE_CREATION_FAILED_FOR_X,
                            [
                                'merchant_id' => $this->merchant->getId(),
                                'year'        => $this->year,
                                'month'       => $this->month,
                            ]);

                        $this->trace->count(Metric::EINVOICE_CREATION_FAILED_FOR_X);
                    }
                }
            }
        }

        /*
         * Merchant Invoice is created at balance level, however Merchant E-Invoice is created at invoice number i.e it
         * contains aggregation of balances, I'm trying to fix the abstraction w/o making too many code changes
         */
        $allInvoicesEligibleForEInvoice = array_unique($allInvoicesEligibleForEInvoice);
        foreach ($allInvoicesEligibleForEInvoice as $invoiceNumber)
        {
            try
            {
                $invoiceCore = new Core;

                $data = $invoiceCore->getXEInvoiceDataViaInvoiceNumber($this->month, $this->year, $invoiceNumber, $merchant);

                $invoiceCore->dispatchForXEInvoice($data, $this->month, $this->year, $merchant->getId());
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::EINVOICE_CREATION_FAILED_FOR_X,
                    [
                        'merchant_id' => $this->merchant->getId(),
                        'year'        => $this->year,
                        'month'       => $this->month,
                    ]);

                $this->trace->count(Metric::EINVOICE_CREATION_FAILED_FOR_X);
            }
        }
    }

    private function checkIfCreditNoteAmountGreaterThanInvoiceAmount($data)
    {
        $invoiceAmount = $data[BankingInvoiceReport::ROWS][DocumentTypes::INV]
        [BankingInvoiceReport::COMBINED][BankingInvoiceReport::GRAND_TOTAL];

        $creditNoteAmount = $data[BankingInvoiceReport::ROWS][DocumentTypes::CRN]
        [BankingInvoiceReport::COMBINED][BankingInvoiceReport::GRAND_TOTAL];

        if($creditNoteAmount > $invoiceAmount)
        {
            $this->trace->info(TraceCode::EINVOICE_CRN_AMOUNT_GREATER_THAN_INV_FOR_X,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'month' => $this->month,
                    'year' => $this->year,
                ]
            );
        }
    }

    protected function checkInvoiceExists(string $merchantId , string $balanceId): Base\PublicCollection
    {
        return $this->repo->merchant_invoice->fetchFeesDataToCheckInvoiceExists($merchantId, $this->month, $this->year ,$balanceId);
    }

    protected function createInvoiceBreakup(Merchant\Balance\Entity $balance, array $details)
    {
        $invoiceBreakup =  $this->repo->transaction(function() use ($balance, $details){

            $feeBearer = $this->merchant->getFeeBearer();

            $amount = 0 ;

            $invoiceBreakup = new Base\PublicCollection;

            foreach ($details as $type => $feeDetails)
            {
                $params = [
                    Entity::MONTH  => $this->month,
                    Entity::YEAR   => $this->year,
                    Entity::TYPE   => $type,
                    Entity::GSTIN  => $this->gstin,
                    Entity::AMOUNT => $feeDetails[Entity::AMOUNT],
                    Entity::TAX    => $feeDetails[Entity::TAX],
                ];

                if (($balance->isTypePrimary() === true) and
                    ($feeBearer === Merchant\FeeBearer::CUSTOMER)) {
                    unset($params[Entity::GSTIN]);

                    $this->app['trace']->info(
                        TraceCode::INVOICE_WITHOUT_GSTIN,
                        [
                            'gstin_no' => $this->gstin,
                            'Merchant_id' => $this->merchantId,
                        ]);
                }

                $amount += $params[Entity::AMOUNT];

                $lineItem = (new Core)->create($params, $this->merchant, $balance);

                $invoiceBreakup->push($lineItem);
            }

            return $invoiceBreakup;
        });

        if($balance->isTypePrimary() === true)
        {
            try
            {
                $adjustmentInvoiceData = $this->repo
                                              ->merchant_invoice
                                              ->fetchInvoiceReportData($this->merchantId, $this->month, $this->year, Type::ADJUSTMENT);

                if ($adjustmentInvoiceData->isEmpty() != true)
                {
                    foreach ($adjustmentInvoiceData as $adjustmentInvoiceDatalineItem)
                    {
                        $invoiceBreakup->push($adjustmentInvoiceDatalineItem);
                    }
                }

                // this is added to avoid the PDF creation if none of the amount is present
                // neither credit_note/debit_note/invoice
               if ($this->hasTaxableAmount($invoiceBreakup) === false)
               {
                   return;
               }

                $pgEInvoiceCore = (new Merchant\Invoice\EInvoice\PgEInvoice());
                $merchant = $this->repo->merchant->findOrFailPublicWithRelations($this->merchantId, ['merchantDetail']);

                $date = Carbon::createFromDate($this->year, $this->month, 1, Timezone::IST);

                if($pgEInvoiceCore->shouldGenerateEInvoice($merchant, $date->getTimestamp()) === true)
                {
                    $invoiceCore = (new Core());
                    [$date, $isGstApplicable, $data] = $invoiceCore->getPgInvoiceData($merchant, $this->month,
                        $this->year, $invoiceBreakup);

                    $invoiceCore->dispatchForPgEInvoice($data, $this->month, $this->year, $merchant->getId());
                }
                else
                {
                    (new PdfGenerator())->generatePgInvoice($this->merchantId, $this->month, $this->year, $invoiceBreakup);
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::MERCHANT_INVOICE_PDF_CREATION_FAILED,
                    [
                        'merchant_id' => $this->merchant->getId(),
                        'year'        => $this->year,
                        'month'       => $this->month,
                    ]);
            }
        }
    }

    public static function hasTaxableAmount($invoiceBreakup) : bool
    {
        foreach ($invoiceBreakup as $index => $entity)
        {
            $amount = abs($entity->getAmount());

            if($amount !== 0)
            {
                return true;
            }
        }

        return false;
    }

    protected function checkEligibleLineItems($invoice) : bool
    {
        foreach ($invoice as $index => $lineItem)
        {
            $tax = $lineItem[Entity::TAX];
            if($tax > 0)
            {
                return true;
            }
        }
        return false;
    }

    protected function calculateFeesForPrimaryBalance($balanceId, array & $details)
    {
        // sum over fees & tax for different commission types
        foreach ($details as $type => $values)
        {
            $details[$type] = $this->calculateFeesForInvoiceByTypeForPrimary($type, $balanceId);
        }
    }

    protected function calculateFeesForBankingBalance(string $balanceId, array & $details)
    {
        // sum over fees & tax for different commission types
        foreach ($details as $type => $values)
        {
            $details[$type] = $this->calculateFeesForInvoiceByTypeForBanking($type, $balanceId);
        }
    }

    protected function calculateFeesForInvoiceByTypeForBanking(string $type, string $balanceId)
    {
        // TODO: Check if the invoice already exists for this combination.

        $formattedFeesForTypeAndBalance = [
            Entity::AMOUNT  => 0,
            Entity::TAX     => 0
        ];

        if ($type === Type::RX_TRANSACTIONS)
        {
            $bankingPayoutsFeeAmount = $this->repo
                ->payout
                ->fetchFeesAndTaxOfPayoutsForGivenBalanceId(
                    $this->merchantId,
                    $balanceId,
                    $this->beginTimestamp,
                    $this->endTimestamp
                );

            $this->logMerchantInvoiceResult(
                $type,
                'banking_invoice_' . $type,
                'banking_payout_fee_amount',
                $bankingPayoutsFeeAmount,
                $balanceId
            );

            $bankingFAVsFeeAmount = $this->repo
                ->fund_account_validation
                ->fetchFeesAndTaxForFAVsForGivenBalanceId(
                    $this->merchantId,
                    $balanceId,
                    $this->beginTimestamp,
                    $this->endTimestamp
                );

            $this->logMerchantInvoiceResult(
                $type,
                'banking_invoice_' . $type,
                'banking_FAV_fee_amount',
                $bankingFAVsFeeAmount,
                $balanceId
            );

            $formattedFeesForTypeAndBalance = $this->formatFeesForBankingInvoiceTransaction($bankingPayoutsFeeAmount,
                $bankingFAVsFeeAmount);
        }

        if ($type === Type::RX_ADJUSTMENTS)
        {
            $bankingFailedPayoutsFeeAmount = $this->repo
                ->payout
                ->fetchFeesAndTaxForFailedPayoutsForGivenBalanceId(
                    $this->merchantId,
                    $balanceId,
                    $this->beginTimestamp,
                    $this->endTimestamp
                );

            $this->logMerchantInvoiceResult(
                $type,
                'banking_invoice_' . $type,
                'banking_failed_payout_fee_amount',
                $bankingFailedPayoutsFeeAmount,
                $balanceId
            );

            $bankingReversalsFeeAmount = $this->repo
                ->reversal
                ->fetchSumOfFeesAndTaxForReversalPayoutsForGivenBalanceId(
                    $this->merchantId,
                    $balanceId,
                    $this->beginTimestamp,
                    $this->endTimestamp);

            $this->logMerchantInvoiceResult(
                $type,
                'banking_invoice_' . $type,
                'banking_reversal_fee_amount',
                $bankingReversalsFeeAmount,
                $balanceId
            );
            $formattedFeesForTypeAndBalance = $this->formatFeesForBankingInvoiceAdjustment($bankingFailedPayoutsFeeAmount,
                $bankingReversalsFeeAmount);
        }

        return $formattedFeesForTypeAndBalance;
    }

    /**
     * Populate the map of Type of Commission with its Amount and Tax values
     *
     * @param string $type
     * @param $balanceId
     *
     * @return array
     * @throws Exception\RuntimeException|Exception\LogicException
     */
    public function calculateFeesForInvoiceByTypeForPrimary(string $type, $balanceId)
    {
        $transactionFeeAmount = [];

        $validationFeeAmount = [];

        $paymentFeeAmount = [];

        $refundFeeAmount = [];

        $refundReversalFeeAmount = [];

        $pricingBundleFeeAmount = [];

        $platformFeeAmount = [];

        $feeBasedGatingAmount = [];

        if ($this->isInvoiceTypeOfPayment($type) === true)
        {
            $cacheKey = $this->getCacheKeyFromTypeAndTableName($type, 'payment');

            // fetching data from cache
            $cacheResult = $this->fetchResultsFromCache($cacheKey);

            if ($cacheResult != null)
            {
                // populating data from cache if it exists
                $paymentFeeAmount = $cacheResult;
            }
            else {
                // else running the query and storing in cache
                $paymentFeeAmount = $this->repo
                    ->payment
                    ->fetchFeesAndTaxForPaymentByType(
                        $this->merchantId,
                        $this->beginTimestamp,
                        $this->endTimestamp,
                        $type);

                $this->storeResultsInCache($cacheKey, $paymentFeeAmount);
            }

            $this->cacheKeyArr[$this->cacheTag][] = $cacheKey;

            $this->logMerchantInvoiceResult(
                $type,
                'pg_invoice_' . $type,
                'payment_fee_amount',
                $paymentFeeAmount,
                $balanceId);
        }

        $paymentAmounts = $this->formatFeesForInvoice($paymentFeeAmount);

        if ($type === Type::VALIDATION)
        {
            $cacheKey = $this->getCacheKeyFromTypeAndTableName($type, 'transaction');

            // fetching data from cache
            $cacheResult = $this->fetchResultsFromCache($cacheKey);

            if ($cacheResult != null)
            {
                // populating data from cache if it exists
                $validationFeeAmount =  $cacheResult;
            }
            else {
                // else running the query and storing in cache
                $validationFeeAmount = $this->repo
                    ->transaction
                    ->fetchFeesAndTaxForPrimaryFundAccountValidations(
                        $this->merchantId,
                        $this->beginTimestamp,
                        $this->endTimestamp);

                $this->storeResultsInCache($cacheKey, $validationFeeAmount);
            }

            $this->cacheKeyArr[$this->cacheTag][] = $cacheKey;

            $this->logMerchantInvoiceResult(
                $type,
                'pg_invoice_' . $type,
                'validation_fee_amount',
                $validationFeeAmount,
                $balanceId);
        }

        $validationAmounts = $this->formatFeesForInvoice($validationFeeAmount);

        if ($type === Type::OTHERS)
        {
            $cacheKey = $this->getCacheKeyFromTypeAndTableName($type, 'transaction');

            // fetching data from cache
            $cacheResult = $this->fetchResultsFromCache($cacheKey);

            if ($cacheResult != null)
            {
                // populating data from cache if it exists
                $transactionFeeAmount =  $cacheResult;
            }
            else {
                // else running the query and storing in cache
                $transactionFeeAmount = $this->repo
                    ->transaction
                    ->fetchFeesAndTaxForTransactions(
                        $this->merchantId,
                        $this->beginTimestamp,
                        $this->endTimestamp);

                $platformFeeDetails = $this->getPlatformFeeDetails();

                $transactionFeeAmount = $this->removePlatformFeeTransferAmountFromFeeDetails($transactionFeeAmount, $platformFeeDetails);

                $this->storeResultsInCache($cacheKey, $transactionFeeAmount);
            }

            $this->cacheKeyArr[$this->cacheTag][] = $cacheKey;

            $this->logMerchantInvoiceResult(
                $type,
                'pg_invoice_' . $type,
                'transaction_fee_amount',
                $transactionFeeAmount,
                $balanceId);
        }

        $transactionAmounts = $this->formatFeesForInvoice($transactionFeeAmount);

        if ($this->isInvoiceTypeOfRefund($type) === true)
        {
            $cacheKey = $this->getCacheKeyFromTypeAndTableName($type, 'transaction');

            // fetching data from cache
            $cacheResult = $this->fetchResultsFromCache($cacheKey);

            if ($cacheResult != null)
            {
                // populating data from cache if it exists
                $refundFeeAmount =  $cacheResult;
            }
            else {
                // else running the query and storing in cache
                $refundFeeAmount = $this->repo
                    ->transaction
                    ->fetchFeesAndTaxForRefundByType(
                        $this->merchantId,
                        $this->beginTimestamp,
                        $this->endTimestamp);

                $this->storeResultsInCache($cacheKey, $refundFeeAmount);
            }

            $this->cacheKeyArr[$this->cacheTag][] = $cacheKey;

            $this->logMerchantInvoiceResult(
                $type,
                'pg_invoice_' . $type,
                'refund_fee_amount',
                $refundFeeAmount,
                $balanceId);
        }

        $refundFeeAmounts = $this->formatFeesForInvoice($refundFeeAmount);

        // Get the reversals as well when generating refund invoice
        // Reversals could have happened due to failure of instant flow, where we charge first and reverse the fee
        // Hence, the cumulative tax value can be negative
        if ($this->isInvoiceTypeOfRefund($type) === true)
        {
            $cacheKey = $this->getCacheKeyFromTypeAndTableName($type, 'reversal');

            // fetching data from cache
            $cacheResult = $this->fetchResultsFromCache($cacheKey);

            if ($cacheResult != null)
            {
                // populating data from cache if it exists
                $refundReversalFeeAmount =  $cacheResult;
            }
            else {
                // else running the query and storing in cache
                $refundReversalFeeAmount = $this->repo
                    ->reversal
                    ->fetchFeesAndTaxForRefundByType(
                        $this->merchantId,
                        $this->beginTimestamp,
                        $this->endTimestamp);

                $this->storeResultsInCache($cacheKey, $refundReversalFeeAmount);
            }

            $this->cacheKeyArr[$this->cacheTag][] = $cacheKey;

            $this->logMerchantInvoiceResult(
                $type,
                'pg_invoice_' . $type,
                'refund_reversal_fee_amount',
                $refundReversalFeeAmount,
                $balanceId);
        }

        $refundReversalFeeAmounts = $this->formatFeesForInvoice($refundReversalFeeAmount);

        if ($type === Type::PRICING_BUNDLE)
        {
            $cacheKey = $this->getCacheKeyFromTypeAndTableName($type, 'growth_service.invoice');

            // fetching data from cache
            $cacheResult = $this->fetchResultsFromCache($cacheKey);

            if ($cacheResult != null)
            {
                $pricingBundleFeeAmount =  $cacheResult;
            }
            else {
                // else running the query and storing in cache
                $pricingBundleFeeAmount = $this->app->growthService->getReceiptForInvoice(['month' => $this->month, 'year' => $this->year, 'merchant_id' => $this->merchantId]);

                $this->storeResultsInCache($cacheKey, $pricingBundleFeeAmount);
            }

            $this->cacheKeyArr[$this->cacheTag][] = $cacheKey;

            $this->logMerchantInvoiceResult(
                $type,
                'pg_invoice_' . $type,
                'pricing_bundle_fee_amount',
                $pricingBundleFeeAmount,
                $balanceId);
        }

        if ($type === Type::FEE_BASED_GATING)
        {
            $merchantId = $this->merchantId;

            $this->trace->info(TraceCode::FEE_BASED_GATING_INVOICE_GENERATION, [
                'merchant_id' => $this->merchantId,
                'fee_type'    => $type
            ]);

            $isEligibleForInvoicing = false;

            // need to check where the cache key is getting set

            $cacheKey = $this->getCacheKeyFromTypeAndTableName($type, 'transaction');

            // fetching data from cache
            $cacheResult = $this->fetchResultsFromCache($cacheKey);

            // data will be stored in cache for 24 hours

            if ($cacheResult != null)
            {
                $feeBasedGatingAmount = $cacheResult;
            }
            else
            {
                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $feeBasedGatingResponse = (new Merchant\Detail\Core())->fetchMerchantGatingDetails($merchant);

                if (isset($feeBasedGatingResponse[DetailConstants::FEE_BASED_GATING]) === true)
                {
                    // The keys in the fee based gating will be always there but just empty if it is not filled

                    $feeBasedGatingEligibility = $feeBasedGatingResponse[DetailConstants::FEE_BASED_GATING][DetailConstants::IS_ELIGIBLE];

                    $paymentStatus = $feeBasedGatingResponse[DetailConstants::FEE_BASED_GATING][DetailConstants::PAYMENT_STATUS];

                    $invoiceStatus = $feeBasedGatingResponse[DetailConstants::FEE_BASED_GATING][DetailConstants::INVOICE_SENT] ?? false;

                    // need to put check here if invoice is already sent should not be sent to same merchant id twice

                    if ($feeBasedGatingEligibility === true and $paymentStatus === Status::CAPTURED and $invoiceStatus === false)
                    {
                        $isEligibleForInvoicing = true;
                    }

                    $this->trace->info(TraceCode::FEE_BASED_GATING_INVOICE_GENERATION, [
                        'merchant_id'            => $merchantId,
                        'isEligibleForInvoicing' => $isEligibleForInvoicing,
                    ]);
                }
                /*
                 If the cron attempts fail what will happen to the downstream services call ?
                 */

                if ($isEligibleForInvoicing === true)
                {
                    $saveInvoiceLogicResponse = $this->saveMerchantInvoiceDataInPGOS($merchant, true);

                    if ($saveInvoiceLogicResponse !== true)
                    {
                        throw new IntegrationException("Invoice Save Logic Failed", ErrorCode::SERVER_ERROR_PGOS_PROCESSNG_FAILED);
                    }
                }

                $feeBasedGatingAmount = [
                    Entity::AMOUNT => $isEligibleForInvoicing ? InvoiceConstant::FEE_BASED_GATING_BASE_AMOUNT  : 0,
                    Entity::TAX    => $isEligibleForInvoicing ? InvoiceConstant::FEE_BASED_GATING_TAX_AMOUNT : 0,
                ];

                $this->storeResultsInCache($cacheKey, $feeBasedGatingAmount);
            }

            $this->cacheKeyArr[$this->cacheTag][] = $cacheKey;

            $this->trace->info(TraceCode::FEE_BASED_GATING_INVOICE_GENERATION, [
                'merchant_id'             => $this->merchantId,
                'fee_based_gating_amount' => $feeBasedGatingAmount,
            ]);

            $this->logMerchantInvoiceResult(
                $type,
                'pg_invoice_' . $type,
                'fee_based_gating_amount',
                $feeBasedGatingAmount,
                $balanceId);
        }

        if ($type === Type::PLATFORM_FEE)
        {
            $platformFeeDetails = $this->getPlatformFeeDetails();

            $platformFeeAmount = [
                Entity::AMOUNT  => $platformFeeDetails[Entity::AMOUNT] ?? 0,
                Entity::TAX     => $platformFeeDetails[Entity::TAX] ?? 0,
            ];

            $cacheKey = $this->getCacheKeyFromTypeAndTableName($type, 'transaction');

            $this->cacheKeyArr[$this->cacheTag][] = $cacheKey;

            $this->logMerchantInvoiceResult($type, 'pg_invoice_' . $type, 'platform_fee_amount', $platformFeeAmount, $balanceId);
        }

        $amount  = $paymentAmounts[Entity::AMOUNT] + $transactionAmounts[Entity::AMOUNT]
                    + $validationAmounts[Entity::AMOUNT] + $refundFeeAmounts[Entity::AMOUNT] + $pricingBundleFeeAmount[Entity::AMOUNT] + $feeBasedGatingAmount[Entity::AMOUNT]
                    - $refundReversalFeeAmounts[Entity::AMOUNT] + $platformFeeAmount[Entity::AMOUNT];

        // The Finance come up with the requirement that we should have the merchant Invoice to be GST compliant
        // That mean they want the Tax should always be equal to 18% of the fees(amount) that we charge from the Merchant
        if(in_array($type, Type::$taxablePrimaryCommissionTypes, true) === true)
        {
            return [
                // This is to round the TAX as per the GST Compliance i.e. Normal rounding (PHP_ROUND_HALF_UP)
                Entity::TAX     => (int) round($amount * Constants::GST_PERCENTAGE),
                Entity::AMOUNT  => $amount,
            ];
        }
        else
        {
            return [
                Entity::TAX    => 0,
                Entity::AMOUNT => $amount,
            ];
        }
    }

    protected function logMerchantInvoiceResult($item, $type, $step, $result, $balanceId)
    {
        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_QUERY_RESULT,
            [
                'merchant_id' => $this->merchantId,
                'type'        => $type,
                'step'        => $step,
                'balance_id'  => $balanceId,
                'result'      => $result,
            ]);

        //throw exception if query returns null
        if((empty($result) === true) and ($this->isPGInvoiceType($item) === true))
        {
            $errorData = [
                'merchant_id' => $this->merchantId,
                'type'        => $type,
                'step'        => $step,
                'balance_id'  => $balanceId,
            ];
            $errorMessage = 'Query returns null';
            //send slack alert
            (new SlackNotification)->send($errorMessage, $errorData, null, null, Entity::P0_PP_ALERTS);

            throw new Exception\RuntimeException('Query returns null', $errorData);
        }
    }

    protected function isInvoiceTypeOfPayment(string $type)
    {
        return (in_array($type, [Type::CARD_GT_2K, Type::CARD_LTE_2K, Type::OTHERS], true) === true);
    }

    protected function isInvoiceTypeOfRefund(string $type)
    {
        return (in_array($type, [Type::INSTANT_REFUNDS], true) === true);
    }

    protected function isPGInvoiceType(string $type)
    {
        return (in_array($type, Type::getAllPrimaryBalanceTypes()) === true);
    }

    /**
     * Formats the invoice fee result in generalized format
     *
     * @param $feeDetails
     * @return array
     * [
     *  'amount' => 0,
     *  'tax'    => 0,
     * ]
     */
    protected function formatFeesForInvoice($feeDetails): array
    {
        if (empty($feeDetails) === true)
        {
            return [
                Entity::TAX    => 0,
                Entity::AMOUNT => 0,
            ];
        }

        $details = $feeDetails->getAttributes();

        $fees = $details['fee'];

        $tax = $details['tax'];

        return [
            Entity::TAX    => $tax,
            Entity::AMOUNT => $fees - $tax,
        ];
    }


    /**
     * Formats the invoice fee result in generalized format
     *
     * @param PayoutEntity   $bankingPayoutsFeeDetails
     * @param PayoutEntity   $bankingFailedPayoutsFeeDetails
     * @param FAVEntity      $bankingFAVsFeeDetails
     * @param ReversalEntity $bankingReversalsFeeDetails
     *
     * @return array
     */
    protected function formatFeesForBankingInvoiceTransaction(PayoutEntity $bankingPayoutsFeeDetails,
                                                              FAVEntity $bankingFAVsFeeDetails)
    {
        if ((empty($bankingPayoutsFeeDetails) === true) and
            (empty($bankingFAVsFeeDetails) === true))
        {
            return [
                Entity::TAX     => 0,
                Entity::AMOUNT  => 0
            ];
        }

        $bankingPayoutsFeeAmount       = $bankingPayoutsFeeDetails->getAttributes();
        $bankingFAVsFeeAmount          = $bankingFAVsFeeDetails->getAttributes();

        $bankingPayoutsFees = $bankingPayoutsFeeAmount['fee'];
        $bankingPayoutsTax  = $bankingPayoutsFeeAmount['tax'];


        $bankingFAVsFees = $bankingFAVsFeeAmount['fee'];
        $bankingFAVsTax  = $bankingFAVsFeeAmount['tax'];

        // The Finance come up with the requirement that we should have the merchant Invoice to be GST compliant
        // That mean they want the Tax should always be equal to 18% of the fees(amount) that we
        // charge from the Merchant

        $amount = ($bankingPayoutsFees - $bankingPayoutsTax) + ($bankingFAVsFees - $bankingFAVsTax);
        $tax = $bankingPayoutsTax + $bankingFAVsTax;

        return [
            Entity::TAX     => $tax, // Updating to remove the mismatch in tax invoice and fee deducted
            Entity::AMOUNT  => $amount
        ];
    }

    protected function formatFeesForBankingInvoiceAdjustment(PayoutEntity $bankingFailedPayoutsFeeDetails,
                                                             ReversalEntity $bankingReversalsFeeDetails)
    {
        if ((empty($bankingFailedPayoutsFeeDetails) === true) and
            (empty($bankingReversalsFeeDetails) === true))
        {
            return [
                Entity::TAX     => 0,
                Entity::AMOUNT  => 0
            ];
        }

        $bankingFailedPayoutsFeeAmount = $bankingFailedPayoutsFeeDetails->getAttributes();
        $bankingReversalsFeeAmount     = $bankingReversalsFeeDetails->getAttributes();

        $bankingFailedPayoutsFees = $bankingFailedPayoutsFeeAmount['fee'];
        $bankingFailedPayoutsTax  = $bankingFailedPayoutsFeeAmount['tax'];

        $reversalFees = $bankingReversalsFeeAmount['fee'];
        $reversalTax  = $bankingReversalsFeeAmount['tax'];

        // The Finance come up with the requirement that we should have the merchant Invoice to be GST compliant
        // That mean they want the Tax should always be equal to 18% of the fees(amount) that we
        // charge from the Merchant

        $amount = ($reversalFees - $reversalTax) + ($bankingFailedPayoutsFees - $bankingFailedPayoutsTax);
        $tax = $reversalTax + $bankingFailedPayoutsTax;

        return [
            Entity::TAX     => $tax, // Updating to remove the mismatch in tax invoice and fee deducted
            Entity::AMOUNT  => $amount
        ];
    }

    protected function initializeVars()
    {
        $this->merchant = $this->repo->merchant->findOrFailPublicWithRelations($this->merchantId, ['merchantDetail']);

        $beginDate = Carbon::createFromDate($this->year, $this->month, 1, Timezone::IST);

        $this->beginTimestamp = $this->getPatchedFirstDay($this->month, $this->year)->timestamp;

        $this->endTimestamp = $this->getPatchedLastDay($this->month, $this->year)->timestamp;

        // Get GSTIN
        $this->gstin = $this->merchant->getGstin();

        // Initialize commission values
        $this->invoiceBreakup = [];

        $balances = $this->merchant->balances;

        if ($balances->count() === 0)
        {
            return;
        }

        foreach ($balances as $balance)
        {
            if (($balance->isTypePrimary() === true) and ($this->merchant->isActivated() === true))
            {
                // [
                //    'card_lte_2k'             => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                //    'card_gt_2k'              => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                //    'instant_refunds'         => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                //    'others'                  => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                //    'validation'              => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                //    'pricing_bundle'          => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                //    'fee_based_gating'        => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                //    'platform_fee'            => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                // ]

                $primaryCommissionTypes = Type::getAllPrimaryBalanceTypes();

                foreach ($primaryCommissionTypes as $type)
                {
                    $this->invoiceBreakup[$balance->getId()][$type] = [
                        Entity::AMOUNT          => 0,
                        Entity::TAX             => 0,
                        Entity::AMOUNT_DUE      => 0,
                    ];
                }
            }
            else if ($balance->isTypeBanking() === true)
            {
                $bankingCommissionTypes = Type::getAllBankingBalanceTypes();

                foreach ($bankingCommissionTypes as $type)
                {
                    $this->invoiceBreakup[$balance->getId()][$type] = [
                        Entity::TAX             => 0,
                        Entity::AMOUNT          => 0,
                    ];
                }
            }
        }
    }

    protected function getPlatformFeeDetails(): ?array
    {
        $cacheKey = $this->getCacheKeyFromTypeAndTableName(Type::PLATFORM_FEE, 'transaction');

        $cacheResult = $this->fetchResultsFromCache($cacheKey);

        if ($cacheResult != null)
        {
            $platformFeeDetails = $cacheResult;
        }
        else
        {
            $platformFeeDetails = (new TransferService())->getPlatformFeeDetailsForMerchant($this->merchantId, $this->month, $this->year, $this->beginTimestamp, $this->endTimestamp);

            $this->storeResultsInCache($cacheKey, $platformFeeDetails);
        }

        return $platformFeeDetails;
    }

    private function getPatchedFirstDay($month, $year)
    {
        $date = Carbon::createFromDate($year, $month, 1, Timezone::IST);

        if ($month == 01 and $year == 2021) {
            return $date->firstOfMonth()->subDays(1)->startOfDay();
        }
        else {
            return $date->startOfMonth();
        }
    }

    private function getPatchedLastDay($month, $year)
    {
        $date = Carbon::createFromDate($year, $month, 1, Timezone::IST);
        if ($month == 12 and $year == 2020) {
            return $date->lastOfMonth()->subDays(1)->endOfDay();
        }
        else {
            return $date->endOfMonth();
        }
    }

    private function isMismatchingSellerEntity($invoiceData)
    {
        foreach($invoiceData[BankingInvoiceReport::ROWS] as $type => $lineItem)
        {
            $accounts = array_except($lineItem, BankingInvoiceReport::COMBINED);

            $virtualAccountInvoiceAmount = 0;
            $rblAccountInvoiceAmount = 0;

            foreach ($accounts as $account => $attributes)
            {
                if($attributes[BankingInvoiceReport::ACCOUNT_TYPE] === 'shared')
                {
                    if($attributes[BankingInvoiceReport::AMOUNT] > $virtualAccountInvoiceAmount)
                    {
                        $virtualAccountInvoiceAmount = $attributes[Entity::AMOUNT];
                    }
                }
                else if($attributes[BankingInvoiceReport::ACCOUNT_TYPE] === 'direct'
                    and $attributes[BankingInvoiceReport::CHANNEL] === 'rbl')
                {
                    if($attributes[BankingInvoiceReport::AMOUNT] > $rblAccountInvoiceAmount)
                    {
                        $rblAccountInvoiceAmount = $attributes[Entity::AMOUNT];
                    }
                }
            }

            if($rblAccountInvoiceAmount > 0 and $virtualAccountInvoiceAmount > 0)
            {
                return true;
            }
        }
        return false;
    }

    // remove platform fee transfer amount from the fee details (trxn, payment etc.) to avoid double
    // calculation since there is already a separate line item for platform fee transfer in the invoice
    private function removePlatformFeeTransferAmountFromFeeDetails($feeDetails, $platformFeeDetails)
    {
        if (empty($feeDetails) === true or empty($platformFeeDetails) === true)
        {
            return $feeDetails;
        }

        $details = $feeDetails->getAttributes();

        $fees = $details['fee'] - ($platformFeeDetails['transfer_details']['fee'] ?? 0);

        $tax = $details['tax'] - ($platformFeeDetails['transfer_details']['tax'] ?? 0);

        // ideally this is not possible but to avoid negative fee & tax amount, don't update the attributes
        if ($fees < 0 or $tax < 0)
        {
            $this->trace->info(
                TraceCode::MERCHANT_MONTHLY_INVOICE_NEGATIVE_FEE_DETAILS,
                [
                    'overall_trxn_fee'  => $details['fee'],
                    'overall_trxn_tax'  => $details['tax'],
                    'updated_fee'       => $fees,
                    'updated_tax'       => $tax
                ]
            );

            return $feeDetails;
        }

        $feeDetails->setAttribute('fee', $fees);

        $feeDetails->setAttribute('tax', $tax);

        return $feeDetails;
    }

    private function getCacheKeyFromTypeAndTableName(string $type, string $tableName): string
    {
        // cacheKey will look like merchant_invoice_{mode}_{mid}_{month}_{year}_{type}_{table_name}
        return sprintf(self::CACHE_KEY_RESOURCE, $this->mode, $this->merchantId, $this->month, $this->year, $type, $tableName);
    }

    public function fetchResultsFromCache(string $cacheKey)
    {
        return $this->app['cache']->tags($this->cacheTag)->get($cacheKey);
    }

    public function storeResultsInCache(string $cacheKey, $result)
    {
        $this->app['cache']->tags($this->cacheTag)->put($cacheKey, $result, self::CACHE_TTL);
    }

    public function getCacheKeyArray()
    {
        return $this->cacheKeyArr;
    }
    protected function saveMerchantInvoiceDataInPGOS(Merchant\Entity $merchant, bool $isInvoiceSent)
    {

        // This route will just called from here and will be only called when we have to save the invoice logic, this should
        // not fail

        $merchantId = $merchant->getId();

        try
        {
            $payload =
                [
                    DetailConstants::MERCHANT_ID  => $merchantId,
                    DetailConstants::INVOICE_SENT => $isInvoiceSent
                ];

            // response will be a boolean value - success -> true or false

            $response = $this->pgosProxyController->handlePGOSProxyRequests('merchant_invoice_logic_save',
                                                                            $payload, $merchant, true);

            $this->trace->info(TraceCode::PGOS_INVOICE_LOGIC_RESPONSE, [
                'merchant_id' => $merchantId,
                'response'    => $response,
            ]);

            if (isset($response[Constant::SUCCESS]) and $response[Constant::SUCCESS] === true)
            {
                return true;
            }

            return false;
        }
        catch (\Throwable $exception)
        {
            $this->trace->error(TraceCode::PGOS_INVOICE_LOGIC_SAVE_FAILURE, [
                'merchant_id'   => $merchantId,
                'error_message' => $exception->getMessage()
            ]);

            return false;
        }
    }
}
