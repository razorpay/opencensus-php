<?php

namespace RZP\Models\Upi\Turbo\RewardProcessor;

use Monolog\Logger;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Core;
use RZP\Gateway\P2p\Upi\AxisOlive\ClientGateway;

class Base extends Core
{
    protected $rewardPartner;

    const MAX_RETRY_COUNT = 2;

    const FLOW_ELIGIBILITY = 'eligibility';
    const FLOW_ALLOT       = 'allot';

    const REWARD_PARTNER_LOGO_URL = 'reward_partner_logo_url';

    const STATE = 'status';

    const REWARD_PARTNER_NAME = 'reward_partner_name';

    const ONBOARDING = 'onboarding';
    const PAYMENT = 'payment';

    const MERCHANT_REWARD_PARTNER_MAP = [
        '100DemoAccount' => RewardPartner::REWARD_PARTNER_CRED
    ];

    const CONTACT     = 'contact';
    const TOKEN       = 'token';
    const AMOUNT      = 'amount';
    const USER        = 'user';
    const TYPE        = 'type';
    const ACTION      = 'action';
    const DATA        = 'data';
    const MERCHANT_ID = 'merchant_id';
    const PAYMENT_ID  = 'payment_id';
    const SDK_SESSION_ID = 'sdk_session_id';
    const IDEMPOTENCY_ID = 'idempotency_id';

    const ACTION_MAP = [];

    public function getRewardProcessorClass()
    {
        $merchantId = $this->merchant->getId();

        $rewardPartnerName = self::getRewardPartnerName($merchantId);

        $rewardPartnerClass = __NAMESPACE__ . '\\'. studly_case(strtolower($rewardPartnerName));

        if (class_exists($rewardPartnerClass) === true)
        {
            return new $rewardPartnerClass($merchantId);
        }

       throw new \RZP\Exception\RuntimeException("Reward processor not found");
    }

    public function getRewardType($action)
    {
        return static::ACTION_MAP[$action] ?? null;
    }

    public static function getRewardPartnerName($merchantId): string|null
    {
        return self::MERCHANT_REWARD_PARTNER_MAP[$merchantId] ?? null;
    }

    public function getRewardEligibilityRequestPayload($input): array
    {
        return [];
    }

    public function parseRewardEligibilityResponse($rewardPartnerResponse): array
    {
        return [];
    }

    public function getRewardAllotmentRequestPayload($input)
    {
        return [];
    }

    public function parseRewardAllotmentResponse($rewardPartnerResponse): array
    {
        return [];
    }

    public function makeRequestAndGetResponse($request, $action)
    {
        $response = null;
        $methodName = '';
        $retryCount = 0;

        switch ($action)
        {
            case self::FLOW_ALLOT:
                $methodName = 'allotCustomerReward';
                break;

            case self::FLOW_ELIGIBILITY:
                $methodName = 'checkCustomerRewardEligibility';
                break;

            default:
                break;
        }

        while ($retryCount <= self::MAX_RETRY_COUNT)
        {
            try
            {
                return  (new ClientGateway)->$methodName($request);
            }
            catch (\Throwable $exception) {

                $this->trace->traceException(
                    $exception,
                    Logger::ERROR,
                    TraceCode::CUSTOMER_REWARD_ELIGIBILITY_CHECK_ERROR,
                    [
                        'request'                 => $request,
                        'gateway_method'          => $methodName,
                        'reward_partner_response' => $rewardPartnerResponse ?? null,
                        'retry_count'             => $retryCount,
                    ]
                );

                $retryCount ++;
            }
        }

        return $response;
    }

    public function process($input, $action): array
    {
        $subProcessorClass = $this->getRewardProcessorClass();

        return $subProcessorClass->process($input, $action);
    }
}
