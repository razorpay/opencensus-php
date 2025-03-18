<?php

namespace RZP\Models\Partner\Commission\Invoice;

use Carbon\Carbon;

use Mail;
use RZP\Exception;
use RZP\Jobs\CommissionOnHoldClear;
use RZP\Mail\Merchant\CommissionInvoiceAutoApproved;
use RZP\Models\Merchant\Detail\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Status as DetailStatus;
use RZP\Models\Partner\Activation\Constants as PartnerActivationConstants;
use RZP\Models\Tax;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\LineItem;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Constants\HyperTrace;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Services\KafkaProducer;
use RZP\Models\Currency\Currency;
use RZP\Models\Partner\Commission;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Models\Pricing\Calculator;
use RZP\Models\Tax\Gst\GstTaxIdMap;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\CommissionTdsSettlement;
use RZP\Jobs\CommissionInvoiceAction;
use RZP\Jobs\CommissionInvoiceGenerate;
use RZP\Mail\Merchant\CommissionInvoice;
use RZP\Mail\Merchant\CommissionProcessed;
use RZP\Mail\Merchant\CommissionOpsInvoice;
use RZP\Models\LineItem\Tax as LineItemTax;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Diag\Event\OnBoardingEvent;
use RZP\Mail\Merchant\CommissionInvoiceIssued;
use RZP\Mail\Merchant\CommissionInvoiceReminder;
use RZP\Models\Merchant\Detail\Status as DeStatus;
use RZP\Models\Admin\Permission\Name as Permission;

class Core extends Base\Core
{
    const MAX_ALLOWED_PDF_GEN_ATTEMPTS = 2;
    const COMMISSION_GENERATE_MID_LIMIT = 100;
    const COMMISSION_INVOICE_ACTION_DELAY = 120; // seconds
    const COMMISSION_INVOICE_GENERATE_MUTEX_TIMEOUT = 3600; // seconds
    const COMMISSION_INVOICE_MUTEX_TIMEOUT = 300; // in seconds
    const MUTEX_LOCK_TIMEOUT = 3000; // in seconds
    const COMMISSIONS_TRANSACTION_FETCH_LIMIT = 2500;

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

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function fetchPartnersWithCommissionInvoiceFeature(int $limit, string $afterId) : array
    {
        $features = $this->repo->feature->fetchPaginatedPartnerIdsWithFeature(Feature\Constants::GENERATE_PARTNER_INVOICE, $afterId, $limit);

        $afterId = $features->last()->getId();

        $mIds = $features->pluck(Feature\Entity::ENTITY_ID)->toArray();
        return ['partner_ids' => $mIds, 'after_id' => $afterId];
    }

    public function changeInvoiceStatus(Entity $invoice, $input)
    {
        $merchant = $invoice->merchant;
        $env = $this->app->environment();
        // check for approved only for merchant request and not after workflow approval
        if (($this->app['api.route']->isWorkflowExecuteOrApproveCall() === true) or
            (($env === 'testing') and ($input[Entity::ACTION] === Status::APPROVED)))
        {
            return $this->approveInvoiceForProcessing($invoice, $merchant, $input);
        }

        (new Validator())->validateMerchantToAllowChangeAction($input[Entity::ACTION]);

        (new Validator())->validatePartnerInvoiceApprovalExpiry($invoice);

        if ($this->isFinanceAutoApprovalEnabled($invoice))
        {
            try {
            $result = $this->approveInvoiceForProcessing($invoice, $merchant, $input);

                $this->trace->info(
                    TraceCode::COMMISSION_INVOICE_FINANCE_AUTO_APPROVED,
                    [
                        "partner_id" => $merchant->getId(),
                        "invoice_id" => $invoice->getId()
                    ]
                );
                $this->trace->count(Metric::COMMISSION_INVOICE_FINANCE_AUTO_APPROVED);

                return $result;
            } catch (\Exception $e) {
                $this->trace->count(Metric::COMMISSION_INVOICE_FINANCE_AUTO_APPROVAL_FAILURE_TOTAL);
                throw $e;
            }
        }

        return $this->markInvoiceUnderReview($invoice, $input, $merchant);
    }

    private function isFinanceAutoApprovalEnabled(Entity $invoice): bool
    {
        $merchant = $invoice->merchant;

        if($merchant->getCountry() == 'MY')
        {
            return false;
        }

        if (!$this->isPartialFinanceApprovalRemovalExpEnabled($merchant->getId())) { // partner finance exp. check
            return false;
        }
        if ($invoice->getGrossAmount() > $this->getMaxAutoApprovalAmount($invoice)) { // gross amount > 150k
            return false;
        }
        return ($invoice->getYear() >= 2023 or ($invoice->getYear() >= 2022 and $invoice->getMonth() >= 12));// partner approval timestamp check for invoice before dec-2022
    }

    private function getMaxAutoApprovalAmount(Entity $invoice): int
    {
        if ($invoice->getYear() >= 2024 or ($invoice->getYear() >= 2023 and $invoice->getMonth() >= 12)) {
            return Entity::MAX_AUTO_APPROVAL_AMOUNT;
        }

        return Entity::OLD_MAX_AUTO_APPROVAL_AMOUNT;
    }

    /**
     * Approves the invoice and clears onHold status of invoice settlement.
     *
     * @param   Entity              $invoice    The invoice entity
     * @param   Merchant\Entity     $merchant   The partner merchant entity
     * @param   string[]            $input      This contains data to pass on next method
     *
     * @return  string[]            Success response as true
     *
     * @throws  Exception\LogicException    Throws Exception\LogicException
     */
    private function approveInvoiceForProcessing(Entity $invoice, Merchant\Entity $merchant, array $input)
    {
        $invoice->setStatus(Status::APPROVED);

        $this->repo->saveOrFail($invoice);

        $this->app->partnerships->updateInvoiceStatusAsync(['id'=> $invoice->getId(), 'status'=> $invoice->getStatus()], $invoice->getMerchantId());

        Tracer::inspan(['name' => HyperTrace::CLEAR_ON_HOLD_FOR_PARTNER_CORE], function () use ($merchant, $invoice, $input) {
            // clear on Hold For Partner after workflow is approved
            $data = [ Commission\Constants::INVOICE_ID => $invoice->getId() ];
            if (isset($input[Commission\Constants::INVOICE_AUTO_APPROVED])) {
                $data[Commission\Constants::INVOICE_AUTO_APPROVED] = $input[Commission\Constants::INVOICE_AUTO_APPROVED];
            }

            (new Commission\Core)->clearOnHoldForPartner($merchant, $data);
        });

        return ['success' => 'true'];
    }

    private function markInvoiceUnderReview($invoice, $input, $merchant)
    {
        $invoice->setStatus($input[Entity::ACTION]);

        $this->repo->saveOrFail($invoice);

        $this->app->partnerships->updateInvoiceStatusAsync(['id'=> $invoice->getId(), 'status'=> $invoice->getStatus()], $invoice->getMerchantId());

        $attrs = [
            'invoiceId'        =>  $invoice->getId(),
            'invoiceStatus'    =>  $invoice->getStatus(),
            'merchantId'       => $merchant->getId()
        ];
        Tracer::inspan(['name' => HyperTrace::COMMISSION_INVOICE_ACTION, 'attributes' => $attrs], function () use ($invoice) {

            CommissionInvoiceAction::dispatch(
                $this->mode, $invoice->getStatus(), $invoice->getId()
            )->delay(self::COMMISSION_INVOICE_ACTION_DELAY);
        });

        Tracer::inspan(['name' => HyperTrace::TRIGGER_COMMISSION_INVOICE_ACTION, 'attributes' => $attrs], function () use ($invoice, $merchant) {

            $this->triggerWorkflowAction($invoice, $merchant);
        });
        $this->trace->info(
            TraceCode::COMMISSION_INVOICE_FINANCE_UNDER_REVIEW,
            [
                "partner_id" => $merchant->getId(),
                "invoice_id" => $invoice->getId()
            ]
        );
        $this->trace->count(Metric::COMMISSION_INVOICE_FINANCE_UNDER_REVIEW);

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

        if((in_array($activationStatus, DeStatus::PAYMENTS_ENABLED_STATUSES) === false) || ($merchant->isFundsOnHold() === true))
        {
            return;
        }

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
        $merchant = $invoice->merchant;

        $properties = [
            'id'            => $merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.send_sms_on_commission_invoice_issued_exp_id'),
        ];

        $isExpEnabled = (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');

        if($isExpEnabled === false)
        {
            return ;
        }

        $data = $this->getTemplateData($invoice, $pdfPath);

        if((in_array($data['activation_status'], DeStatus::PAYMENTS_ENABLED_STATUSES) === false) || ($merchant->isFundsOnHold() === true))
        {
            return;
        }

        $data ['view'] = $this->getEmailTemplateView($merchant, Status::ISSUED, $data['activation_status']);

        $commissionInvoice = new CommissionInvoiceIssued($data);

        Mail::send($commissionInvoice);
    }

    /**
     * send commission invoice auto-approval email
     *
     * @param   Entity   $invoice    The invoice Entity
     *
     * @param   string|null $pdfPath  invoice pdf path
     *
     * @return  void
     *
     */
    public function sendInvoiceAutoApprovedMail(Entity $invoice, string $pdfPath = null) {
        $data = $this->getTemplateData($invoice, $pdfPath);

        $commissionInvoiceAutoApproved = new CommissionInvoiceAutoApproved($data);

        Mail::send($commissionInvoiceAutoApproved);
    }

    /**
     * send commission invoice auto-approval sms
     *
     * @param   Entity   $invoice    The invoice Entity
     *
     * @param   string|null $pdfPath  invoice pdf path
     *
     * @return  void
     *
     */
    public function sendCommissionAutoApprovedSMS(Entity $invoice, string $pdfPath = null) {
        $merchant = $invoice->merchant;
        $templateName = Commission\Constants::COMMISSION_INVOICE_ISSUED_SMS_TEMPLATE[Commission\Constants::INVOICE_AUTO_APPROVED];
        $data = $this->getTemplateData($invoice, $pdfPath);
        $activationStatus = $data['activation_status'];

        $tracePayload = [
            'partner_id'          => $merchant->getId(),
            'activation_status'   => $activationStatus,
            'sms_template'        => $templateName,
            "invoice_id"          => $invoice->getId(),
            "invoice_month"       => $invoice->getMonth(),
            "invoice_year"        => $invoice->getYear(),
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

                $this->trace->info(TraceCode::SEND_PARTNER_COMMISSION_INVOICE_AUTO_APPROVED_SMS, $tracePayload);

                $this->app->stork_service->sendSms($this->mode, $smsPayload);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL, TraceCode::PARTNER_COMMISSION_INVOICE_COMMUNICATION_SMS_FAILED, $tracePayload);
        }
    }

    public function sendCommissionInvoiceEvents(Entity $invoice, array $eventCode)
    {
        $data = $this->getTemplateData($invoice);

        $eventData = [
            'partner_id'                =>  $data['merchant']['id'],
            'invoice_id'                =>  $invoice->getId(),
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

        if ($this->isLumberJackToKafkaExpEnabled($eventData['partner_id']) === true)
        {
            $this->sendEventToKafkaTopic(Commission\Constants::COMMISSION_EVENTS,
                $eventCode['name'],
                Commission\Constants::COMMISSION_EVENTS_VERSION,
                $eventData);
        }
        else
        {
            $this->app['diag']->trackOnboardingEvent($eventCode, $invoice->merchant, null, $eventData);
        }
    }

    public function sendCommissionProcessedMail(Entity $invoice, string $pdfPath)
    {
        $data = $this->getTemplateData($invoice, $pdfPath);

        $commissionInvoice = new CommissionProcessed($data);

        Mail::send($commissionInvoice);
    }

    public function  sendCommissionReminderMail(Base\PublicCollection $invoices = null, Merchant\Entity $partner = null, string $activationStatus = null)
    {
        $invoiceData = [];

        foreach ($invoices as $invoice)
        {
            $timestamps    = $this->convertMonthAndYearToTimeStamp($invoice->getMonth(), $invoice->getYear());

            $fromTimestamp = $timestamps[Commission\Constants::FROM];
            $endTimestamp  = $timestamps[Commission\Constants::TO];

            $tempData ['gross_amount_spread']   = $this->formatAmountForTemplate($invoice->getGrossAmount(), $invoice->getCurrency());
            $tempData ['start_date']            = Carbon::createFromTimestamp($fromTimestamp, Timezone::IST)->format('d-M-y');
            $tempData ['end_date']              = Carbon::createFromTimestamp($endTimestamp, Timezone::IST)->format('d-M-y');

            $invoiceData[] = [
                    'id'                        => $invoice->getId(),
                    'gross_amount_spread'       => $tempData['gross_amount_spread'][1].'.'.$tempData['gross_amount_spread'][2],
                    'period'                    => $tempData['start_date'].' to '.$tempData['end_date']
            ];
        }

        $data = [
            'merchant'                  => $partner->toArray(),
            'activation_status'         => $activationStatus,
            'invoices'                  => $invoiceData,
            'invoice_count'             => $invoices->count(),
            'view'                      => $this->getEmailTemplateView($partner, Constants::REMINDER, $activationStatus),
            'country_code'              => $partner->getCountry()
        ];

        $this->trace->info(
            TraceCode::SEND_PARTNER_COMMISSION_INVOICE_REMINDER_EMAIL,
            [
                'merchant_id'          => $data['merchant']['id'],
                'activation_status'    => $activationStatus,
                'invoices'             => $invoiceData,
                'invoice_count'        => $data['invoice_count'],
            ]
        );

        $commissionInvoice = new CommissionInvoiceReminder($data);

        Mail::send($commissionInvoice);
    }

    public function sendCommissionReminderSms(int  $count, Merchant\Entity $merchant = null, string $activationStatus = null)
    {
        $templateName     = Commission\Constants::COMMISSION_INVOICE_REMINDER_SMS_TEMPLATE[Merchant\Constants::DEFAULT];

        if(isset(Commission\Constants::COMMISSION_INVOICE_REMINDER_SMS_TEMPLATE[$activationStatus])=== true)
        {
            $templateName = Commission\Constants::COMMISSION_INVOICE_REMINDER_SMS_TEMPLATE[$activationStatus];
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
                        'invoice_count' => $count,
                    ]
                ];

                $this->trace->info(TraceCode::SEND_PARTNER_COMMISSION_INVOICE_REMINDER_SMS, $tracePayload);

                $this->app->stork_service->sendSms($this->mode, $smsPayload);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL, TraceCode::PARTNER_COMMISSION_INVOICE_REMINDER_SMS_FAILED, $tracePayload);
        }
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
        $activationStatus     = $this->getApplicablePartnerActivationStatus($merchant);

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
            'invoice'                  => $invoice->toArrayPublic(),
            'created_at'               => Carbon::createFromTimestamp($invoice->getCreatedAt(), Timezone::IST)->format('d-M-y'),
            'tds_percentage'           => $tdsPercentage/100,
            'activation_status'        => $activationStatus,
            'country_code'             => $invoice->merchant->getCountry(),
        ];

        if (empty($pdfPath) === false)
        {
            $data['file_path'] = $pdfPath;
        }

        $data['invoice']['gross_amount_spread'] = $this->formatAmountForTemplate($data['invoice']['gross_amount'],
            $invoice->getCurrency());
        $data['invoice']['tax_amount_spread'] = $this->formatAmountForTemplate($data['invoice']['tax_amount'],
            $invoice->getCurrency());

        foreach ($data['invoice']['line_items'] as $key => &$lineItem)
        {
            if (empty($lineItem['taxes']) === false)
            {
                foreach ($lineItem['taxes'] as &$tax)
                {
                    $tax['tax_amount_spread'] = $this->formatAmountForTemplate($tax['tax_amount'],
                        $invoice->getCurrency());
                }
            }

            $lineItem['gross_amount_spread'] = $this->formatAmountForTemplate($lineItem['gross_amount'],
                $invoice->getCurrency());
            $lineItem['tax_amount_spread'] = $this->formatAmountForTemplate($lineItem['tax_amount'],
                $invoice->getCurrency());
            $lineItem['net_amount_spread'] = $this->formatAmountForTemplate($lineItem['net_amount'],
                $invoice->getCurrency());

            $subTotal = $lineItem['gross_amount'] - $lineItem['tax_amount'];
            $lineItem['sub_total_spread'] = $this->formatAmountForTemplate($subTotal, $invoice->getCurrency());
        }

        return $data;
    }

    /**
     * The function will return the applicable activation status of the partner based on partner type and merchant activation status.
     * For Reseller partner without merchant activated will return partner_activation status and in all other cases merchant activation status will be returned.
     *
     * @param   Merchant\Entity     $merchant   The partner merchant entity
     *
     * @return  string|null             response will be activation status of the partner
     *
     */
    public function getApplicablePartnerActivationStatus(Merchant\Entity $merchant)
    {
        $activationStatus     = $merchant->merchantDetail->getActivationStatus();
        $partnerType          = $merchant->getPartnerType();

        if ( empty($partnerType) === true)
        {
            return null;
        }

        //pick activation status from partner_activation entity for reseller if partner is not merchant activation status is not submitted.
        if (($partnerType === Merchant\Constants::RESELLER) and (empty($activationStatus) === true))
        {
            $activationStatus = ($merchant->partnerActivation !== null) ? $merchant->partnerActivation->getActivationStatus() : null;
        }
        return $activationStatus;
    }

    protected function formatAmountForTemplate($amount, $currency)
    {
        $currencySymbol = Currency::SYMBOL[$currency];

        $denominationFactor = Currency::DENOMINATION_FACTOR[$currency] ?: 100;

        $rupeesInAmount = money_format_IN((integer)($amount / $denominationFactor));

        $paiseInAmount = str_pad($amount % $denominationFactor, 2, 0, STR_PAD_LEFT);

        return [$currencySymbol, $rupeesInAmount, $paiseInAmount];
    }

    public function triggerWorkflowAction(Entity $invoice, Merchant\Entity $merchant)
    {

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
        if($this->isCommissionInvoiceReverseShadowOrCutoffEnabled($partner->getId()))
        {
            $this->trace->info(
                TraceCode::COMMISSION_INVOICE_GENERATE_SKIP_REVERSE_SHADOW,
                [
                    'partner' => $partner->getId(),
                ]);
            return ;
        }
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

    /**
     * Generate invoice for partners in bulk for given month and year
     *
     * @param array $input
     *
     * @return array
     */
    public function bulkGenerateCommissionInvoice(array $input): array
    {
        $summary = [
            'failed_ids'    => [],
            'failed_count'  => 0,
            'success_count' => 0,
        ];

        [$newTncPartners, $oldTncPartners] = $this->fetchPartnerWithOldAndNewTnc($input['merchant_ids']);

        if(count($newTncPartners)>0)
        {
            $newTncPartnerIds = $newTncPartners->getIds();

            $invoiceMonth = $this->getInvoiceMonthString($input);

            try
            {
                $partnerSubMtuArray = $this->repo->commission_invoice->fetchPartnerSubMtuCountFromDataLake($newTncPartnerIds, $invoiceMonth);
                $partnerSubMtuMap = array_combine(array_column($partnerSubMtuArray, 'partner_id'), array_column($partnerSubMtuArray, 'mtu_count'));

                foreach ($newTncPartners as $partner)
                {
                    try
                    {
                        if(
                            empty($partnerSubMtuMap[$partner->getId()]) === false and
                            $partnerSubMtuMap[$partner->getId()] >= Constants::GENERATE_INVOICE_MIN_SUB_MTU_COUNT
                        )
                        {
                            $this->generateInvoice($partner, $input);
                        }
                        else
                        {
                            $this->trace->info(TraceCode::COMMISSION_INVOICE_SKIPPED_SUB_MTU_LIMIT,
                                               ['partner_id'=>$partner->getId(), 'mtu_count'=>$partnerSubMtuMap[$partner->getId()]]);

                            $this->trace->count(PartnerMetric::COMMISSION_INVOICE_SKIPPED_SUB_MTU_LIMIT, ['month' => $input[Entity::MONTH]]);
                        }

                        $summary['success_count']++;
                    }
                    catch (\Throwable $e)
                    {
                        $summary['failed_count']++;
                        $summary['failed_ids'][] = $partner->getId();

                        $this->trace->traceException($e, Trace::ERROR, TraceCode::COMMISSION_INVOICE_GENERATE_ERROR, ['id' => $partner->getId()]);

                        $this->trace->count(PartnerMetric::COMMISSION_INVOICE_GENERATION_FAILED_TOTAL);
                    }
                }

            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::PARTNER_SUB_MTU_COUNT_FETCH_ERROR, ['partnerIds' => $newTncPartnerIds]);

                $this->trace->count(PartnerMetric::FETCH_PARTNER_SUB_MTU_COUNT_FAILED_TOTAL);

                $summary['failed_count'] = $summary['failed_count']+count($newTncPartners);
            }
        }
        // TODO: to be removed after TNC changes released to all partners
        foreach ($oldTncPartners as $partner)
        {
            try
            {
                $this->generateInvoice($partner, $input);
                $summary['success_count']++;
            }
            catch (\Throwable $e)
            {
                $summary['failed_count']++;
                $summary['failed_ids'][] = $partner->getId();

                $this->trace->traceException($e, Trace::ERROR, TraceCode::COMMISSION_INVOICE_GENERATE_ERROR, ['id' => $partner->getId()]);

                $this->trace->count(PartnerMetric::COMMISSION_INVOICE_GENERATION_FAILED_TOTAL);
            }
        }
        return $summary;
    }

    /**
     * get invoice month string to fetch from datalake
     *
     * @param array $input
     *
     * @return string
     */
    protected function getInvoiceMonthString(array $input): string
    {
        $previousMonth = Carbon::now(Timezone::IST)->subMonth();
        $year            = $input[Entity::YEAR] ?? $previousMonth->year;
        $month           = $input[Entity::MONTH] ?? $previousMonth->month;

        return $year.'-'. $month;
    }

    /**
     * Fetch partners created before and after tnc update
     *
     * @param array $merchantIds
     *
     * @return array
     */
    protected function fetchPartnerWithOldAndNewTnc(array $merchantIds): array
    {
        $partners = $this->repo->merchant->findManyOnReadReplica($merchantIds);
        /* Skip new Tnc for malaysain merchants
         * We don't want malaysian merchants to go through the new flow because we don't want to add any constraint of
         * number of sub-merchants that partner have.
         */
        $newTncPartners = $partners->filter(function ($partner)  {
            return (
                $partner->getCreatedAt() >= Constants::INVOICE_TNC_UPDATED_TIMESTAMP and
                $partner->getCountry() !== 'MY'
            );
        });
        $oldTncPartners = $partners->diff($newTncPartners);

        return [$newTncPartners,$oldTncPartners];
    }

    protected function generate(Merchant\Entity $partner, array $input): bool
    {
        //skip invoice generation for the prev month if the partner is unmarked as partner in current/prev month
        if($partner->isPartner() == false)
        {
            $this->trace->info(TraceCode::COMMISSION_INVOICE_GENERATION_SKIPPED, [
                'partner_id' => $partner->getId()
            ]);

            return false;
        }

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

            return false;
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

                return false;
            }
        }

        $invoiceCreateInput = [
            Entity::MONTH => $month,
            Entity::YEAR  => $year,
        ];

        $invoice = $this->build($partner, $invoiceCreateInput);

        $this->repo->transaction(function() use ($partner, $invoice, $month, $year, $regenerateIfExists, $forceRegenerate) {

            $created = $this->createLineItemsForInvoice($partner, $invoice, $month, $year);

            if ($created === false)
            {
                $this->trace->info(TraceCode::COMMISSION_INVOICE_SKIPPED_LINE_ITEMS_NOT_CREATED,
                    [
                        'partner'     => $partner->getId(),
                    ]);

                return false;
            }

            $this->updateInvoiceAmounts($invoice);
            $this->repo->saveOrFail($invoice);

            $isPartnerAutoApprovalEnabled = $this->isPartnerInvoiceAutoApprovalEnabled($partner, $invoice);

            if ($isPartnerAutoApprovalEnabled)
            {
                $this->trace->info(
                    TraceCode::COMMISSION_INVOICE_PARTNER_AUTO_APPROVED,
                    [
                        "partner_id" => $partner->getId(),
                        "invoice_id" => $invoice->getId()
                    ]
                );

                $invoiceStatusRequest = [Entity::ACTION => Status::UNDER_REVIEW, Commission\Constants::INVOICE_AUTO_APPROVED => $isPartnerAutoApprovalEnabled];
                $this->changeInvoiceStatus($invoice, $invoiceStatusRequest);
                $this->trace->count(Metric::COMMISSION_INVOICE_AUTO_APPROVED);
            }
            if ($invoice->isIssued() === true)
            {
                CommissionInvoiceAction::dispatch($this->mode, $invoice->getStatus(), $invoice->getId())->delay(self::COMMISSION_INVOICE_ACTION_DELAY);
            }
            $this->app->partnerships->createInvoiceShadowPhase($invoice, $month, $year, $regenerateIfExists||$forceRegenerate);
        });

        return true;
    }

    public function isPartnerInvoiceAutoApprovalEnabled(Merchant\Entity $partner, Entity $invoice): bool
    {
        if ($partner->getCountry() == 'MY')
        {
            return false;
        }

        if ($invoice->getYear() <= 2022)
        {
            return false;
        }
        if (!$this->isPartnerInvoiceAutoApprovalExpEnabled($partner->getId(), $invoice->getYear()))
        {
            return false;
        }
        if ($invoice->getGrossAmount() > Entity::MAX_AUTO_APPROVAL_AMOUNT)
        {
            return false;
        }
        if ($partner->isPartnerInvoiceAutoApprovalDisabled())
        {
            return false;
        }

        $merchant = $invoice->merchant;
        $merchantDetail = $merchant->merchantDetail;

        if ($merchantDetail === null or $this->checkPartnerActivationStatus($partner, $merchantDetail) === false)
        {
            return false;
        }

        // If partner is reseller auto approval is enabled even if GSTIN is available
        // For partner types other than reseller if GSTIN is available then auto approval not applicable
        if (empty($merchantDetail->getGstin()) === false)
        {
            $isPartnerEligible =  in_array($merchant->getPartnerType(), Constants::GSTIN_AUTO_APPROVAL_ENABLED_PARTNER_TYPES);
            $isInvoiceEligible = $invoice->getMonth() > 4 and $invoice->getYear() > 2022;
            return $isPartnerEligible && $isInvoiceEligible;
        }
        return true;
    }

    private function checkPartnerActivationStatus(Merchant\Entity $partner, MerchantDetail\Entity $merchantDetail): bool
    {
        $partnerType = $partner->getPartnerType();

        if ($partnerType === Merchant\Constants::RESELLER)  {
            $activationStatus = ($partner->partnerActivation !== null) ? $partner->partnerActivation->getActivationStatus() : null;
            return $activationStatus === PartnerActivationConstants::ACTIVATED;
        }

        return $merchantDetail->getActivationStatus() === MerchantDetail\Status::ACTIVATED;
    }

    public function canPartnerApproveInvoice(): array
    {
        $merchant = $this->merchant;

        $isActivated = $this->checkPartnerActivationStatus($merchant, $merchant->merchantDetail);

        return ['can_approve' => $isActivated];
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

    public function getStartTimeForCommissionInvoiceReminders() : int
    {
        $year        = Carbon::now()->year;
        $month       = Carbon::now()->month;

        $year  = ($month < 4) ? $year-1 : $year ; // Decrease year for Jan, Feb and Mar.

        $month = ($month > 3 and $month < 7) ? 2 : 5 ;

        return Carbon::createFromDate($year, $month, 1, Timezone::IST)->startOfMonth()->getTimestamp();
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
            $totalSum += $aggregateSum['credit']-$aggregateSum['debit'];
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
            $amount = $aggregateSum['credit']-$aggregateSum['debit'];

            if ($amount <= 0) {
                continue;
            }

            // if line item amount is less than 100 paisa, skip creating the line item
            if ($amount < 100)
            {
                $this->trace->info(TraceCode::COMMISSION_INVOICE_LINE_ITEMS_CREATE_SKIPPED, [
                    'reason' => 'line item amount less than 100',
                    'amount' => $amount,
                ]);
                continue;
            }

            $lineItem = [
                LineItem\Entity::AMOUNT        => $amount,
                LineItem\Entity::CURRENCY      => $partner->getCurrency(),
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
                $taxComponents = Calculator\Tax\IN\Utils::getTaxComponents($partner);

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

    /**
     * Checks whether merchant is allowed for auto invoice disbursal.
     *
     * @param string $merchantId
     *
     * @return bool
     */
    private function isPartialFinanceApprovalRemovalExpEnabled(string $merchantId): bool
    {
        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.finance_approval_removal_exp_id'),
        ];

        return (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');
    }

    /**
     * Checks whether event is allowed directly to kafka topic instead of lumberjack.
     *
     * @param string $merchantId
     *
     * @return bool
     */
    private function isLumberJackToKafkaExpEnabled(string $merchantId): bool
    {
        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.commission_invoice_events_to_kafka_exp_id'),
        ];

        return (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');
    }

    /**
     * Checks whether partner is allowed for auto invoice generation.
     *
     * @param string $merchantId
     *
     * @return bool
     */
    private function isPartnerInvoiceAutoApprovalExpEnabled(string $partnerId, int $invoiceYear): bool
    {

        $properties = [
            'id'            => $partnerId,
            'experiment_id' => $this->app['config']->get('app.partner_invoice_auto_approval_exp_id'),
            'request_data'  => json_encode([
                'invoice_year' => strval($invoiceYear),
                'mid' => $partnerId,
                ]),
        ];

        return (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');
    }

    /**
     * sends event to kafka topic
     *
     * @param string $eventType
     * @param string $eventName
     * @param string $eventVersion
     * @param array  $properties
     *
     */
    private function sendEventToKafkaTopic(string $eventType, string $eventName, string $eventVersion, array $properties)
    {
        $now             = Carbon::now()->timestamp;
        $event           = [
            'event_type'         => $eventType,
            'event_name'         => $eventName,
            'version'            => $eventVersion,
            'event_timestamp'    => $now,
            'producer_timestamp' => $now,
            'source'             => "commission_invoice",
            'mode'               => $this->mode,
            'properties'         => $properties,
            'context'            => [
                'request_id' => $this->app['request']->getId(),
                'task_id'    => $this->app['request']->getTaskId()
            ],
        ];
        (new KafkaProducer(Commission\Constants::COMMISSIONS_EVENTS_TOPIC . $this->mode, stringify($event)))->Produce();
    }

    /**
     * Returns the email template view  based on the event and activation status.
     */
    public function getEmailTemplateView(Merchant\Entity $merchant, string $event, string $activationStatus = null)
    {
        $partnerType = $merchant->getPartnerType();

        switch ($partnerType)
        {
            case  Merchant\Constants::RESELLER:
                return $this->getResellerEmailTemplate($merchant, $event, $activationStatus);

            default:
                return $this->getDefaultEmailTemplate($event, $activationStatus, $merchant->getCountry());
        }
    }

    private function getResellerEmailTemplate(Merchant\Entity $merchant, string $event, string $activationStatus = null)
    {
        $isResellerPartnerWithMerchantKyc = (new PartnerCore())->isResellerPartnerWithMerchantKyc($merchant);

        if($isResellerPartnerWithMerchantKyc === true)
        {
            return $this->getDefaultEmailTemplate($event, $activationStatus, $merchant->getCountry());
        }

        if($event === Status::ISSUED)
        {
            return Constants::RESELLER_PARTNER_INVOICE_ISSUED_EMAIL_TEMPLATE;
        }

        $templateSuffix =  $activationStatus;

        if(in_array($activationStatus, Commission\Constants::VALID_PARTNER_STATUS_EMAIL_TEMPLATES) === false)
        {
            $templateSuffix = Merchant\Constants::DEFAULT;
        }
        return Constants::RESELLER_PARTNER_INVOICE_REMINDER_EMAIL_TEMPLATE_PREFIX.'.'.$templateSuffix;
    }

    private function getDefaultEmailTemplate(string $event, string $activationStatus = null, $countryCode = 'IN')
    {

        if($event === Status::ISSUED)
        {
            if ($countryCode === 'MY')
            {
                return Constants::DEFAULT_PARTNER_INVOICE_ISSUED_EMAIL_TEMPLATE_MY_REGION;
            }
            else
            {
                return Constants::DEFAULT_PARTNER_INVOICE_ISSUED_EMAIL_TEMPLATE;
            }
        }

        $templateSuffix =  $activationStatus;

        if(in_array($activationStatus, Commission\Constants::VALID_PARTNER_STATUS_EMAIL_TEMPLATES) === false)
        {
            $templateSuffix = Merchant\Constants::DEFAULT;
        }
        return (Constants::DEFAULT_PARTNER_INVOICE_REMINDER_EMAIL_TEMPLATE_PREFIX.'.'.$templateSuffix);
    }

    // Below function are used for reverse shadow and cutoff
    /**
     * This flow is called from reverse-shadow of commissions-invoice issued
     * This will create commission invoice in idempotent way
     * This will get called from kafka job processor and also as API call from reverse-shadow retry cron
     *
     * @param array $input
     *    {
     *    "id": "outbox_id",
     *    "payload" : "{}",
     *    "created_at" : 0,
     *    }
     *
     * @return array
     */
    public function createIssuedFromPRTS(array $input)
    {
        try
        {
            $payloadStr = $input[Constants::PAYLOAD];
            $payload    = json_decode($payloadStr, true);

            $this->trace->info(TraceCode::PRTS_COMMISSION_INVOICE_PROCESS_REQUEST, ['payload' => $payload]);

            $regenerate = $payload[Entity::REGENERATE_IF_EXISTS] ?? false;

            $invoice = $this->createInvoiceForPRTS($payload['Invoice'], $regenerate);

            return $this->buildAckResponse(['processed' => true], null, $input[Entity::ID], $input[Constants::CREATED_AT]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_INVOICE_CREATE_FAILED,
                [
                    'mode'  => $this->mode,
                    'input' => $input,
                ]
            );
            $error = $this->buildError($e->getCode(), $e->getMessage());

            return $this->buildAckResponse(null, $error, $input[Entity::ID], $input[Constants::CREATED_AT]);
        }
    }

    public function createInvoiceAndFinanceWorkflowFromPRTS(array $input)
    {
        try
        {
            $payloadStr = $input[Constants::PAYLOAD];
            $payload    = json_decode($payloadStr, true);

            $this->trace->info(TraceCode::PRTS_COMMISSION_INVOICE_PROCESS_REQUEST, ['payload' => $payload]);

            $invoice = $this->createInvoiceForPRTS($payload['Invoice']);

            return $this->createFinanceWorkflowFromPRTS($input);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_INVOICE_CREATE_FAILED,
                [
                    'mode'  => $this->mode,
                    'input' => $input,
                ]
            );
            $error = $this->buildError($e->getCode(), $e->getMessage());

            return $this->buildAckResponse(null, $error, $input[Entity::ID], $input[Constants::CREATED_AT]);
        }
    }

    public function createFinanceWorkflowFromPRTS(array $input)
    {
        try
        {
            $payloadStr = $input[Constants::PAYLOAD];
            $payload    = json_decode($payloadStr, true);

            $this->trace->info(TraceCode::PRTS_COMMISSION_INVOICE_PROCESS_REQUEST, ['payload' => $payload]);

            Tracer::inspan(['name' => HyperTrace::TRIGGER_COMMISSION_INVOICE_ACTION, 'attributes' => $payload], function () use ($payload) {

                $this->triggerFinanceWorkflowAction($payload['Invoice'], $payload['TdsPercentage']);
            });

            return $this->buildAckResponse(['processed' => true], null, $input[Entity::ID], $input[Constants::CREATED_AT]);
        }
        catch (\Throwable $e)
        {
            if (($e instanceof Exception\EarlyWorkflowResponse) === true)
            {
                return $this->buildAckResponse(['processed' => true], null, $input[Entity::ID], $input[Constants::CREATED_AT]);
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_INVOICE_WORKFLOW_CREATE_FAILED,
                [
                    'mode'  => $this->mode,
                    'input' => $input,
                ]
            );

            $error = $this->buildError($e->getCode(), $e->getMessage());

            return $this->buildAckResponse(null, $error, $input[Entity::ID], $input[Constants::CREATED_AT]);
        }
    }

    public function createInvoiceAndSettlementTDSFromPRTS(array $input)
    {
        try
        {
            //create Invoice
            $payloadStr = $input[Constants::PAYLOAD];
            $payload    = json_decode($payloadStr, true);

            $this->trace->info(TraceCode::PRTS_COMMISSION_INVOICE_PROCESS_REQUEST, ['payload' => $payload]);

            $invoice = $this->createInvoiceForPRTS($payload['Invoice']);

            $this->settlementTDSForPRTS($payload['Invoice'], $payload['TdsPercentage'], $payload['CreateTds']);

            $invoice->setStatus(Status::PROCESSED);

            $this->repo->saveOrFail($invoice);

            return $this->buildAckResponse(['processed' => true], null, $input[Entity::ID], $input[Constants::CREATED_AT]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_INVOICE_SETTLEMENT_FAILED,
                [
                    'mode'  => $this->mode,
                    'input' => $input,
                ]
            );
            $error = $this->buildError($e->getCode(), $e->getMessage());

            return $this->buildAckResponse(null, $error, $input[Entity::ID], $input[Constants::CREATED_AT]);
        }
    }

    public function settlementTDSFromPRTS(array $input)
    {
        try
        {
            $payloadStr = $input[Constants::PAYLOAD];
            $payload    = json_decode($payloadStr, true);

            $this->trace->info(TraceCode::PRTS_COMMISSION_INVOICE_PROCESS_REQUEST, ['payload' => $payload]);

            $this->settlementTDSForPRTS($payload['Invoice'],$payload['TdsPercentage'],$payload['CreateTds']);

            return $this->buildAckResponse(['processed' => true], null, $input[Entity::ID], $input[Constants::CREATED_AT]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_INVOICE_SETTLEMENT_FAILED,
                [
                    'mode'  => $this->mode,
                    'input' => $input,
                ]
            );
            $error = $this->buildError($e->getCode(), $e->getMessage());

            return $this->buildAckResponse(null, $error, $input[Entity::ID], $input[Constants::CREATED_AT]);
        }
    }

    /**
     * @param $payload
     * @param bool $regenerate
     * @return Entity
     */
    private function createInvoiceForPRTS($payload, bool $regenerate = false): Entity
    {
        $resource   = 'COMMISSION_INVOICE_CREATE_' . $payload[Entity::ID];

        return $this->app['api.mutex']->acquireAndRelease(
            $resource, function() use ($payload, $regenerate)
            {
                $invoices = $this->repo->commission_invoice->fetchInvoices(
                    $payload[Entity::MERCHANT_ID],
                    $payload[Entity::MONTH],
                    $payload[Entity::YEAR]);

                if ($invoices->isEmpty() === false)
                {
                    $invoice = $invoices->first();
                }

                if ($regenerate)
                {
                    foreach ($invoices as $invoice)
                    {
                        // delete the existing invoices
                        $this->repo->deleteOrFail($invoice);
                    }
                    unset($invoice);
                }

                if (isset($invoice) === false)
                {
                    $timeNow = millitime();

                    $invoice = $this->repo->transaction(function() use ($payload)
                    {
                        // save commission invoice
                        $invoice = new Entity;
                        $invoice->fillSelectAttributes($payload, Entity::$prtsFillable);
                        $this->repo->saveOrFail($invoice);

                        // save commission line items
                        foreach ($payload['line_items'] as $lineIt)
                        {
                            // add merchant id in line items
                            $lineIt[LineItem\Entity::MERCHANT_ID] = $payload[LineItem\Entity::MERCHANT_ID];
                            $lineIt[LineItem\Entity::AMOUNT] = $lineIt[LineItem\Entity::GROSS_AMOUNT];
                            $lineIt[LineItem\Entity::NET_AMOUNT] = $lineIt[LineItem\Entity::GROSS_AMOUNT];
                            $lineIt[LineItem\Entity::QUANTITY] = 1;

                            $lineItem = new LineItem\Entity;
                            $lineItem->fillSelectAttributes($lineIt, LineItem\Entity::$prtsFillable);
                            $this->repo->saveOrFail($lineItem);

                            // save commission line items tax
                            foreach ($lineIt['Taxes'] as $lineItemTx)
                            {
                                $lineItemTx[LineItemTax\Entity::TAX_AMOUNT] = $lineItemTx[LineItem\Entity::AMOUNT];

                                $lineItemTax = new LineItemTax\Entity;
                                $lineItemTax->fillSelectAttributes($lineItemTx, LineItemTax\Entity::$prtsFillable);
                                $this->repo->saveOrFail($lineItemTax);
                            }
                        }

                        // save filestore
                        $filestore = [
                            FileStore\Entity::MERCHANT_ID => $payload[FileStore\Entity::MERCHANT_ID],
                            FileStore\Entity::TYPE => FileStore\Type::COMMISSION_INVOICE,
                            FileStore\Entity::ENTITY_ID => $payload[FileStore\Entity::ID],
                            FileStore\Entity::ENTITY_TYPE => FileStore\Type::COMMISSION_INVOICE,
                            FileStore\Entity::EXTENSION => FileStore\Format::PDF,
                            FileStore\Entity::MIME => 'application/pdf',
                            FileStore\Entity::SIZE => 1,//read it later from request
                            FileStore\Entity::NAME => str_replace(".pdf", "", $payload['file_details']['location'] . $payload['file_details']['fileName']),
                            FileStore\Entity::STORE => FileStore\Store::S3,
                            FileStore\Entity::LOCATION => $payload['file_details']['location'] . $payload['file_details']['fileName'],
                            FileStore\Entity::BUCKET => $payload['file_details']['bucketName'],
                            FileStore\Entity::REGION => 'ap-south-1',
                            FileStore\Entity::CREATED_AT => $payload[FileStore\Entity::CREATED_AT],
                            FileStore\Entity::UPDATED_AT => $payload[FileStore\Entity::UPDATED_AT],
                        ];

                        $file = new FileStore\Entity;
                        $file->fillSelectAttributes($filestore, FileStore\Entity::$prtsFillable);
                        $this->repo->saveOrFail($file);

                        return $invoice;
                    });

                    $lag = $timeNow - $payload[Constants::CREATED_AT];
                    $this->trace->histogram(PartnerMetric::REVERSE_SHADOW_COMMISSION_INVOICE_CREATE_LAG, $lag, ['mode' => $this->mode]);

                }
                else
                {
                    $this->trace->info(
                        TraceCode::COMMISSION_INVOICE_ALREADY_CREATED,
                        [
                            'commission_id' => $invoice->getId(),
                            'mode'          => $this->mode,
                        ]
                    );

                    if ($invoice->getStatus() != $payload[Entity::STATUS])
                    {
                        $invoice->setStatus($payload[Entity::STATUS]);

                        $this->repo->saveOrFail($invoice);
                    }
                }

                return $invoice;
            },
            self::COMMISSION_INVOICE_MUTEX_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

    private function triggerFinanceWorkflowAction($payload, $tdsPercentage)
    {
        $routePermission = Permission::COMMISSION_PAYOUT;

        $this->trace->info(
            TraceCode::COMMISSION_INVOICE_ACTION_TRIGGER_WORKFLOW,
            [
                'invoice_id' => $payload[Entity::ID],
                'merchant_id' => $payload[Entity::MERCHANT_ID],
            ]);


        $partner         = $this->repo->merchant->findOrFail($payload[Entity::MERCHANT_ID]);
        $totalCommission = $payload[Entity::GROSS_AMOUNT] - $payload[Entity::TAX_AMOUNT];
        $totalTax        = $payload[Entity::TAX_AMOUNT];

        if(isset($tdsPercentage) && $tdsPercentage['IsSet'] === true)
        {
            $tdsPer = $tdsPercentage['Value'];

            $totalTds = (int) round(($tdsPer * $totalCommission) / 10000);
        }
        else
        {
            $core = new Commission\Core;

            list($totalTds, $tdsPer) = $core->calculateTds($partner, $totalCommission);
        }

        $netAmount       = $totalCommission + $totalTax - $totalTds;

        $dirtyData = [
            Entity::ID          => [$payload[Entity::ID]],
            Entity::MERCHANT_ID => [$payload[Entity::MERCHANT_ID]],
            Entity::MONTH       => [$payload[Entity::MONTH]],
            Entity::YEAR        => [$payload[Entity::YEAR]],
            Entity::STATUS      => Status::APPROVED,
            Commission\Constants::TOTAL_TAX        => $totalTax,
            Commission\Constants::TOTAL_TDS        => $totalTds,
            Commission\Constants::TOTAL_COMMISSION => $totalCommission,
            Commission\Constants::TOTAL_NET_AMOUNT => $netAmount,
            Commission\Constants::TDS_PERCENTAGE   => ($tdsPer/100),
        ];

        // test for both retry and ack approval. It'll break while callback the url
        $this->app['workflow']
            ->setPermission($routePermission)
            ->setRouteName('commissions_invoice_status_change')
            ->setRouteParams(['id' => $payload[Entity::ID]])
            ->setWorkflowMaker($partner)
            ->setWorkflowMakerType('merchant')
            ->setMakerFromAuth(false)
            ->setImitateProxyAuth(true)
            ->setController('RZP\Http\Controllers\CommissionInvoiceController@changeStatus')
            ->setMethod('PUT')
            ->setDirty($dirtyData)
            ->setInput([Entity::ACTION => Status::APPROVED])
            ->setEntityAndId('commission_invoice', $payload[Entity::ID])
            ->handle();
    }

    public function settlementTDSForPRTS($payload, $tdsPercentage, $createTds = true)
    {
        $resource   = 'COMMISSION_INVOICE_SETTLEMENT_' . $payload[Entity::ID];

        $this->app['api.mutex']->acquireAndRelease(
            $resource,
            function () use ($payload, $tdsPercentage, $createTds)
            {
                $timeStarted = microtime(true);
                $partnerId = $payload[Entity::MERCHANT_ID];
                $timestamps    = $this->convertMonthAndYearToTimeStamp($payload[Entity::MONTH], $payload[Entity::YEAR]);

                $fromTimestamp = $timestamps[Commission\Constants::FROM];
                $endTimestamp  = $timestamps[Commission\Constants::TO];

                $core = new Commission\Core;

                $afterId = null;

                $partner = $this->repo->merchant->findOrFail($partnerId);

                $batchCount = 1;

                while (true)
                {
                    $this->trace->info(TraceCode::COMMISSION_TRANSACTION_FETCH_START, ['batch_count' => $batchCount]);

                    $comm_transactions = $this->repo->commission->getTransactionIDFromCommissions(
                        $partnerId,
                        $fromTimestamp,
                        $endTimestamp,
                        self::COMMISSIONS_TRANSACTION_FETCH_LIMIT,
                        $afterId
                    );
                    $transactions = $comm_transactions['transaction_ids'];
                    if (empty($transactions)) {
                        break;
                    }
                    $afterId = $comm_transactions['after_id'];

                    CommissionOnHoldClear::dispatch($this->mode, $transactions);

                    $batchCount++;
                }

                if ($createTds === true)
                {
                    $totalCommission = $payload[Entity::GROSS_AMOUNT] - $payload[Entity::TAX_AMOUNT];
                    $totalTax        = $payload[Entity::TAX_AMOUNT];

                    if(isset($tdsPercentage) && $tdsPercentage['IsSet'] === true)
                    {
                        $tdsPercentage = $tdsPercentage['Value'];

                        $totalTds = (int) round(($tdsPercentage * $totalCommission) / 10000);
                    }
                    else
                    {
                        list($totalTds, $tdsPercentage) = $core->calculateTds($partner, $totalCommission);
                    }

                    $summary['total_tax']        = $totalTax;
                    $summary['total_commission'] = $totalCommission;
                    $summary['total_tds']        = $totalTds;
                    $summary['tds_percentage']   = $tdsPercentage;

                    $this->trace->info(TraceCode::COMMISSION_TDS_SETTLEMENT_SUMMARY, $summary);

                    if ($totalTds > 0)
                    {
                        $core->createCommissionTds($partner, $totalTds, $payload[Entity::ID]);
                    }
                }
            },
            static::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_COMMISSION_TDS_SETTLEMENT_OPERATION_IN_PROGRESS);
    }

    private function buildAckResponse(?array $response = null, ?array $error = null, ?string $id = null, ?int $createdAt = null): array
    {
        return [
            "id"         => $id ?? null,
            "response"   => $response ?? null,
            "created_at" => $createdAt ?? null,
            "error"      => $error ?? null,
        ];
    }

    private function buildError(string $code, string $message): array
    {
        return [
            "code"    => $code,
            "message" => $message,
        ];
    }

    public function getCommissionInvoiceExperimentMode(string $partnerId): ?string
    {
        try
        {
            $requestData = ['mid' => $partnerId, 'mode' => $this->mode];

            $properties = [
                'id'            => $partnerId,
                'experiment_id' => $this->app['config']->get('app.prts_commission_invoice_exp_id'),
                'request_data'  => json_encode($requestData),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            return $response['response']['variant']['name'] ?? '';
        }
        catch (\Exception $e)
        {
            $id        = $properties['id'] ?? null;
            $traceCode = $traceCode ?? TraceCode::SPLITZ_ERROR;
            $this->trace->traceException($e, Trace::ERROR, $traceCode, ['id' => $id]);

            return '';
        }
    }

    public function isCommissionInvoiceReverseShadowEnabled(string $partnerId) : bool
    {
        $variant = $this->getCommissionInvoiceExperimentMode($partnerId);
        return (isset($variant) === true and $variant == 'reverse-shadow');
    }

    public function isCommissionInvoiceReverseShadowOrCutoffEnabled(string $partnerId) : bool
    {
        $variant = $this->getCommissionInvoiceExperimentMode($partnerId);
        return (isset($variant) === true and ($variant == 'reverse-shadow' or $variant == 'cutoff'));
    }
}
