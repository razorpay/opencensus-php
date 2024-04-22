<?php

namespace RZP\Models\Upi\Turbo\RewardProcessor;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\Upi\Turbo\Validator;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Feature\Constants as FeatureConstants;

class Cred extends Base
{
    protected $validator;

    protected $merchantId;

    const REWARD_PARTNER_CRED = 'CRED';
    const ACTION_PAYMENT_COMPLETED = 'upi_plugin_payment_completed';
    const ACTION_ONBOARDING_COMPLETED = 'user_onboarding_completed';

    const LOGO_URL = 'https://cdn.razorpay.com/cred.gif';

    const REWARD_ELIGIBILITY_RULE_NAME = 'cred_reward_eligibility';
    const REWARD_ALLOTMENT_RULE_NAME = 'cred_reward_allotment';

    //Response Params
    const STATUS_ELIGIBLE = 'ELIGIBLE';
    const STATUS_INELIGIBLE = 'INELIGIBLE';

    const STATE      = 'state';
    const STATUS     = 'status';
    const LAYOUT     = 'layout';
    const ACTION_MAP = [
        self::ONBOARDING => self::ACTION_ONBOARDING_COMPLETED,
        self::PAYMENT    => self::ACTION_PAYMENT_COMPLETED
    ];

    const TERMS_AND_CONDITIONS = 'terms_and_conditions';
    const TERMS_AND_CONDITIONS_LIST = [];

    const ELIGIBLE = 'eligible';
    const RESPONSE_MAPPER = [
        'coupon_code'       => 'coupon_code',
        'pre_action_nudge'  => 'pre_allot_text',
        'post_action_nudge' => 'post_allot_text'
    ];
    public function __construct()
    {
        parent::__construct();
        $this->validator = new Validator;
        $this->rewardPartner = RewardPartner::REWARD_PARTNER_CRED;
    }
    public function getRewardEligibilityRequestPayload($input): array
    {
        $this->validator->validateInput(self::REWARD_ELIGIBILITY_RULE_NAME, $input);

        $action = $this->getRewardType($input[self::ACTION]);

        if (!$this->merchant->isFeatureEnabled(FeatureConstants::CUSTOMER_REWARDS_ENABLED))
        {
            throw new BadRequestValidationFailureException("Merchant is not enabled for Turbo rewards");
        }

        $request = [
            self::CONTACT             => $input[self::CONTACT],
            self::ACTION              => $action,
            self::MERCHANT_ID         => $this->merchant->getMerchantId(),
            self::REWARD_PARTNER_NAME => $this->rewardPartner
        ];

        if ($action === self::ACTION_PAYMENT_COMPLETED)
        {
            $request[self::AMOUNT] = round($input[self::DATA][self::AMOUNT], 2);
        }

        return $request;
    }
    public function getRewardAllotmentRequestPayload($input): array
    {
        $this->validator->validateInput(self::REWARD_ALLOTMENT_RULE_NAME, $input);

        $rewardType = $this->getRewardType($input[self::ACTION]);

        $idempotencyKey = $this->getIdempotencyKeyForRewardAllotmentRequest($input, $rewardType);

        $metadataPaymentAmount = $this->validateAndFetchPaymentAmount($idempotencyKey, $rewardType);

        $request = [
            self::IDEMPOTENCY_ID      => $idempotencyKey,
            self::CONTACT             => $input[self::CONTACT],
            self::ACTION              => $rewardType,
            self::MERCHANT_ID         => $this->merchant->getMerchantId(),
            self::REWARD_PARTNER_NAME => $this->rewardPartner,
        ];

        if ($metadataPaymentAmount != null)
        {
            $request[self::AMOUNT] = $metadataPaymentAmount;
        }

        return $request;
    }

    public function parseRewardEligibilityResponse($rewardPartnerResponse): array
    {
        $rewardPartnerResponse = json_decode($rewardPartnerResponse, true);

        $eligibilityStatus = $rewardPartnerResponse[self::DATA][self::STATE];
        $response[self::ELIGIBLE] = ($eligibilityStatus === self::STATUS_ELIGIBLE);

        if ($response[self::ELIGIBLE]) {
            $reward = [
                self::REWARD_PARTNER_NAME => $this->rewardPartner,
                self::REWARD_PARTNER_LOGO_URL => self::LOGO_URL,
                self::TERMS_AND_CONDITIONS => self::TERMS_AND_CONDITIONS_LIST
            ];

            foreach (self::RESPONSE_MAPPER as $external => $internal) {
                $reward[$internal] = $rewardPartnerResponse[self::DATA][self::LAYOUT][$external];
            }

            $response['rewards'] = [$reward];

        }
        else
        {
            $response['rewards'] = null;
        }

        return $response;
    }

    public function parseRewardAllotmentResponse($rewardPartnerResponse): array
    {
        $rewardPartnerResponse = json_decode($rewardPartnerResponse, true);
        $response = [
            'success' => false
        ];

        if ((empty($rewardPartnerResponse) === false) and
            (isset($rewardPartnerResponse[self::DATA][self::STATUS]) === true) and
            ($rewardPartnerResponse[self::DATA][self::STATUS] === Status::STATUS_PROCESSING))
        {
            $response['success'] = true;
        }

        return $response;
    }

    private function getIdempotencyKeyForRewardAllotmentRequest($input, $action)
    {
        $idempotencyKey = '';

        switch ($action)
        {
            case self::ACTION_PAYMENT_COMPLETED:
                $idempotencyKey = $input[self::DATA][self::PAYMENT_ID] ?? null;
                break;

                case self::ACTION_ONBOARDING_COMPLETED:
                $idempotencyKey = $input[self::DATA][self::SDK_SESSION_ID] ?? null;
        }

        if (empty($idempotencyKey) === true)
        {
            throw new BadRequestValidationFailureException("The idempotency_key is either invalid or missing");
        }

        return $idempotencyKey;
    }

    private function validateAndFetchPaymentAmount($idempotencyKey, $rewardType)
    {
        if ($rewardType != self::ACTION_PAYMENT_COMPLETED)
        {
            return null;
        }

        $payment = app('repo')->payment->findOrFailPublic(substr($idempotencyKey, 4));

        if (($payment === null) or ($payment->isInAppUpi() !== true))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $authorizeTimestamp = $payment->getAuthorizeTimestamp();
        $currentTimestamp = time();

        if ($authorizeTimestamp === null || ($currentTimestamp - $authorizeTimestamp) > (3 * 60))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID,"Sorry! Your reward could not be processed." );
        }

        return round($payment->getAmount(), 2);
    }

    public function processRewardAllotmentRequest($input)
    {
        $request = $this->getRewardAllotmentRequestPayload($input);

        $response = $this->makeRequestAndGetResponse($request, self::FLOW_ALLOT);

        $parsedResponse = $this->parseRewardAllotmentResponse($response);

        $this->trace->info(TraceCode::TURBO_REWARD_ALLOTMENT_REQUEST_PROCESSED,
                           [
                               'reward_partner' => $this->rewardPartner,
                               'response'       => $parsedResponse
                           ]);

        return $parsedResponse;
    }

    public function processRewardEligibilityRequest($input)
    {
        $request = $this->getRewardEligibilityRequestPayload($input);

        $response = $this->makeRequestAndGetResponse($request, self::FLOW_ELIGIBILITY);

        $parsedResponse = $this->parseRewardEligibilityResponse($response);

        $this->trace->info(TraceCode::TURBO_REWARD_ELIGIBILITY_REQUEST_PROCESSED,
                           [
                               'reward_partner' => $this->rewardPartner,
                               'response'       => $parsedResponse
                           ]);

        return $parsedResponse;
    }

    public function process($input, $action): array
    {
        switch ($action)
        {
            case self::FLOW_ALLOT:
                return $this->processRewardAllotmentRequest($input);
                break;

            case self::FLOW_ELIGIBILITY:
                return $this->processRewardEligibilityRequest($input);
                break;

        }
    }
}
