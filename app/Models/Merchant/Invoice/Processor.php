<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Models\Transaction\Type as TransactionType;
use RZP\Models\Transaction\Entity as TransactionEntity;

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

    public function createInvoiceEntities(bool $isCorrection)
    {
        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_REQUEST,
            [
                'correction'    => $isCorrection,
                'merchant_id'   => $this->merchantId,
                'month'         => $this->month,
                'year'          => $this->year,
            ]);

        try
        {
            $this->repo->transaction(function(){
                foreach ($this->invoiceBreakup as $balanceId => & $details)
                {
                    $existingInvoices = $this->checkInvoiceExists($this->merchantId , $balanceId);

                    if ($existingInvoices->count() > 0)
                    {
                        $this->trace->info(
                            TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_SKIPPED,
                            [
                                'merchant'      => $this->merchantId,
                                'month'         => $this->month,
                                'year'          => $this->year,
                                'invoice_ids'   => $existingInvoices->getIds(),
                            ]);

                        continue;
                    }

                    // sum over fees & tax for different commission types

                    /** @var Merchant\Balance\Entity $balance */
                    $balance = $this->repo->balance->findByIdAndMerchantId($balanceId, $this->merchantId);

                    if ($balance->isTypePrimary() === true)
                    {
                        // details passed by reference
                        $this->calculateFeesForPrimaryBalance($details);
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
            });
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_FAILED,
                [
                    'merchant_id'   => $this->merchant->getId(),
                    'month'         => $this->month,
                    'year'          => $this->year,
                ]);
        }
    }

    protected function checkInvoiceExists(string $merchantId , string $balanceId): Base\PublicCollection
    {
        return $this->repo->merchant_invoice->fetchFeesDataToCheckInvoiceExists($merchantId, $this->month, $this->year ,$balanceId);
    }

    protected function createInvoiceBreakup(Merchant\Balance\Entity $balance, array $details)
    {
        $feeBearer = $this->merchant->getFeeBearer();

        foreach ($details as $type => $feeDetails)
        {
            $params = [
                Entity::MONTH   => $this->month,
                Entity::YEAR    => $this->year,
                Entity::TYPE    => $type,
                Entity::GSTIN   => $this->gstin,
                Entity::AMOUNT  => $feeDetails[Entity::AMOUNT],
                Entity::TAX     => $feeDetails[Entity::TAX],
            ];

            if (($balance->isTypePrimary() === true) and
                ($feeBearer === Merchant\FeeBearer::CUSTOMER))
            {
                unset($params[Entity::GSTIN]);

                $this->app['trace']->info(
                    TraceCode::INVOICE_WITHOUT_GSTIN,
                    [
                        'gstin_no'     => $this->gstin,
                        'Merchant_id'  => $this->merchantId,
                    ]);
            }

            (new Core)->create($params, $this->merchant, $balance);
        }
    }

    protected function calculateFeesForPrimaryBalance(array & $details)
    {
        // sum over fees & tax for different commission types
        foreach ($details as $type => $values)
        {
            $details[$type] = $this->calculateFeesForInvoiceByTypeForPrimary($type);
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
            $rxTransactionFeeAmount = $this->repo
                                           ->transaction
                                           ->fetchFeesAndTaxForRXTransactions(
                                               $this->merchantId,
                                               $balanceId,
                                               $this->beginTimestamp,
                                               $this->endTimestamp
                                           );

            $rxReversalFeeAmount = $this->repo
                                        ->reversal
                                        ->fetchSumOfFeesAndTaxForReversalPayoutsByBalanceId($this->merchantId,
                                                                                            $balanceId,
                                                                                            $this->beginTimestamp,
                                                                                            $this->endTimestamp);

            $formattedFeesForTypeAndBalance = $this->formatFeesForRXInvoice($rxTransactionFeeAmount,
                                                                            $rxReversalFeeAmount);

        }

        return $formattedFeesForTypeAndBalance;
    }

    /**
     * Populate the map of Type of Commission with its Amount and Tax values
     *
     * @param string $type
     * @param bool   $isCorrection
     *
     * @return array
     */
    public function calculateFeesForInvoiceByTypeForPrimary(string $type, bool $isCorrection = false)
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
                                         $type,
                                         $isCorrection);
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
        }

        $refundReversalFeeAmounts = $this->formatFeesForInvoice($refundReversalFeeAmount);

        return [
            Entity::TAX     => $paymentAmounts[Entity::TAX] + $transactionAmounts[Entity::TAX]
                                + $validationAmounts[Entity::TAX] + $refundFeeAmounts[Entity::TAX]
                                - $refundReversalFeeAmounts[Entity::TAX],

            Entity::AMOUNT  => $paymentAmounts[Entity::AMOUNT] + $transactionAmounts[Entity::AMOUNT]
                                + $validationAmounts[Entity::AMOUNT] + $refundFeeAmounts[Entity::AMOUNT]
                                - $refundReversalFeeAmounts[Entity::AMOUNT],
        ];
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
            $this->trace->error(
                TraceCode::MERCHANT_INVOICE_QUERY_TIMEOUT,
                [
                    'merchant_id' => $this->merchantId,
                    'month'       => $this->month,
                    'year'        => $this->year
                ]);

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
     * @param array $transactionFeeDetails
     * @param array $reversalFeeDetails
     *
     * @return array
     */
    protected function formatFeesForRXInvoice(TransactionEntity $transactionFeeDetails, ReversalEntity $reversalFeeDetails)
    {
        // TODO: Check if this works fine if there are no transactions for the month for the given balance ID
        if ((empty($transactionFeeDetails) === true) and
            (empty($reversalFeeDetails) === true))
        {
            return [
                Entity::TAX     => 0,
                Entity::AMOUNT  => 0
            ];
        }

        $transactionFeeDetails = $transactionFeeDetails->getAttributes();
        $reversalFeeDetails = $reversalFeeDetails->getAttributes();

        $transactionFees = $transactionFeeDetails['fee'];
        $transactionTax = $transactionFeeDetails['tax'];

        $reversalFees = $reversalFeeDetails['fee'];
        $reversalTax = $reversalFeeDetails['tax'];

        return [
            Entity::TAX     => $transactionTax - $reversalTax,
            Entity::AMOUNT  => ($transactionFees - $transactionTax) - ($reversalFees - $reversalTax)
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
