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

    public function eligibilityCron(array $input): array
    {
        return $this->core->eligibilityCron($input);
    }

    public function fetch()
    {
        $merchantId = $this->merchant->getMerchantId();

        $data = [
            'status'                    => Entity::INELIGIBLE,
            'merchant_status'           => '',
            'is_delisted_atleast_once'  => 0,
            'is_live'                   => false,
        ];

        /** @var Entity $trustedBadge */
        $trustedBadge = $this->repo->trusted_badge->fetchByMerchantId($merchantId);

        // check for is delisted atleast once

        if ($this->core->isDelistedCheck($merchantId) === true)
        {
            $data['is_delisted_atleast_once'] = 1;
        }

        if ($trustedBadge !== null)
        {
            $data['is_live'] = $trustedBadge->isLive();
            $data = array_merge($data, $trustedBadge->toArrayPublic());
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

    public function updateTrustedBadgeStatus($input): array
    {
        (new Validator())->validateInput('validate_status', $input);

        $merchantIdList = $input['merchant_ids'];

        $status= $input['status'];

        $action = $input['action'];

        $response = [
            'success' => 0,
            'failures' => [],
        ];

        foreach ($merchantIdList as $merchantId)
        {
            try {
                // check if merchant_id exists
                $this->repo->merchant->findOrFail($merchantId);

                if ($action === 'remove')
                {
                    // fetch current status
                    $currentTrustedBadge = $this->repo->trusted_badge->fetchByMerchantId($merchantId);

                    /**
                     * We should remove merchant from blacklist if and only if they are already in blacklist.
                     * similarly, remove them from whitelist if and only if they are already in whitelist
                     */
                    if ($currentTrustedBadge[Entity::STATUS] === $status)
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
                $this->trace->info(TraceCode::TRUSTED_BADGE_STATUS_UPDATE_FAILURE,
                    [
                        'merchant_id' => $merchantId,
                        'status'      => $status,
                        'action'      => $action,
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
