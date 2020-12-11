<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Reversal\Entity as ReversalEntity;
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

    public function __construct(string $merchantId, int $month, int $year)
    {
        parent::__construct();

        $this->merchantId = $merchantId;

        $this->month = $month;

        $this->year = $year;

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
             }
        }
    }

    protected function checkInvoiceExists(string $merchantId , string $balanceId): Base\PublicCollection
    {
        return $this->repo->merchant_invoice->fetchFeesDataToCheckInvoiceExists($merchantId, $this->month, $this->year ,$balanceId);
    }

    protected function createInvoiceBreakup(Merchant\Balance\Entity $balance, array $details)
    {
        [$invoiceBreakup, $generatePdf] =  $this->repo->transaction(function() use ($balance, $details){

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

            if($amount > 0)
            {
                return [$invoiceBreakup, true];
            }

            return [$invoiceBreakup, false];
        });

        if(($generatePdf === true) and ($balance->isTypePrimary() === true))
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

                (new PdfGenerator())->generatePgInvoice($this->merchantId, $this->month, $this->year, $invoiceBreakup);
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
                'banking_invoice_' . $type,
                'banking_payout_fee_amount',
                $bankingPayoutsFeeAmount,
                $balanceId
            );

            $bankingFailedPayoutsFeeAmount = $this->repo
                                                  ->payout
                                                  ->fetchFeesAndTaxForFailedPayoutsForGivenBalanceId(
                                                      $this->merchantId,
                                                      $balanceId,
                                                      $this->beginTimestamp,
                                                      $this->endTimestamp
                                                  );

            $this->logMerchantInvoiceResult(
                'banking_invoice_' . $type,
                'banking_failed_payout_fee_amount',
                $bankingFailedPayoutsFeeAmount,
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
                'banking_invoice_' . $type,
                'banking_FAV_fee_amount',
                $bankingFAVsFeeAmount,
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
                'banking_invoice_' . $type,
                'banking_reversal_fee_amount',
                $bankingReversalsFeeAmount,
                $balanceId
            );

            $formattedFeesForTypeAndBalance = $this->formatFeesForBankingInvoice($bankingPayoutsFeeAmount,
                                                                                 $bankingFailedPayoutsFeeAmount,
                                                                                 $bankingFAVsFeeAmount,
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

        if ($this->isInvoiceTypeOfPayment($type) === true)
        {
            $paymentFeeAmount = $this->repo
                                     ->payment
                                     ->fetchFeesAndTaxForPaymentByType(
                                         $this->merchantId,
                                         $this->beginTimestamp,
                                         $this->endTimestamp,
                                         $type);

            $this->logMerchantInvoiceResult(
                'pg_invoice_' . $type,
                'payment_fee_amount',
                $paymentFeeAmount,
                $balanceId);
        }

        $paymentAmounts = $this->formatFeesForInvoice($paymentFeeAmount);

        if ($type === Type::VALIDATION)
        {
            $validationFeeAmount = $this->repo
                                        ->transaction
                                        ->fetchFeesAndTaxForPrimaryFundAccountValidations(
                                            $this->merchantId,
                                            $this->beginTimestamp,
                                            $this->endTimestamp);

            $this->logMerchantInvoiceResult(
                'pg_invoice_' . $type,
                'validation_fee_amount',
                $validationFeeAmount,
                $balanceId);
        }

        $validationAmounts = $this->formatFeesForInvoice($validationFeeAmount);

        if ($type === Type::OTHERS)
        {
            $transactionFeeAmount = $this->repo
                                         ->transaction
                                         ->fetchFeesAndTaxForTransactions(
                                             $this->merchantId,
                                             $this->beginTimestamp,
                                             $this->endTimestamp);

            $this->logMerchantInvoiceResult(
                'pg_invoice_' . $type,
                'transaction_fee_amount',
                $transactionFeeAmount,
                $balanceId);
        }

        $transactionAmounts = $this->formatFeesForInvoice($transactionFeeAmount);

        if ($this->isInvoiceTypeOfRefund($type) === true)
        {
            $refundFeeAmount = $this->repo
                                    ->transaction
                                    ->fetchFeesAndTaxForRefundByType(
                                        $this->merchantId,
                                        $this->beginTimestamp,
                                        $this->endTimestamp);

            $this->logMerchantInvoiceResult(
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
            $refundReversalFeeAmount = $this->repo
                                            ->reversal
                                            ->fetchFeesAndTaxForRefundByType(
                                                $this->merchantId,
                                                $this->beginTimestamp,
                                                $this->endTimestamp);

            $this->logMerchantInvoiceResult(
                'pg_invoice_' . $type,
                'refund_reversal_fee_amount',
                $refundReversalFeeAmount,
                $balanceId);
        }

        $refundReversalFeeAmounts = $this->formatFeesForInvoice($refundReversalFeeAmount);

        $amount  = $paymentAmounts[Entity::AMOUNT] + $transactionAmounts[Entity::AMOUNT]
                    + $validationAmounts[Entity::AMOUNT] + $refundFeeAmounts[Entity::AMOUNT]
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

    protected function logMerchantInvoiceResult($type, $step, $result, $balanceId)
    {
        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_QUERY_RESULT,
            [
                'merchant_id' => $this->merchantId,
                'type'        => $type,
                'step'        => $step,
                'balance_id'  => $balanceId,
                'result'      => empty($result) === false ? $result->getAttributes() : $result,
            ]);
    }

    protected function isInvoiceTypeOfPayment(string $type)
    {
        return (in_array($type, [Type::CARD_GT_2K, Type::CARD_LTE_2K, Type::OTHERS], true) === true);
    }

    protected function isInvoiceTypeOfRefund(string $type)
    {
        return (in_array($type, [Type::INSTANT_REFUNDS], true) === true);
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
    protected function formatFeesForBankingInvoice(PayoutEntity $bankingPayoutsFeeDetails,
                                                   PayoutEntity $bankingFailedPayoutsFeeDetails,
                                                   FAVEntity $bankingFAVsFeeDetails,
                                                   ReversalEntity $bankingReversalsFeeDetails)
    {
        if ((empty($bankingPayoutsFeeDetails) === true) and
            (empty($bankingFailedPayoutsFeeDetails) === true) and
            (empty($bankingFAVsFeeDetails) === true) and
            (empty($bankingReversalsFeeDetails) === true))
        {
            return [
                Entity::TAX     => 0,
                Entity::AMOUNT  => 0
            ];
        }

        $bankingPayoutsFeeAmount       = $bankingPayoutsFeeDetails->getAttributes();
        $bankingFailedPayoutsFeeAmount = $bankingFailedPayoutsFeeDetails->getAttributes();
        $bankingFAVsFeeAmount          = $bankingFAVsFeeDetails->getAttributes();
        $bankingReversalsFeeAmount     = $bankingReversalsFeeDetails->getAttributes();

        $bankingPayoutsFees = $bankingPayoutsFeeAmount['fee'];
        $bankingPayoutsTax  = $bankingPayoutsFeeAmount['tax'];

        $bankingFailedPayoutsFees = $bankingFailedPayoutsFeeAmount['fee'];
        $bankingFailedPayoutsTax  = $bankingFailedPayoutsFeeAmount['tax'];

        $bankingFAVsFees = $bankingFAVsFeeAmount['fee'];
        $bankingFAVsTax  = $bankingFAVsFeeAmount['tax'];

        $reversalFees = $bankingReversalsFeeAmount['fee'];
        $reversalTax  = $bankingReversalsFeeAmount['tax'];

        // The Finance come up with the requirement that we should have the merchant Invoice to be GST compliant
        // That mean they want the Tax should always be equal to 18% of the fees(amount) that we
        // charge from the Merchant

        $amount = ($bankingPayoutsFees + $bankingFAVsFees - $bankingPayoutsTax - $bankingFAVsTax)
                  - ($reversalFees - $reversalTax)
                  - ($bankingFailedPayoutsFees - $bankingFailedPayoutsTax);

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

        $this->beginTimestamp = $beginDate->startOfMonth()->timestamp;

        $this->endTimestamp = $beginDate->endOfMonth()->timestamp;

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
            if ($balance->isTypePrimary() === true)
            {
                // [
                //    'card_lte_2k'             => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                //    'card_gt_2k'              => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                //    'instant_refunds'         => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                //    'others'                  => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
                //    'validation'              => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
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
}
