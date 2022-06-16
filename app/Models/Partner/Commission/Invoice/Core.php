<?php

namespace RZP\Models\Partner\Commission\Invoice;

use Carbon\Carbon;

use Mail;
use RZP\Exception;
use RZP\Models\Tax;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\LineItem;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Constants\HyperTrace;
use RZP\Models\Currency\Currency;
use RZP\Models\Partner\Commission;
use RZP\Models\Pricing\Calculator;
use RZP\Models\Tax\Gst\GstTaxIdMap;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\CommissionTdsSettlement;
use RZP\Jobs\CommissionInvoiceAction;
use RZP\Jobs\CommissionInvoiceGenerate;
use RZP\Mail\Merchant\CommissionInvoice;
use RZP\Mail\Merchant\CommissionProcessed;
use RZP\Mail\Merchant\CommissionOpsInvoice;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Mail\Merchant\CommissionInvoiceIssued;
use RZP\Models\Admin\Permission\Name as Permission;

class Core extends Base\Core
{
    const MAX_ALLOWED_PDF_GEN_ATTEMPTS = 2;
    const COMMISSION_GENERATE_MID_LIMIT = 100;
    const COMMISSION_INVOICE_ACTION_DELAY = 120; // seconds
    const COMMISSION_INVOICE_GENERATE_MUTEX_TIMEOUT = 3600; // seconds

    /**
     * @var PdfGenerator
     */
    protected $pdfGenerator;

    public function queueCreateInvoiceEntities(array $input)
    {
        RuntimeManager::setTimeLimit(1800);

        $previousMonth = Carbon::now(Timezone::IST)->subMonth();

        $input[Entity::YEAR]  = $input[Entity::YEAR] ?? $previousMonth->year;
        $input[Entity::MONTH] = $input[Entity::MONTH] ?? $previousMonth->month;

        $input[Entity::REGENERATE_IF_EXISTS] = $input[Entity::REGENERATE_IF_EXISTS] ?? false;
        $input[Entity::FORCE_REGENERATE]     = $input[Entity::FORCE_REGENERATE] ?? false;

        (new Validator)->validateInput('invoice_generate_request', $input);

        if (empty($input['merchant_ids']) === false)
        {
            $this->dispatchCommissionInvoiceGenerateRequest($input);

            return [];
        }

        $afterId = null;

        $data = [
            Entity::MONTH                => $input[Entity::MONTH],
            Entity::YEAR                 => $input[Entity::YEAR],
            Entity::REGENERATE_IF_EXISTS => $input[Entity::REGENERATE_IF_EXISTS],
            Entity::FORCE_REGENERATE     => $input[Entity::FORCE_REGENERATE],
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
        $merchant = $invoice->merchant;
        $env = $this->app->environment();

        // check for approved only for merchant request and not after workflow approval
        if (($this->app['api.route']->isWorkflowExecuteOrApproveCall() === true) or
            (($env === 'testing') and ($input[Entity::ACTION] === Status::APPROVED)))
        {
            $invoice->setStatus(Status::APPROVED);

            $this->repo->saveOrFail($invoice);

            Tracer::inspan(['name' => HyperTrace::CLEAR_ON_HOLD_FOR_PARTNER_CORE], function () use ($merchant, $invoice) {

                // clear on Hold For Partner after workflow is approved
                (new Commission\Core)->clearOnHoldForPartner($merchant, [Commission\Constants::INVOICE_ID => $invoice->getId()]);
            });

            return ['success' => 'true'];
        }

        (new Validator())->validateMerchantToAllowChangeAction($input[Entity::ACTION]);

        $invoice->setStatus($input[Entity::ACTION]);

        $this->repo->saveOrFail($invoice);

        $attrs = [
            'invoiceId'        =>  $invoice->getId(),
            'invoiceStatus'    =>  $invoice->getStatus(),
            'merchantId'       => $merchant->getId()
        ];
        Tracer::inspan(['name' => HyperTrace::COMMISSION_INVOICE_ACTION, 'attributes' => $attrs], function () use ($invoice) {

            CommissionInvoiceAction::dispatch($this->mode, $invoice->getStatus(), $invoice->getId())->delay(self::COMMISSION_INVOICE_ACTION_DELAY);
        });

        Tracer::inspan(['name' => HyperTrace::TRIGGER_COMMISSION_INVOICE_ACTION, 'attributes' => $attrs], function () use ($invoice, $merchant) {

            $this->triggerWorkflowActionIfApplicable($invoice, $merchant);
        });

        return ['success' => 'true'];
    }

    public function sendCommissionMail(Entity $invoice, string $pdfPath)
    {
        $data = $this->getTemplateData($invoice, $pdfPath);

        $commissionInvoice = new CommissionInvoice($data);

        Mail::send($commissionInvoice);

        $opsInvoice = new CommissionOpsInvoice($data);

        Mail::send($opsInvoice);
    }

    public function sendCommissionSms(Entity $invoice, string $pdfPath = null)
    {
        $merchant = $invoice->merchant;

        $properties = [
            'id'            => $merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.send_sms_on_commission_invoice_issued_exp_id'),
        ];

        $isExpEnabled = (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable', TraceCode::SEND_SMS_ON_COMMISSION_INVOICE_ISSUED_SPLITZ_ERROR);

        if($isExpEnabled === false)
        {
            return ;
        }

        $data = $this->getTemplateData($invoice, $pdfPath);

        $activationStatus = $data['activation_status'];

        $templateName = Commission\Constants::COMMISSION_INVOICE_ISSUED_SMS_TEMPLATE[Merchant\Constants::DEFAULT];

        if(isset(Commission\Constants::COMMISSION_INVOICE_ISSUED_SMS_TEMPLATE[$activationStatus])=== true)
        {
            $templateName = Commission\Constants::COMMISSION_INVOICE_ISSUED_SMS_TEMPLATE[$activationStatus];
        }

        $tracePayload = [
            'partner_id'          => $merchant->getId(),
            'activation_status'   => $activationStatus,
            'sms_template'        => $templateName
        ];

        try
        {
            if(empty($merchant->merchantDetail->getContactMobile()) === false)
            {
                $smsPayload = [
                    'ownerId'           => $merchant->getId(),
                    'ownerType'         => 'merchant',
                    'orgId'             => $merchant->getOrgId(),
                    'sender'            => 'RZRPAY',
                    'destination'       => $merchant->merchantDetail->getContactMobile(),
                    'templateName'      => $templateName,
                    'templateNamespace' => 'partnerships',
                    'language'          => 'english',
                    'contentParams'     => [
                        'start_date'   => $data['start_date'],
                        'end_date'     => $data['end_date']
                    ]
                ];

                $this->trace->info(TraceCode::SEND_PARTNER_COMMISSION_INVOICE_SMS, $tracePayload);

                $this->app->stork_service->sendSms($this->mode, $smsPayload);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL, TraceCode::PARTNER_COMMISSION_INVOICE_COMMUNICATION_SMS_FAILED, $tracePayload);
        }
    }

    public function sendCommissionIssuedMail(Entity $invoice, string $pdfPath = null)
    {
        $data = $this->getTemplateData($invoice, $pdfPath);

        $commissionInvoice = new CommissionInvoiceIssued($data);

        Mail::send($commissionInvoice);
    }

    public function sendCommissionInvoiceEvents(Entity $invoice, array $eventCode)
    {
        $data = $this->getTemplateData($invoice);

        $properties = [
            'id'            => $data['merchant']['id'],
            'experiment_id' => $this->app['config']->get('app.commission_invoice_events_exp_id'),
        ];

        $isExpEnabled = (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');

        if($isExpEnabled === false)
        {
            return ;
        }

        $eventData = [
            'partner_id'                =>  $data['merchant']['id'],
            'month_of_commission'       =>  $invoice->getMonth().'-'.$invoice->getYear(),
            'commission_amount'         =>  $data['invoice']['gross_amount_spread'][0].' '.$data['invoice']['gross_amount_spread'][1].'.'.$data['invoice']['gross_amount_spread'][2],
        ] ;

        if ($eventCode !== EventCode::PARTNERSHIPS_COMMISSION_INVOICE_PROCESSED)
        {
            $eventData['activation_status'] = $data['activation_status'];
        }

        $this->trace->info(TraceCode::COMMISSION_INVOICE_ACTION_EVENTS,
            [
                'mode'   => $this->mode,
                'event'  => $eventCode['name'],
                'data'   => $eventData,
            ]);

        $this->app['diag']->trackOnboardingEvent($eventCode, $invoice->merchant, null, $eventData);
    }

    public function sendCommissionProcessedMail(Entity $invoice, string $pdfPath)
    {
        $data = $this->getTemplateData($invoice, $pdfPath);

        $commissionInvoice = new CommissionProcessed($data);

        Mail::send($commissionInvoice);
    }

    public function getTemplateData(Entity $invoice, $pdfPath = null): array
    {
        $month = $invoice->getMonth();
        $year  = $invoice->getYear();

        $timestamps = $this->convertMonthAndYearToTimeStamp($month, $year);

        $fromTimestamp = $timestamps[Commission\Constants::FROM];
        $endTimestamp  = $timestamps[Commission\Constants::TO];

        $relations = ['lineItems', 'lineItems.taxes'];
        $invoice->load($relations);

        $merchant      = $invoice->merchant;
        $tdsPercentage = (new Commission\Core)->getTdsPercentage($merchant);

        $gstin = $merchant->merchantDetail->getGstin();
        $gstin = (empty($gstin) === true) ? null : $gstin;

        $promotorPan   = $merchant->merchantDetail->getPromoterPan();
        $companyPan    = $merchant->merchantDetail->getPan();
        $activationStatus     = $merchant->merchantDetail->getActivationStatus();

        $pan = null;

        // use pan which corresponds to the gstin else show whichever pan is available
        if (empty($gstin) === false)
        {
            if (empty($promotorPan) === false and str_contains($gstin, $promotorPan) === true)
            {
                $pan = $promotorPan;
            }
            else if (empty($companyPan) === false and str_contains($gstin, $companyPan) === true)
            {
                $pan = $companyPan;
            }
            else {
                // use whatever pan is present in gstin
                $pan = substr($gstin, 2, 10);
            }
        }
        else if (empty($companyPan) === false)
        {
            $pan = $companyPan;
        }
        else if (empty($promotorPan) === false)
        {
            $pan = $promotorPan;
        }

        $data  = [
            'merchant'                 => $invoice->merchant->toArray(),
            'pan'                      => $pan,
            'gstin'                    => $gstin,
            'address'                  => $merchant->getBusinessRegisteredAddressAsText(),
            'start_date'               => Carbon::createFromTimestamp($fromTimestamp, Timezone::IST)->format('d-M-y'),
            'end_date'                 => Carbon::createFromTimestamp($endTimestamp, Timezone::IST)->format('d-M-y'),
            'is_under_auto_commission' => $invoice->merchant->isUnderAutomatedCommission(),
            'invoice'                  => $invoice->toArrayPublic(),
            'created_at'               => Carbon::createFromTimestamp($invoice->getCreatedAt(), Timezone::IST)->format('d-M-y'),
            'tds_percentage'           => $tdsPercentage/100,
            'activation_status'        => $activationStatus,
        ];

        if (empty($pdfPath) === false)
        {
            $data['file_path'] = $pdfPath;
        }

        $data['invoice']['gross_amount_spread'] = $this->formatAmountForTemplate($data['invoice']['gross_amount']);
        $data['invoice']['tax_amount_spread'] = $this->formatAmountForTemplate($data['invoice']['tax_amount']);

        foreach ($data['invoice']['line_items'] as $key => &$lineItem)
        {
            if (empty($lineItem['taxes']) === false)
            {
                foreach ($lineItem['taxes'] as &$tax)
                {
                    $tax['tax_amount_spread'] = $this->formatAmountForTemplate($tax['tax_amount']);
                }
            }

            $lineItem['gross_amount_spread'] = $this->formatAmountForTemplate($lineItem['gross_amount']);
            $lineItem['tax_amount_spread'] = $this->formatAmountForTemplate($lineItem['tax_amount']);
            $lineItem['net_amount_spread'] = $this->formatAmountForTemplate($lineItem['net_amount']);

            $subTotal = $lineItem['gross_amount'] - $lineItem['tax_amount'];
            $lineItem['sub_total_spread'] = $this->formatAmountForTemplate($subTotal);
        }

        return $data;
    }

    protected function formatAmountForTemplate($amount)
    {
        $currency = 'INR';

        $currencySymbol = Currency::SYMBOL[$currency];

        $denominationFactor = Currency::DENOMINATION_FACTOR[$currency] ?: 100;

        $rupeesInAmount = money_format_IN((integer)($amount / $denominationFactor));

        $paiseInAmount = str_pad($amount % $denominationFactor, 2, 0, STR_PAD_LEFT);

        return [$currencySymbol, $rupeesInAmount, $paiseInAmount];
    }

    public function triggerWorkflowActionIfApplicable(Entity $invoice, Merchant\Entity $merchant)
    {
        $result = $merchant->isFeatureEnabled(Feature\Constants::AUTOMATED_COMM_PAYOUT);

        if ($result === false)
        {
            return;
        }

        //trigger workflow if automated commission feature is present;
        $routePermission = Permission::COMMISSION_PAYOUT;

        $this->trace->info(
            TraceCode::COMMISSION_INVOICE_ACTION_TRIGGER_WORKFLOW,
            [
                'invoice_id' => $invoice->getId(),
                'merchant_id' => $merchant->getId(),
            ]);

        $newInvoice = clone $invoice;

        $newInvoice->setStatus(Status::APPROVED);

        $details = (new Commission\Core)->fetchAggregateCommissionDetails($merchant,[Commission\Constants::INVOICE_ID => $invoice->getId()]);

        $dirtyData = [
            Entity::ID          => $invoice->getId(),
            Entity::MERCHANT_ID => $invoice->getMerchantId(),
            Entity::MONTH       => $invoice->getMonth(),
            Entity::YEAR        => $invoice->getYear(),
            Entity::STATUS      => $newInvoice->getStatus(),
        ];

        $dirtyData = array_merge($dirtyData, $details);

        $this->app['workflow']
            ->setPermission($routePermission)
            ->setEntityAndId($invoice->getEntity(), $invoice->getId())
            ->setDirty($dirtyData)
            ->handle();
    }

    public function generateInvoice(Merchant\Entity $partner, array $input)
    {
        $this->trace->info(
            TraceCode::COMMISSION_INVOICE_GENERATE_REQUEST,
            [
                'partner' => $partner->getId(),
            ]);

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

        $resource = 'COMMISSION_INVOICE_GENERATE_'. $partner->getId();

        $this->app['api.mutex']->acquireAndRelease(
            $resource, function () use ($partner, $input) {
                $this->generate($partner, $input);
            },
            self::COMMISSION_INVOICE_GENERATE_MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);
    }

    protected function generate(Merchant\Entity $partner, array $input)
    {
        $previousMonth = Carbon::now(Timezone::IST)->subMonth();

        $year  = $input[Entity::YEAR] ?? $previousMonth->year;
        $month = $input[Entity::MONTH] ?? $previousMonth->month;

        $regenerateIfExists = (bool) ($input[Entity::REGENERATE_IF_EXISTS] ?? false);
        $forceRegenerate    = (bool) ($input[Entity::FORCE_REGENERATE] ?? false);

        $invoices = $this->repo->commission_invoice->fetchInvoices($partner->getId(), $month, $year);

        if (($invoices->isEmpty() === false) and ($regenerateIfExists === false))
        {
            $this->trace->info(TraceCode::COMMISSION_INVOICE_SKIPPED_ALREADY_EXISTS,
                [
                    'partner'     => $partner->getId(),
                    'invoice_ids' => $invoices->getIds(),
                ]);

            return;
        }

        if ($invoices->isEmpty() === false)
        {
            // regenerate only if the existing invoice is in issued state or forceRegenerate is true
            if (($regenerateIfExists === true) and (($forceRegenerate === true) or ($invoices->first()->isIssued() === true)))
            {
                foreach ($invoices as $invoice)
                {
                    // delete the existing invoices
                    $this->repo->deleteOrFail($invoice);
                }
            }
            else
            {
                $this->trace->info(TraceCode::COMMISSION_INVOICE_REGENERATE_SKIPPED,
                    [
                        'partner'     => $partner->getId(),
                        'invoice_ids' => $invoices->getIds(),
                    ]);

                return;
            }
        }

        $invoiceCreateInput = [
            Entity::MONTH => $month,
            Entity::YEAR  => $year,
        ];

        $invoice = $this->build($partner, $invoiceCreateInput);

        $this->repo->transaction(function() use ($partner, $invoice, $month, $year) {

            $created = $this->createLineItemsForInvoice($partner, $invoice, $month, $year);

            if ($created === false)
            {
                $this->trace->info(TraceCode::COMMISSION_INVOICE_SKIPPED_LINE_ITEMS_NOT_CREATED,
                    [
                        'partner'     => $partner->getId(),
                    ]);

                return;
            }

            $this->updateInvoiceAmounts($invoice);

            $this->repo->saveOrFail($invoice);

            if ($invoice->isIssued() === true)
            {
                CommissionInvoiceAction::dispatch($this->mode, $invoice->getStatus(), $invoice->getId())->delay(self::COMMISSION_INVOICE_ACTION_DELAY);
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
            Entity::MONTH                => $input[Entity::MONTH],
            Entity::YEAR                 => $input[Entity::YEAR],
            'merchant_ids'               => $input['merchant_ids'],
            Entity::REGENERATE_IF_EXISTS => $input[Entity::REGENERATE_IF_EXISTS],
            Entity::FORCE_REGENERATE     => $input[Entity::FORCE_REGENERATE],
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
        $fromTimestamp = Carbon::createFromDate($year, $month, 1, Timezone::IST)->startOfMonth()->getTimestamp();
        $endTimestamp  = Carbon::createFromDate($year, $month, 1, Timezone::IST)->endOfMonth()->getTimestamp();

        return [
            Commission\Constants::FROM => $fromTimestamp,
            Commission\Constants::TO   => $endTimestamp,
        ];
    }

    public function clearOnHoldForInvoiceBulk(array $input)
    {
        if (empty($input[Constants::INVOICE_IDS]) === true)
        {
            return [];
        }

        $invoiceCore = new Core;

        foreach ($input[Constants::INVOICE_IDS] as $invoiceId)
        {
            $invoice = $this->repo->commission_invoice->findOrFail($invoiceId);

            $createTds           = (bool) ($input[Constants::CREATE_TDS] ?? false);
            $updateInvoiceStatus = (bool) ($input[Constants::UPDATE_INVOICE_STATUS] ?? false);
            $skipProcessed       = (bool) ($input[Constants::SKIP_PROCESSED] ?? true);

            $data = $invoiceCore->convertMonthAndYearToTimeStamp($invoice->getMonth(), $invoice->getYear());

            $data[Constants::INVOICE_ID] = $invoice->getId();
            $data[Constants::UPDATE_INVOICE_STATUS] = $updateInvoiceStatus;
            $data[Constants::CREATE_TDS] = $createTds;
            $data[Constants::SKIP_PROCESSED] = $skipProcessed;

            $attrs = [
                'partnerId'        =>  $invoice->getMerchantId(),
                'invoiceId'        =>  $data[Constants::INVOICE_ID]
            ];
            Tracer::inspan(['name' => HyperTrace::COMMISSION_TDS_SETTLEMENT, 'attributes' => $attrs], function () use ($invoice, $data) {

                CommissionTdsSettlement::dispatch($this->mode, $invoice->getMerchantId(), $data);
            });
        }

        return [];
    }

    protected function createLineItemsForInvoice(Merchant\Entity $partner, Entity $invoice, int $month, int $year): bool
    {
        $timestamps = $this->convertMonthAndYearToTimeStamp($month, $year);

        $fromTimestamp = $timestamps[Commission\Constants::FROM];
        $endTimestamp  = $timestamps[Commission\Constants::TO];

        $aggregateSumComponents = $this->repo->commission->fetchAggregateFeesAndTaxForInvoice($partner->getId(), $fromTimestamp, $endTimestamp);

        $totalSum = 0;

        foreach ($aggregateSumComponents as $sumComponent)
        {
            $aggregateSum = $sumComponent->getAttributes();

            // aggregateSum contains both commission and tax
            $totalSum += $aggregateSum['fee'];
        }

        if (empty($totalSum) === true)
        {
            $this->trace->info(TraceCode::COMMISSION_INVOICE_SKIPPED_AMOUNT_ZERO,
                               [
                                   'partner'     => $partner->getId(),
                               ]);
            return false;
        }

        $lineItemInput = [];

        foreach ($aggregateSumComponents as $key => $sumComponent)
        {
            $aggregateSum = $sumComponent->getAttributes();
            $amount = $aggregateSum['fee'];

            if ($amount <= 0) {
                continue;
            }

            // if line item amount is less than 100 paisa, skip creating the line item
            if ($amount < 100)
            {
                $this->trace->info(TraceCode::COMMISSION_INVOICE_LINE_ITEMS_CREATE_SKIPPED, [
                    'reason' => 'line item amount less than 100 paisa',
                    'amount' => $amount,
                ]);
                continue;
            }

            $lineItem = [
                LineItem\Entity::AMOUNT        => $amount,
                LineItem\Entity::CURRENCY      => 'INR',
                LineItem\Entity::TAX_INCLUSIVE => true,
            ];

            // assigning line item tax components for primary and banking commissions
            if (($key === 'zero_tax_primary') or ($key === 'zero_tax_banking'))
            {
                $lineItem[LineItem\Entity::TAX_RATE] = 0;
                $lineItem[LineItem\Entity::TAX_IDS]  = [];

                $lineItem[LineItem\Entity::NAME] = ($key === 'zero_tax_primary') ? Commission\Constants::PRIMARY_COMMISSION
                                                                                 : Commission\Constants::BANKING_COMMISSION;
            }
            else
            {
                $taxComponents = Calculator\Base::getTaxComponents($partner);

                $taxRate = 1800;
                $prefix = Tax\Entity::getSign() . '_';

                // CGST/IGST for karnataka partners and IGST for others
                $taxIds = [$prefix . GstTaxIdMap::IGST_180000];
                if (count($taxComponents) == 2) {
                    $taxIds  = [$prefix . GstTaxIdMap::CGST_90000, $prefix . GstTaxIdMap::SGST_90000];
                }

                $lineItem[LineItem\Entity::TAX_RATE] = $taxRate;
                $lineItem[LineItem\Entity::TAX_IDS]  = $taxIds;

                $lineItem[LineItem\Entity::NAME] = ($key === 'nonzero_tax_primary') ? Commission\Constants::PRIMARY_COMMISSION
                                                                                    : Commission\Constants::BANKING_COMMISSION;
            }

            $lineItemInput[] = $lineItem;
        }

        if (empty($lineItemInput) === true)
        {
            return false;
        }

        (new LineItem\Core)->updateLineItemsAsPut($lineItemInput, $partner, $invoice);

        return true;
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
