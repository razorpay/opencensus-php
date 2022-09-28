<?php

namespace RZP\Models\Partner;

use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HyperTrace;
use RZP\Models\Merchant\Entity;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Service as MerchantService;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;

class RateLimitBatch extends Base\Core
{
    protected $redis;

    const RATE_LIMIT_TTL = 86400; // 24 hours

    const THRESHOLD_RATE_LIMIT_COUNT = 10000;


    public function __construct()
    {
        parent::__construct();

        $this->redis = $this->app['redis']->Connection('mutex_redis');
    }

    public function partnerSubmerchantInvite(Entity $merchant): void
    {
        $allow = $this->allowPartnerToAddSubmerchant($merchant);

        if ($allow === false)
        {
            //Adding merchant Id to dimension as this is a extreme case
            $this->trace->count(Metric::SUBMERCHANT_INVITE_BATCH_DAILY_LIMIT_EXCEEDED, ['partner_id' => $merchant->getId()]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_DAILY_LIMIT_SUBMERCHANT_INVITE_EXCEEDED, null,
                                          ['merchant_id' => $merchant->getMerchantId(), 'email' => $merchant->getEmail(),]);
        }

        $this->incrementRateLimitCount($merchant);
    }

    public function allowPartnerToAddSubmerchant(Entity $merchant)
    {
        $value = $this->getRateLimitCount($merchant);

        if ($value > self::THRESHOLD_RATE_LIMIT_COUNT)
        {
            $data = [
                'count'                 => $value,
                'merchant_id'           => $merchant->getId(),
                'merchant_name'         => $merchant->getName(),
            ];

            $this->trace->info(TraceCode::RATE_LIMIT_BATCH_PARTNER_SUBMERCHANT_INVITE, $data);

            return false;
        }

        return true;
    }

    public function getRateLimitCount(Entity $merchant): int
    {
        $rateLimitRedisKey = $this->getRateLimitRedisKey($merchant->getMerchantId());

        return $this->redis->get($rateLimitRedisKey) ?? 0;
    }

    public function getRateLimitRedisKey(string $merchantId): string
    {
        return Constants::RATE_LIMIT_SUBMERCHANT_INVITE_BATCH_PREFIX . $merchantId;
    }

    public function incrementRateLimitCount(Entity $merchant)
    {
        $rateLimitRedisKey = $this->getRateLimitRedisKey($merchant->getMerchantId());

        $index = $this->redis->incr($rateLimitRedisKey);

        // add expiry for first increment of the key
        if ($index == 1)
        {
            $this->redis->expire($rateLimitRedisKey, self::RATE_LIMIT_TTL);
        }
    }

}
