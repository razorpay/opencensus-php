<?php

namespace RZP\Models\Partner\Commission\Invoice;

use RZP\Base;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Partner\Metric as PartnerMetric;

class Validator extends Base\Validator
{
    // for banking and primary commissions (taxable + non_taxable)
    const MAX_ALLOWED_LINE_ITEMS = 4;

    protected static $invoiceGenerateRequestRules = [
        Entity::MONTH                => 'required|integer|between:1,12',
        Entity::YEAR                 => 'required|digits:4',
        Entity::REGENERATE_IF_EXISTS => 'sometimes|boolean',
        Entity::FORCE_REGENERATE     => 'sometimes|boolean',
        'merchant_ids'               => 'sometimes|array',
        'merchant_ids.*'             => 'sometimes|string|size:14',
    ];

    protected static $changeStatusRules = [
        Entity::ACTION => 'required|string|custom',
    ];

    protected static $fetchSubMtuCountRules = [
        Constants::PARTNER_IDS   => 'required|array',
        Constants::INVOICE_MONTH => 'required|string',
    ];

    protected static $createRules = [
        Entity::MONTH => 'required|integer|between:1,12',
        Entity::YEAR  => 'required|digits:4',
    ];

    protected static $bulkOnHoldClearRules = [
        Constants::INVOICE_IDS           => 'required|array',
        Constants::CREATE_TDS            => 'sometimes|boolean',
        Constants::UPDATE_INVOICE_STATUS => 'sometimes|boolean',
        Constants::SKIP_PROCESSED        => 'sometimes|boolean',
    ];

    public function validateAction($attribute, $key)
    {
        Status::validateStatus($key);
    }

    public function validateMerchantToAllowChangeAction(string $status)
    {
        return key_exists($status, Status::ALLOWED_STATUSES_FOR_MERCHANT);
    }

    /**
     * validates if the invoice is expired for partner approval
     * @param Entity $invoice invoice entity
     *
     * @throws Exception\LogicException
     */
    public function validatePartnerInvoiceApprovalExpiry(Entity $invoice)
    {
        $invoiceMonth = $invoice->getMonth();
        $invoiceYear  = $invoice->getYear();
        $invoiceTimestamp = Carbon::createFromDate($invoiceYear, $invoiceMonth)->setTimezone(Timezone::IST)->timestamp;

        if($this->isCurrentFinancialYear($invoiceTimestamp))
        {
            return ;
        }
        if($this->isLastQuarterInvoiceValid($invoiceMonth, $invoiceYear) === false)
        {
            app('trace')->count(PartnerMetric::PARTNER_INVOICE_APPROVAL_AFTER_EXPIRY);

            throw new Exception\LogicException(
                'Invoice expired for partner approval',
                null,
                [
                    'invoice_id' => $invoice->getId()
                ]);
        }
    }

    /**
     * validates if the invoice timestamp is present in current financial year
     * @param int $invoiceTimestamp invoice timestamp
     *
     * @return bool
     */
    protected function isCurrentFinancialYear(int $invoiceTimestamp): bool
    {
        $now = Carbon::now(Timezone::IST);

        $financialYearStart = $now->month > 3 ?
            Carbon::createFromDate($now->year, 4)->setTimezone(Timezone::IST)->startOfMonth()->timestamp
            : Carbon::createFromDate($now->year-1, 4)->setTimezone(Timezone::IST)->startOfMonth()->timestamp;

        $financialYearEnd = $now->month > 3 ?
            Carbon::createFromDate($now->year+1, 3)->setTimezone(Timezone::IST)->endOfMonth()->timestamp
            : Carbon::createFromDate($now->year, 3)->setTimezone(Timezone::IST)->endOfMonth()->timestamp;

        return $invoiceTimestamp >= $financialYearStart and $invoiceTimestamp <= $financialYearEnd;
    }

    /**
     * validates if the invoice is for last quarter previous financial year and current month is
     * present in first quarter of current financial year
     * @param int $invoiceYear invoice year
     * @param int $invoiceMonth invoice month
     *
     * @return bool
     */
    protected function isLastQuarterInvoiceValid(int $invoiceMonth, int $invoiceYear): bool
    {
        $now = Carbon::now(Timezone::IST);

        if($now->month > 6)
        {
            return false;
        }

        return $invoiceMonth < 4  and $invoiceYear === $now->year;
    }


    public function validateLineItemsCount(int $lineItemsCount)
    {
        if ($lineItemsCount > self::MAX_ALLOWED_LINE_ITEMS)
        {
            $message = 'The invoice may not have more than ' . self::MAX_ALLOWED_LINE_ITEMS . ' items in total.';

            throw new Exception\BadRequestValidationFailureException(
                $message,
                null,
                [
                    'max_allowed_line_items'  => self::MAX_ALLOWED_LINE_ITEMS,
                    'actual_line_items_count' => $lineItemsCount,
                ]);
        }
    }
}
