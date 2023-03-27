<?php

namespace RZP\Jobs;

use App;
use RZP\Models\Payment;
use RZP\Models\Pricing\Plan;
use RZP\Trace\TraceCode;
use RZP\Models\PaymentLink;
use Illuminate\Support\Str;
use RZP\Models\Merchant;
use Rzp\Models\PaymentLink\CustomDomain\Plans as CDSPlan;

/**
 * - Asynchronously update payment page, generate receipt etc after a successful payment
 */
class PaymentPageProcessor extends Job
{
    const RETRY_DELAY           = 5;
    const MAX_RETRY_ATTEMPTS    = 5;

    const PAYMENT_CAPTURE_EVENT     = 'PAYMENT_CAPTURE_EVENT';
    const REFUND_PROCESSED_EVENT    = 'REFUND_PROCESSED_EVENT';
    const PAYMENT_HANDLE_CREATION   = 'PAYMENT_HANDLE_CREATION';

    const PAYMENT_PAGE_CREATE_DEDUPE    = 'PAYMENT_PAGE_CREATE_DEDUPE';
    const PAYMENT_PAGE_HOSTED_CACHE     = 'PAYMENT_PAGE_HOSTED_CACHE';

    const CDS_UPDATE_PLAN_IDS_FOR_MERCHANTS  = 'CDS_UPDATE_PLAN_IDS_FOR_MERCHANTS';
    const CDS_PLANS_BILLING_DATE_UPDATE      = 'CDS_PLANS_BILLING_DATE_UPDATE';

    // Once all slugs are migrated this const will be removed
    const NOCODE_CUSTOM_URL_UPSERT_FROM_HOSTED_FLOW = 'NOCODE_CUSTOM_URL_UPSERT_FROM_HOSTED_FLOW';

    const NO_CODE_APPS_PAYMENT_EVENT = 'NO_CODE_APPS_PAYMENT_EVENT';

    /**
     * {@inheritDoc}
     */
    protected $queueConfigKey = 'payment_page_generic';

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $params;

    /**
     * @var \RZP\Models\Merchant\Entity
     */
    protected $merchant;

    protected $core;

    protected $event;

    protected $service;

    protected $context;

    /**
     * Params should have payment key with payment object during payment capture event
     *
     * Params should have refund_id key with a string value which should refer to a valid refund id during
     * refund processed event
     *
     * @param string $mode
     * @param array  $params
     */
    public function __construct(string $mode, array $params)
    {
        parent::__construct($mode);

        $this->params   = collect($params);
        $this->event    = $this->params->get('event', self::PAYMENT_CAPTURE_EVENT);
        $this->merchant = $this->params->get('merchant');
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    public function handle()
    {
        parent::handle();

        $this->context = [
            'mode'  => $this->mode,
            'event' => $this->getEvent()
        ];

        // time taken in milliseconds for a worker to pick job
        $timeTakenToPickJobInMilliSecs  =  millitime() - $this->params->get('start_time');

        $this->trace->histogram(PaymentLink\Metric::PAYMENT_PAGE_PROCESSOR_TIME_TAKEN_TO_PICK_JOB,
            $timeTakenToPickJobInMilliSecs, $this->context);

        $handler = "handle" . Str::studly(Str::lower($this->getEvent()));

        if (! method_exists($this, $handler))
        {
            $this->trace->info(TraceCode::PAYMENT_LINK_POST_PROCESSOR_INVALID_EVENT, $this->context);
            return;
        }

        $this->trace->info(TraceCode::PAYMENT_LINK_POST_PROCESSOR_START, $this->context);

        $start = millitime();

        $this->$handler();

        // this is the time taken to complete the task only
        $jobCompleteDelay = millitime() - $start;

        $this->trace->histogram(
            PaymentLink\Metric::PAYMENT_PAGE_PROCESSOR_TIME_TAKEN_TO_COMPLETE_TASK,
            $jobCompleteDelay,
            $this->context
        );

        $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_COUNT_TOTAL, $this->context);

        $this->trace->info(TraceCode::PAYMENT_LINK_POST_PROCESSOR_COMPLETED, $this->context);

        // total time taken since job was pushed to queue
        $totalTimeTaken = millitime() - $this->params->get('start_time');

        $this->trace->histogram(PaymentLink\Metric::PAYMENT_PAGE_PROCESSOR_TOTAL_TIME_TO_COMPLETE_JOB,
            $totalTimeTaken, $this->context);
    }

    protected function handleNoCodeAppsPaymentEvent()
    {
        $this->trace->info(TraceCode::NO_CODE_APPS_PAYMENT_EVENT_RECEIVED, $this->context);

        $paymentId  = $this->params->get('payment_id');

        if (empty($paymentId) === true)
        {
            $this->delete();

            return;
        }

        /** @var $payment Payment\Entity*/
        $payment = null;

        try
        {
            $payment = $this->repoManager->payment->findOrFail($paymentId);
        }
        catch (\Throwable $exception){}

        $traceContext = $this->context + [
                'payment_id' => $payment->getId()
            ];

        if ($payment->getStatus() !== Payment\Status::CAPTURED
            && $payment->getStatus() !== Payment\Status::REFUNDED)
        {
            /**
             * The status might not have synced yet. Retry the job
             */
            $this->retry($this->attempts() * self::RETRY_DELAY);

            $this->trace->info(TraceCode::NO_CODE_APPS_PAYMENT_EVENT_RETRY, $traceContext + [
                    "attempt"   => $this->attempts()
                ]);

            return;
        }

        $this->core = new PaymentLink\Core;

        $this->core->processNocodeAppsPaymentEvent($payment);

        $this->delete();
    }

    protected function handlePaymentCaptureEvent()
    {
        $this->core = new PaymentLink\Core;

        $paymentId  = $this->params->get('payment_id');

        if (empty($paymentId) === true)
        {
            $this->delete();

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_FAIL_COUNT_TOTAL, $this->context);

            return;
        }

        $payment = null;

        try
        {
            $payment = $this->repoManager->payment->findOrFail($paymentId);
        }
        catch (\Throwable $exception){}

        $paymentLink    = $payment->paymentLink;

        if (empty($paymentLink) === true) {
            $this->delete();

            $this->trace->info(TraceCode::PAYMENT_LINK_POST_PROCESSOR_FAILED, $this->context + [
                    'payment_id'    => $payment->getId(),
                ]);

            return;
        }

        $traceContext = $this->context + [
                    'payment_id'        => $payment->getId(),
                    'payment_page_id'   => $paymentLink->getId(),
                ];

        if ($payment->getStatus() !== Payment\Status::CAPTURED && $payment->getStatus() !== Payment\Status::REFUNDED)
        {
            /**
             * The status might not have synced yet. Retry the job
             */
            $this->retry($this->attempts() * self::RETRY_DELAY);

            $this->trace->info(TraceCode::PAYMENT_LINK_POST_PROCESSOR_RETRY, $traceContext + [
                "attempt"   => $this->attempts()
            ]);

            return;
        }

        try
        {
            $this->trace->info(TraceCode::PAYMENT_LINK_PAYMENT_CAPTURE_QUEUE, $traceContext);

            $this->core->postPaymentCaptureAttemptProcessing($payment, true);

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_SUCCESS_COUNT_TOTAL, $this->context);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, null, $traceContext);

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_FAIL_COUNT_TOTAL, $this->context);
        }

        $this->delete();
    }

    protected function handleRefundProcessedEvent()
    {
        $this->core = new PaymentLink\Core;

        $refund = $this
            ->repoManager
            ->refund
            ->findByIdAndMerchant($this->params['refund_id'], $this->merchant);

        $context = [
            'refund_id'         => $refund->getId(),
            'refund_status'     => $refund->getStatus(),
            "refund"            => $refund->toArrayPublic(),
            'payment_id'        => $refund->payment->getId(),
            'payment_status'    => $refund->payment->getStatus(),
        ];

        if ($refund->getStatus() !== Payment\Refund\Status::PROCESSED && $refund->payment->getStatus() !== Payment\Status::REFUNDED)
        {
            /**
             * The status might not have synced yet. Retry the job
             */
            $this->retry($this->attempts() * self::RETRY_DELAY);

            $this->trace->info(TraceCode::PAYMENT_LINK_POST_PROCESSOR_RETRY, $context + [
                "attempt"   => $this->attempts()
            ]);

            return;
        }

        try
        {
            $this->trace->info(TraceCode::PAYMENT_LINK_REFUND_PROCESS_QUEUE, $context);

            $this->core->postPaymentRefundUpdatePaymentPage($refund);

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_SUCCESS_COUNT_TOTAL, $this->context);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, null, $context);

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_FAIL_COUNT_TOTAL, $this->context);
        }

        $this->delete();
    }

    protected function handlePaymentHandleCreation()
    {
        $this->service = new PaymentLink\Service;

        try
        {
            $merchantId = $this->params->get('merchant_id');

            $merchant = $this->repoManager->merchant->findByPublicId($merchantId);

            $this->trace->info(TraceCode::PAYMENT_HANDLE_CREATION_QUEUE_START);

            $this->setMerchant($merchant);

            $paymentHandle = $this->service->createPaymentHandle($merchantId);

            $context = [
                PaymentLink\Entity::ID      => $paymentHandle[PaymentLink\Entity::ID],
                PaymentLink\Entity::TITLE   => $paymentHandle[PaymentLink\Entity::TITLE],
                PaymentLink\Entity::SLUG    => $paymentHandle[PaymentLink\Entity::SLUG],
                PaymentLink\Entity::URL     => $paymentHandle[PaymentLink\Entity::URL],
            ];

            $this->trace->info(TraceCode::PAYMENT_HANDLE_CREATION_QUEUE_COMPLETED, $context);

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_SUCCESS_COUNT_TOTAL, $this->context);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e, null, null, [
                'params'    => $this->params->toArray(),
            ]);
            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_FAIL_COUNT_TOTAL, $this->context);
        }

        $this->delete();
    }

    protected function handlePaymentPageCreateDedupe()
    {
        $paymentPageId  = $this->params->get('payment_page_id');

        $this->trace->info(TraceCode::PAYMENT_PAGE_CREATE_DEDUPE_QUEUE_START);

        if (empty($paymentPageId) === true)
        {
            $this->trace->info(TraceCode::PAYMENT_HANDLE_CREATION_QUEUE_FAILED, $this->params->toArray());

            $this->delete();

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_FAIL_COUNT_TOTAL, $this->context);

            return;
        }

        try
        {
            $entity = $this->repoManager->payment_link->find($paymentPageId);

            (new PaymentLink\Core)->doDedupeAndRiskActions($entity);

            $this->trace->info(TraceCode::PAYMENT_PAGE_CREATE_DEDUPE_QUEUE_COMPLETED, $this->params->toArray());

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_SUCCESS_COUNT_TOTAL, $this->context);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, null, [
                'params'    => $this->params->toArray(),
            ]);
            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_FAIL_COUNT_TOTAL, $this->context);
        }

        $this->delete();
    }

    protected function handlePaymentPageHostedCache()
    {
        $paymentPageId  = $this->params->get('payment_page_id');

        $this->trace->info(TraceCode::PAYMENT_PAGE_HOSTED_CACHE_QUEUE_START);

        if (empty($paymentPageId) === true)
        {
            $this->trace->info(TraceCode::PAYMENT_PAGE_HOSTED_CACHE_QUEUE_FAILED, $this->params->toArray());

            $this->delete();

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_FAIL_COUNT_TOTAL, $this->context);

            return;
        }

        try
        {
            $entity = $this->repoManager->payment_link->find($paymentPageId);

            (new PaymentLink\Core)->updateHostedCache($entity);

            $this->trace->info(TraceCode::PAYMENT_PAGE_HOSTED_CACHE_QUEUE_COMPLETED, $this->params->toArray());

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_SUCCESS_COUNT_TOTAL, $this->context);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, null, [
                'params'    => $this->params->toArray(),
            ]);
            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_FAIL_COUNT_TOTAL, $this->context);
        }

        $this->delete();
    }

    /**
     * Runs the custom URL migration in batches.
     * Once all slugs are migrated this method will be removed
     *
     * @return void
     */
    protected function handleNocodeCustomUrlUpsertFromHostedFlow()
    {
        $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_UPSERT_INIT);

        $input = $this->params->get('gimli_response', []);

        try
        {
            (new PaymentLink\NocodeCustomUrl\DataMigrator())->insertForHostedFlowWithGimliResponse($input);

            $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_UPSERT_COMPLETED, $this->params->toArray());

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_SUCCESS_COUNT_TOTAL, $this->context);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(PaymentLink\Metric::NOCODE_CUSTOM_URL_CALLS_FAILED_COUNT);

            $this->trace->error(TraceCode::NOCODE_CUSTOM_URL_UPSERT_FAILED, $this->params->toArray());

            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_FAIL_COUNT_TOTAL, $this->context);
        }
    }

    protected function setMerchant(Merchant\Entity $merchant)
    {
        $this->app = App::getFacadeRoot();

        $this->app['basicauth']->setMerchant($merchant);
    }

    protected function retry(int $delay)
    {
        // if the max attempt is not exhausted then release the job for retry
        if ($this->attempts() <= self::MAX_RETRY_ATTEMPTS)
        {
            $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_RETRY_COUNT, $this->context);

            $this->release($delay);

            return;
        }

        $this->trace->count(PaymentLink\METRIC::PAYMENT_PAGE_PROCESSOR_JOB_FAIL_COUNT_TOTAL, $this->context);

        $this->trace->error(TraceCode::PAYMENT_LINK_POST_PROCESSOR_FAILED, $this->context + [
            "reason"    => "Max retries of " . self::MAX_RETRY_ATTEMPTS . " exhausted.",
        ]);

        $this->delete();
    }

    protected function handleCdsUpdatePlanIdsForMerchants()
    {
        $oldPlanId = $this->params[CDSPlan\Constants::OLD_PLAN_ID];

        $newPlanId = $this->params[CDSPlan\Constants::NEW_PLAN_ID];

        $this->trace->info(TraceCode::CDS_PLAN_UPDATE_JOB_TRIGGERED, [
            CDSPlan\Constants::NEW_PLAN_ID  => $newPlanId,
            CDSPlan\Constants::OLD_PLAN_ID  => $oldPlanId
        ]);

        try
        {
            (new CDSPlan\Core())->updatePlansForMerchants($oldPlanId, $newPlanId);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::CDS_PLAN_UPDATE_JOB_FAILED, [
                CDSPlan\Constants::NEW_PLAN_ID  => $newPlanId,
                CDSPlan\Constants::OLD_PLAN_ID  => $oldPlanId
            ]);
        }

        $this->delete();
    }

    protected function handleCdsPlansBillingDateUpdate()
    {
        $this->trace->info(TraceCode::CDS_PLAN_BILLING_DATE_UPDATE_TRIGGERED);

        try
        {
            (new PaymentLink\CustomDomain\Plans\Core())->cdsPlansBillingDateUpdate();
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::CDS_PLAN_BILLING_DATE_UPDATE_FAILED);
        }

        $this->delete();
    }
}
