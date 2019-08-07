<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Transaction\Type as TransactionType;

class Processor extends Base\Core
{
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
            $invoiceExists = $this->checkInvoiceExists($this->merchantId);

            if ($invoiceExists === true)
            {
                $this->trace->info(TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_SKIPPED,
                    [
                        'merchant'  => $this->merchantId,
                        'month'     => $this->month,
                        'year'      => $this->year,
                    ]);

                return;
            }

            // sum over fees & tax for different commission types
            foreach ($this->invoiceBreakup as $type => $values)
            {
                $this->invoiceBreakup[$type] = $this->calculateFeesForInvoiceByType($type, $isCorrection);
            }

            // create entities
            $this->createInvoiceBreakup();
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

    protected function checkInvoiceExists(string $merchantId): bool
    {
        $entities = $this->repo->merchant_invoice->fetchFeesDataForInvoice($merchantId, $this->month, $this->year);

        if ($entities->count() > 0)
        {
            return true;
        }

        return false;
    }

    protected function createInvoiceBreakup()
    {
        $this->repo->transaction(function()
        {
            foreach ($this->invoiceBreakup as $type => $values)
            {
                $params = [
                    Entity::MONTH   => $this->month,
                    Entity::YEAR    => $this->year,
                    Entity::GSTIN   => $this->gstin,
                    Entity::TYPE    => $type,
                    Entity::AMOUNT  => $values[Entity::AMOUNT],
                    Entity::TAX     => $values[Entity::TAX],
                ];

                (new Core)->create($params, $this->merchant);
            }
        });
    }

    /**
     * Populate the map of Type of Commission with its Amount and Tax values
     *
     * @param string $type
     * @param bool   $isCorrection
     *
     * @return array
     */
    public function calculateFeesForInvoiceByType(string $type, bool $isCorrection)
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
                                        ->fetchFeesAndTaxForTransactionsByType(
                                            $this->merchantId,
                                            TransactionType::FUND_ACCOUNT_VALIDATION,
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
                                        $this->endTimestamp,
                                        $type);
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
                                                $this->endTimestamp,
                                                $type);
        }

        $refundReversalFeeAmounts = $this->formatFeesForInvoice($refundReversalFeeAmount);

        return [
            Entity::TAX     => $paymentAmounts[Entity::TAX] + $transactionAmounts[Entity::TAX] + $validationAmounts[Entity::TAX] + $refundFeeAmounts[Entity::TAX] - $refundReversalFeeAmounts[Entity::TAX],
            Entity::AMOUNT  => $paymentAmounts[Entity::AMOUNT] + $transactionAmounts[Entity::AMOUNT] + $validationAmounts[Entity::AMOUNT] + $refundFeeAmounts[Entity::AMOUNT] - $refundReversalFeeAmounts[Entity::AMOUNT],
        ];
    }

    protected function isInvoiceTypeOfPayment(string $type)
    {
        return (in_array($type, [Type::CARD_GT_2K, Type::CARD_LTE_2K, Type::OTHERS]) === true);
    }

    protected function isInvoiceTypeOfRefund(string $type)
    {
        return (in_array($type, [Type::REFUND_LTE_1K, Type::REFUND_GT_1K_LTE_10K, Type::REFUND_GT_10K]) === true);
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

        $commissionTypes = Type::getAllTypes();

        // [
        //    'card_lte_2k'             => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
        //    'card_gt_2k'              => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
        //    'refund_lte_1k'           => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
        //    'refund_gt_1k_lte_10k'    => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
        //    'refund_gt_10k'           => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
        //    'others'                  => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
        //    'validation'              => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
        // ]
        foreach ($commissionTypes as $key)
        {
            $this->invoiceBreakup[$key] = [
                Entity::AMOUNT => 0,
                Entity::TAX => 0,
                Entity::AMOUNT_DUE => 0
            ];
        }
    }
}
