<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Report\Types\BankingInvoiceReport;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Models\Merchant\Invoice\EInvoice\DocumentTypes;
use RZP\Models\FundAccount\Validation\Entity as FAVEntity;

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

    const CACHE_TTL = 86400; // 24 hours

    public function __construct(string $merchantId, int $month, int $year, string $cacheTag = '')
    {
        parent::__construct();

        $this->merchantId = $merchantId;

        $this->month = $month;

        $this->year = $year;

        $this->cacheTag = $cacheTag;

        $this->initializeVars();
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

                        if (($shouldGenerateEInvoice === true))
                        {
                            $invoiceCore = new Core;

                            $data = $invoiceCore->getXEInvoiceData($this->month, $this->year, $merchant);

                            $mismatchingSellerEntity = $this->isMismatchingSellerEntity($data);

                            if($mismatchingSellerEntity === false)
                            {
                                $this->checkIfCreditNoteAmountGreaterThanInvoiceAmount($data);

                                $invoiceCore->dispatchForXEInvoice($data, $this->month, $this->year, $merchant->getId());
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
                            $invoiceCore = new Core;
                            $data = $invoiceCore->getXEInvoiceData($this->month, $this->year, $merchant);
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
                    break;
                }
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
     */
    public function calculateFeesForInvoiceByTypeForPrimary(string $type, $balanceId)
    {
        $transactionFeeAmount = [];

        $validationFeeAmount = [];

        $paymentFeeAmount = [];

        $refundFeeAmount = [];

        $refundReversalFeeAmount = [];

        $pricingBundleFeeAmount = [];

        if ($this->isInvoiceTypeOfPayment($type) === true)
        {
            // creating the cacheKey
            // cacheKey will look like merchant_invoice_{mode}_{mid}_{month}_{year}_{type}_{table_name}
            $cacheKey = sprintf(
                self::CACHE_KEY_RESOURCE, $this->mode, $this->merchantId, $this->month, $this->year, $type, 'payment');

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
            // creating the cacheKey
            // cacheKey will look like merchant_invoice_{mode}_{mid}_{month}_{year}_{type}_{table_name}
            $cacheKey = sprintf(
                self::CACHE_KEY_RESOURCE, $this->mode, $this->merchantId, $this->month, $this->year, $type, 'transaction');

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
            // creating the cacheKey
            // cacheKey will look like merchant_invoice_{mode}_{mid}_{month}_{year}_{type}_{table_name}
            $cacheKey = sprintf(
                self::CACHE_KEY_RESOURCE, $this->mode, $this->merchantId, $this->month, $this->year, $type, 'transaction');

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
            // creating the cacheKey
            // cacheKey will look like merchant_invoice_{mode}_{mid}_{month}_{year}_{type}_{table_name}
            $cacheKey = sprintf(
                self::CACHE_KEY_RESOURCE, $this->mode, $this->merchantId, $this->month, $this->year, $type, 'transaction');

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
            // creating the cacheKey
            // cacheKey will look like merchant_invoice_{mode}_{mid}_{month}_{year}_{type}_{table_name}
            $cacheKey = sprintf(
                self::CACHE_KEY_RESOURCE, $this->mode, $this->merchantId, $this->month, $this->year, $type, 'reversal');

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
            // creating the cacheKey
            // cacheKey will look like merchant_invoice_{mode}_{mid}_{month}_{year}_{type}_{table_name}
            $cacheKey = sprintf(
                self::CACHE_KEY_RESOURCE, $this->mode, $this->merchantId, $this->month, $this->year, $type, 'growth_service.invoice');

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

        $amount  = $paymentAmounts[Entity::AMOUNT] + $transactionAmounts[Entity::AMOUNT]
                    + $validationAmounts[Entity::AMOUNT] + $refundFeeAmounts[Entity::AMOUNT] + $pricingBundleFeeAmount[Entity::AMOUNT]
                    - $refundReversalFeeAmounts[Entity::AMOUNT];

        // The Finance come up with the requirement that we should have the merchant Invoice to be GST compliant
        // That mean they want the Tax should always be equal to 18% of the fees(amount) that we charge from the Merchant
        if(in_array($type, Type::$taxablePrimaryCommissionTypes, true) === true)
        {
            return [
                // This is to round the TAX as per the GST Compliance i.e Normal rounding (PHP_ROUND_HALF_UP)
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

        return [
            // This is to round the TAX as per the GST Compliance i.e Normal rounding (PHP_ROUND_HALF_UP)
            Entity::TAX     => (int) round($amount * Constants::GST_PERCENTAGE),
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

        return [
            // This is to round the TAX as per the GST Compliance i.e Normal rounding (PHP_ROUND_HALF_UP)
            Entity::TAX     => (int) round($amount * Constants::GST_PERCENTAGE),
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

}
