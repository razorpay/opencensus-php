<?php

namespace RZP\Models\TrustedBadge;

use Illuminate\Support\Facades\Redis;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

    }

    public function eligibilityCron()
    {
        return $this->core->eligibilityCron();
    }

    public function fetch()
    {
        $merchantId = $this->merchant->getMerchantId();

        $data = [
            'status'                    => Entity::INELIGIBLE,
            'merchant_status'           => '',
            'is_delisted_atleast_once'  => 0
        ];

        $trustedBadge = $this->repo->trusted_badge->fetchByMerchantId($merchantId);

        // check for is delisted atleast once

        if ($this->core->isDelistedCheck($merchantId) === true)
        {
            $data['is_delisted_atleast_once'] = 1;
        }

        if(isset($trustedBadge))
        {
            $trustedBadge= $trustedBadge->toArrayPublic();
            $data = array_merge($data, $trustedBadge);
        }

        return $data;
    }

    public function updateMerchantStatus($input): void
    {
        $merchantId = $this->merchant->getMerchantId();

        $merchantStatus = $input['merchant_status'] ?? '';

        (new Validator())->validateMerchantStatus(Entity::MERCHANT_STATUS ,$merchantStatus);

        $this->core->upsertMerchantStatus($merchantId, $merchantStatus);
    }

    public function blacklistMerchants($input): array
    {
        (new Validator())->validateInput('validate_blacklist', $input);

        $merchantIdList = $input['merchant_ids'];

        $blacklistStatus = $input['blacklist'];

        $response = [
            'success' => 0,
            'failures' => [],
        ];

        foreach ($merchantIdList as $merchantId)
        {
            try {
                $status = Entity::BLACKLIST;

                // check if merchant_id exists
                $this->repo->merchant->findOrFail($merchantId);

                if ($blacklistStatus === false)
                {
                    // fetch current status
                    $currentTrustedBadge = $this->repo->trusted_badge->fetchByMerchantId($merchantId);

                    // update only if blacklisted
                    if ($currentTrustedBadge[Entity::STATUS] === Entity::BLACKLIST)
                    {
                        $status = Entity::INELIGIBLE;
                    }
                    else
                    {
                        $response['success']++;
                        continue;
                    }
                }

                $this->core->upsertStatus($merchantId, $status);

                $response['success']++;

            } catch (\Throwable $e)
            {

                $this->trace->info(TraceCode::TRUSTED_BADGE_BLACKLIST_FAILURE,
                    [
                        'merchant_id' => $merchantId,
                        'status'      => $status,
                    ]);

                $response['failures'][] = $merchantId;

            }
        }

        return $response;
    }

    public function redirectUrl($input): array
    {
        (new Validator())->validateInput('validate_redirect', $input);

        $data = [
            'url' => 'https://dashboard.razorpay.com/app/trustedbadge/',
        ];

        if($input['cta'] === 'feedback')
        {
            $data['url'] = 'https://razorpay.typeform.com/to/Q0KKcFDu/';
        }

        $data['url'] .= '?utm_source=rtb_' . $input['mailer'] . '_mailer';

        try
        {
            $this->trace->info(TraceCode::TRUSTED_BADGE_MAIL_CTA, [
                'cta'           => $input['cta'],
                'mailer'        => $input['mailer'],
                'merchant_id'   => $input['merchant_id'],
            ]);

            $this->app['rzp.mode'] = 'live';

            $this->app['diag']->trackTrustedBadgeEvent(EventCode::TRUSTED_BADGE_MAIL_CTA, [
                'cta'           => $input['cta'],
                'mailer'        => $input['mailer'],
                'merchant_id'   => $input['merchant_id'],
            ]);
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::TRUSTED_BADGE_REDIRECT_ERROR, ['input' => $input]);
        }

        return $data;
    }

    public function fetchExperimentList()
    {
        try
        {
            $redis = Redis::connection();

            return $redis->smembers(Entity::REDIS_EXPERIMENT_KEY);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::TRUSTED_BADGE_EXPERIMENT_LIST , []);

            return null;
        }
    }

    public function putExperimentList($input)
    {
        try
        {
            $merchantList = $input['merchants'];
            $redis = Redis::connection();

            $redis->del(Entity::REDIS_EXPERIMENT_KEY);

            return $redis->sadd(Entity::REDIS_EXPERIMENT_KEY, $merchantList);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::TRUSTED_BADGE_EXPERIMENT_LIST , ['input' => $input]);

            return null;
        }
    }
}
