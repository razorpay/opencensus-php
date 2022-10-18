<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;

use RZP\Constants\Mode;
use RZP\Constants\Entity;

class SettlementOndemandController extends Controller
{
    public function postSettlementOndemand()
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->create($input);

        return ApiResponse::json($data);
    }

    public function processXSettlementBulkTransfer()
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND_TRANSFER)->processXSettlementBulkTransfer();

        return ApiResponse::json($data);
    }

    public function createFundAccount()
    {
        $data = $this->service(Entity::SETTLEMENT_ONDEMAND_FUND_ACCOUNT)->createFundAccount();

        return ApiResponse::json($data);
    }

    public function createFundAccountForMerchantId(string $id)
    {
        $data = $this->service(Entity::SETTLEMENT_ONDEMAND_FUND_ACCOUNT)->addOndemandFundAccountForMerchant($id);

        return ApiResponse::json($data);
    }

    public function addDefaultOndemandPricingIfNotPresent(string $id)
    {
        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->addDefaultOndemandPricingIfNotPresent($id);

        return ApiResponse::json($data);
    }

    public function addOndemandPricingIfAbscent()
    {
        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->addOndemandPricingIfAbscent();

        return ApiResponse::json($data);
    }

    public function getSettlementOndemand(string $id)
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->fetch($id, $input);

        return ApiResponse::json($data);
    }

    public function getMultipleSettlementOndemand()
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function ondemandPayoutUpdate()
    {
        //since razorpayx_webhook is adirect route the mode will not be set by default
        //setting the mode to 'live' as only in live mode  in prod this route will be hit
        //in test mode and in stage env the statusUpdate service merthod will be called directly
        $this->app = App::getFacadeRoot();

        $this->app['rzp.mode'] = Mode::LIVE;

        $rawContent = Request::getContent();

        $input = Request::all();

        $headers = Request::header();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND_PAYOUT)->statusUpdate($input, $headers, $rawContent);

        return ApiResponse::json($data);
    }

    public function calculateFees()
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->calculateFees($input);

        return ApiResponse::json($data);
    }

    //enables 'es_on_demand' feature in bulk if feature not present for merchants
    //adds ondemand pricing rule if not present, else updates it
    //creates entry in settlement_ondemand_feature_configs if not present, else updates it
    public function enableFeature()
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND_FEATURE_CONFIG)->enableFeature($input);

        return ApiResponse::json($data);
    }

    public function enableFeaturePeriod()
    {
        $input = Request::all();

        $data = $this->service(Entity::EARLY_SETTLEMENT_FEATURE_PERIOD)->enableFeaturePeriod($input);

        return ApiResponse::json($data);
    }

    public function disableFeaturePeriod()
    {
        $input = Request::all();

        $data = $this->service(Entity::EARLY_SETTLEMENT_FEATURE_PERIOD)->disableFeaturePeriod($input);

        return ApiResponse::json($data);
    }

    public function validateWithFeatureConfig()
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND_FEATURE_CONFIG)->validateWithFeatureConfig();

        return ApiResponse::json($data);
    }

    public function enqueueJob(string $id)
    {
        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->enqueueJob($id);

        return ApiResponse::json($data);
    }

    public function markAsProcessed(string $id)
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND_TRANSFER)->markAsProcessed($id);

        return ApiResponse::json($data);
    }

    public function triggerOndemandTransfer()
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND_TRANSFER)->triggerOndemandTransfer($input);

        return ApiResponse::json($data);
    }

    public function updateOndemandTransferPayoutId()
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND_ATTEMPT)->updatePayoutId($input);

        return ApiResponse::json($data);
    }

    public function processPartialSettlementScheduled()
    {
        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->processPartialSettlementScheduled();

        return ApiResponse::json($data);
    }

    public function enableFullESFromRestricted()
    {
        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->enableFullESFromRestricted();

        return ApiResponse::json($data);
    }

    public function addOndemandRestrictedFeature()
    {
        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->addOndemandRestrictedFeature();

        return ApiResponse::json($data);
    }

    public function isOndemandBlocked()
    {
        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->isOndemandBlocked();

        return ApiResponse::json($data);
    }

    public function linkedAccountSettlement()
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_ONDEMAND)->createOndemandSettlementForLinkedAccount($input);

        return ApiResponse::json($data);
    }

    public function reverseOndemandSettlement()
    {
        $input = Request::all();

        try
        {
            $this->service(Entity::SETTLEMENT_ONDEMAND)->createSettlementOndemandReversal($input['settlement_ondemand_id'], $input['merchant_id'],$input['reversal_reason']);
        }
        catch (\Throwable $e)
        {
            return ApiResponse::json(['success' => false]);
        }

        return ApiResponse::json(['success' => true]);

    }
}
