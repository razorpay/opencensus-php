<?php

namespace RZP\Models\Partner\Commission\Invoice;

use Carbon\Carbon;

use Mail;
use RZP\Exception;
use RZP\Models\Tax;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\LineItem;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Partner\Commission;
use RZP\Models\Tax\Gst\GstTaxIdMap;
use RZP\Jobs\CommissionInvoiceAction;
use RZP\Jobs\CommissionInvoiceGenerate;
use RZP\Mail\Merchant\CommissionInvoice;
use RZP\Models\Admin\Permission\Name as Permission;

class Core extends Base\Core
{
    const MAX_ALLOWED_PDF_GEN_ATTEMPTS = 2;
    const COMMISSION_GENERATE_MID_LIMIT = 10;

    /**
     * @var PdfGenerator
     */
    protected $pdfGenerator;

    public function queueCreateInvoiceEntities(array $input)
    {
        RuntimeManager::setTimeLimit(1800);

        $previousMonth = Carbon::now(Timezone::IST)->subMonth();

        $input['year']  = $input['year'] ?? $previousMonth->year;
        $input['month'] = $input['month'] ?? $previousMonth->month;

        (new Validator)->validateInput('invoice_generate_request', $input);

        if (empty($input['merchant_ids']) === false)
        {
            $this->dispatchCommissionInvoiceGenerateRequest($input);

            return [];
        }

        $afterId = null;

        $data = [
            'month' => $input['month'],
            'year'  => $input['year'],
        ];

        while (true)
        {
            $features = $this->repo->feature->findMerchantsHavingFeatures([Feature\Constants::GENERATE_PARTNER_INVOICE], 500, $afterId);

            if ($features->isEmpty() === true)
            {
                break;
            }

            $afterId = $features->last()->getId();

            $mIds = $features->pluck(Feature\Entity::ENTITY_ID)->toArray();

            $mIdGroups = array_chunk($mIds, self::COMMISSION_GENERATE_MID_LIMIT);

            foreach ($mIdGroups as $mIds)
            {
                $data['merchant_ids'] = $mIds;

                $this->dispatchCommissionInvoiceGenerateRequest($data);
            }
        }

        return [];
    }

    public function changeInvoiceStatus(Entity $invoice, $input)
    {
        // check for approved only for merchant request and not after workflow approval
        if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === false)
        {
            (new Validator())->validateMerchantToAllowChangeAction($input[Entity::ACTION]);

            $invoice->setStatus($input[Entity::ACTION]);

            $this->repo->saveOrFail($invoice);

            CommissionInvoiceAction::dispatch($this->mode, $invoice->getStatus(), $invoice->getId());
        }

        $merchant = $invoice->merchant;

        $this->triggerWorkflowActionIfApplicable($invoice, $merchant);

        // clear on Hold For Partner after triggering workflow.
        (new Commission\Core)->clearOnHoldForPartner($merchant, [Commission\Constants::INVOICE_ID => $invoice->getId()]);

        return ['success' => 'true'];
    }

    public function sendCommissionMail(Entity $invoice)
    {
        $pdf = $invoice->pdf();

        $data = [
            'status'     => $invoice->getStatus(),
            'month_year' => $invoice->getMonth() . '/' . $invoice->getYear(),
        ];

        $data['filePath'] = $pdf ?? $pdf->getFullFilePath();

        $commissionInvoice = new CommissionInvoice($data);

        Mail::queue($commissionInvoice);
    }

    public function triggerWorkflowActionIfApplicable(Entity $invoice, Merchant\Entity $merchant)
    {
        $result = $merchant->isFeatureEnabled(Feature\Constants::AUTOMATED_COMM_PAYOUT);

        if ($result === true)
        {
            //trigger workflow if automated commission feature is present;
            $routePermission = Permission::COMMISSION_PAYOUT;

            $this->trace->info(
                TraceCode::COMMISSION_INVOICE_ACTION_TRIGGER_WORKFLOW,
                [
                    'invoice_id' => $invoice->getId(),
                    'merchant_id' => $merchant->getId(),
                ]);

            $newInvoice = clone $invoice;

            $newInvoice->setStatus(Status::PROCESSED);

            $this->app['workflow']->setPermission($routePermission)->handle($newInvoice, $invoice);
        }
    }

    public function generateInvoice(Merchant\Entity $partner, array $input)
    {
        $this->trace->info(
            TraceCode::COMMISSION_INVOICE_GENERATE_REQUEST,
            [
                'partner' => $partner->getId(),
            ]);

        $previousMonth = Carbon::now(Timezone::IST)->subMonth();

        $year  = $input['year'] ?? $previousMonth->year;
        $month = $input['month'] ?? $previousMonth->month;

        $balance = $partner->commissionBalance;

        if ($balance === null)
        {
            $this->trace->info(
                TraceCode::COMMISSION_INVOICE_SKIPPED_BALANCE_ABSENT,
                [
                    'partner' => $partner->getId(),
                ]);

            return;
        }

        $invoices = $this->repo->commission_invoice->fetchInvoices($partner->getId(), $month, $year);

        if ($invoices->isEmpty() === false)
        {
            $this->trace->info(TraceCode::COMMISSION_INVOICE_SKIPPED_ALREADY_EXISTS,
                [
                    'partner'     => $partner->getId(),
                    'invoice_ids' => $invoices->getIds(),
                ]);

            return;
        }

        $invoiceCreateInput = [
            Entity::MONTH => $month,
            Entity::YEAR  => $year,
        ];

        $invoice = $this->build($partner, $invoiceCreateInput);

        $this->repo->transaction(function() use ($partner, $invoice, $month, $year) {

            $this->createLineItemsForInvoice($partner, $invoice, $month, $year);
            $this->updateInvoiceAmounts($invoice);

            $this->repo->saveOrFail($invoice);

            if ($invoice->isIssued() === true)
            {
                CommissionInvoiceAction::dispatch($this->mode, $invoice->getStatus(), $invoice->getId());
            }
        });
    }

    public function createInvoicePdfAndGetFilePath(Entity $invoice)
    {
        $pdf = $this->createInvoicePdf($invoice);

        return ($pdf !== null) ? $pdf->getFullFilePath() : null;
    }

    /**
     * @param array $input
     */
    protected function dispatchCommissionInvoiceGenerateRequest(array $input): void
    {
        $data = [
            'month'        => $input['month'],
            'year'         => $input['year'],
            'merchant_ids' => $input['merchant_ids'],
        ];

        CommissionInvoiceGenerate::dispatch($this->mode, $data);
    }

    protected function createInvoicePdf(Entity $invoice)
    {
        //
        // Single PdfGenerator instance created as part of this class's member,
        // used multiple times in following line with retry.
        //
        $this->setPdfGenerator($invoice);

        return $this->generatePdfWithRetry($invoice->getId());
    }

    protected function generatePdfWithRetry(string $id, int $attempt = 0)
    {
        $attempt++;

        if ($attempt > self::MAX_ALLOWED_PDF_GEN_ATTEMPTS)
        {
            return null;
        }

        try
        {
            return $this->pdfGenerator->generate();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::INVOICE_PDF_GEN_FAILED,
                [
                    'id'       => $id,
                    'attempts' => $attempt,
                ]);

            // Don't attempt regenerating file if there was some 4XX error

            if ($e instanceof Exception\BadRequestValidationFailureException)
            {
                return null;
            }

            $this->generatePdfWithRetry($id, $attempt);
        }
    }

    protected function setPdfGenerator(Entity $invoice)
    {
        $this->pdfGenerator = new PdfGenerator($invoice);
    }

    protected function build(Merchant\Entity $partner, array $input)
    {
        $invoice = new Entity;

        $invoice->generateId();

        $invoice->build($input);

        $invoice->merchant()->associate($partner);

        $invoice->balance()->associate($partner->commissionBalance);

        return $invoice;
    }

    public function convertMonthAndYearToTimeStamp(int $month, int $year)
    {
        $fromTimestamp = Carbon::createFromDate($year, $month, 1)->startOfMonth()->getTimestamp();
        $endTimestamp  = Carbon::createFromDate($year, $month, 1)->endOfMonth()->getTimestamp();

        return [
            Commission\Constants::FROM => $fromTimestamp,
            Commission\Constants::TO   => $endTimestamp,
        ];
    }

    protected function createLineItemsForInvoice(Merchant\Entity $partner, Entity $invoice, int $month, int $year)
    {
        $fromTimestamp = Carbon::createFromDate($year, $month, 1)->startOfMonth()->getTimestamp();
        $endTimestamp  = Carbon::createFromDate($year, $month, 1)->endOfMonth()->getTimestamp();

        $aggregateSum = $this->repo->commission->fetchAggregateFeesAndTax($partner->getId(), $fromTimestamp, $endTimestamp);

        $aggregateSum = $aggregateSum->getAttributes();

        // amount contains both commission and tax
        $amount = $aggregateSum['fee'];

        $taxRate = 1800;
        $prefix = Tax\Entity::getSign() . '_';

        $taxIds  = [$prefix . GstTaxIdMap::CGST_90000, $prefix . GstTaxIdMap::SGST_90000];

        $lineItemInput = [
            [
                LineItem\Entity::NAME          => Commission\Constants::COMMISSION,
                LineItem\Entity::AMOUNT        => $amount,
                LineItem\Entity::CURRENCY      => 'INR',
                LineItem\Entity::TAX_INCLUSIVE => true,
                LineItem\Entity::TAX_RATE      => $taxRate,
                LineItem\Entity::TAX_IDS       => $taxIds,
            ]
        ];

        (new LineItem\Core)->updateLineItemsAsPut($lineItemInput, $partner, $invoice);
    }

    protected function updateInvoiceAmounts(Entity $invoice)
    {
        $lineItems = $invoice->lineItems()->get();

        $grossAmount = $taxAmount = 0;

        $lineItemCore = new LineItem\Core;

        foreach ($lineItems as $lineItem)
        {
            // Gets line item's gross, tax and net amount in order
            $amounts = $lineItemCore->calculateAmountsOfLineItem($lineItem);

            $grossAmount += $amounts[0];
            $taxAmount   += $amounts[1];
        }

        $invoice->setGrossAmount($grossAmount);
        $invoice->setTaxAmount((int) round($taxAmount));
    }
}
