<?php

namespace RZP\Jobs\SettlementOndemand;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand;
use RZP\Models\Settlement\Ondemand\FeatureConfig;

class AddOndemandRestrictedFeatureForMerchant extends Job
{
    protected $mode;

    protected $merchantId;

    const MAX_ATTEMPTS = 3;

    public function __construct($mode, $merchantId)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::ADD_ONDEMAND_RESTRICTED_FEATURE_FOR_MERCHANT, [
            'merchant_id'   => $this->merchantId,
        ]);

        try
        {
            $input = [
                FeatureConfig\Entity::MERCHANT_ID                  => $this->merchantId,
                FeatureConfig\Entity::PERCENTAGE_OF_BALANCE_LIMIT  => FeatureConfig\Entity::DEFAULT_PERCENTAGE_OF_BALANCE_LIMIT,
                FeatureConfig\Entity::SETTLEMENTS_COUNT_LIMIT      => FeatureConfig\Entity::DEFAULT_SETTLEMENTS_COUNT_LIMIT,
                FeatureConfig\Entity::FULL_ACCESS                  => 'no',
                FeatureConfig\Entity::PRICING_PERCENT              => FeatureConfig\Entity::DEFAULT_PRICING_PERCENT,
                FeatureConfig\Entity::MAX_AMOUNT_LIMIT             => FeatureConfig\Entity::DEFAULT_MAX_AMOUNT_LIMIT,
                FeatureConfig\Entity::ES_PRICING_PERCENT           => FeatureConfig\Entity::DEFAULT_ES_PRICING_PERCENT
              ];

            (new FeatureConfig\Service)->enableFeature([$input]);

            $this->delete();
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ADD_ONDEMAND_RESTRICTED_FEATURE_FOR_MERCHANT_ERROR,
                [
                    'merchant_id'    => $this->merchantId,
                ]);

            if ($this->attempts() <= self::MAX_ATTEMPTS)
            {
                $this->release(1);
            }
            else
            {
                $this->delete();
            }
        }
    }
}
