<?php

namespace RZP\Models\PaymentLink\CustomDomain\Plans;

use RZP\Jobs\PaymentPageProcessor;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function createMany(array $input): array
    {
        (new Validator)->validateInput('createMany', $input);

        $response = $this->core->createMany($input);

        return $response;
    }

    public function fetchPlans(): array
    {
        $response = $this->core->fetchPlans();

        return $response;
    }

    public function deletePlans(array $input): array
    {
         return $this->core->deletePlans($input);
    }

    public function fetchPlanForMerchant(): array
    {
         return $this->core->fetchPlanForMerchant();
    }

    public function deletePlanForMerchant()
    {
        return $this->core->deletePlanForMerchant();
    }

    public function updatePlanForMerchants(array $input)
    {
        $this->trace->info(TraceCode::CDS_UPDATE_PLAN_REQUEST_RECEIVED, [
            Constants::INPUT => $input
        ]);
        $validator = new Validator();

        $validator->validateInput('update_plan_for_merchants', $input);

        $newPlanId = array_get($input, Constants::NEW_PLAN_ID);

        $oldPlanId = array_get($input, Constants::OLD_PLAN_ID);

        $validator->validatePlansUpdateForMerchant($newPlanId, $oldPlanId);

        try
        {
            $this->trace->info(TraceCode::CDS_UPDATE_PLAN_JOB_DISPATCHING, [
                Constants::NEW_PLAN_ID => $newPlanId,
                Constants::OLD_PLAN_ID => $oldPlanId
            ]);

            PaymentPageProcessor::dispatch($this->mode, [
                'event' => PaymentPageProcessor::CDS_UPDATE_PLAN_IDS_FOR_MERCHANTS,
                Constants::OLD_PLAN_ID => $oldPlanId,
                Constants::NEW_PLAN_ID => $newPlanId
            ]);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::CDS_PLAN_UPDATE_FOR_MERCHANT_FAILED);
        }

        return [CONSTANTS::RESPONSE => CONSTANTS::UPDATE_PLANS_RESPONSE];
    }

    public function cdsPlansBillingDateUpdate()
    {
        $this->trace->info(TraceCode::CDS_PRICING_BILLING_UPDATE_INITIATED, [
            Constants::BILLING_JOB_DATE   => Carbon::now()->toDateString()
        ]);

        PaymentPageProcessor::dispatch($this->mode, [
            'event' => PaymentPageProcessor::CDS_PLANS_BILLING_DATE_UPDATE
        ]);

        return [CONSTANTS::RESPONSE => CONSTANTS::BILLING_JOB_RESPONSE];
    }
}
