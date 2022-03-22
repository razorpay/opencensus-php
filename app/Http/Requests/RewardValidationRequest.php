<?php

namespace RZP\Http\Requests;

use App;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use Illuminate\Foundation\Http\FormRequest;
use RZP\Models\Merchant\Detail\Metric as DetailMetric;
use RZP\Models\Merchant\M2MReferral\FriendBuy\Constants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\M2MReferral\FriendBuy\FriendBuyClient;
use RZP\Models\Merchant\M2MReferral\FriendBuy\FriendBuyService;

class RewardValidationRequest extends FormRequest
{
    const ATTEMPT_COUNT_TTL_IN_SEC       = 900;
    const MAX_ATTEMPT                    = 30;
    const ATTEMPT_COUNT_REDIS_KEY_PREFIX = 'attempt_count';

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $app = App::getFacadeRoot();

        $app['trace']->info(TraceCode::FRIEND_BUY_REWARD_VALIDATION_REQUEST, [
            'request' => $this->getContent()
        ]);

        $attempts = $this->getAttempts();

        if ($attempts > self::MAX_ATTEMPT)
        {
            $app['trace']->count(DetailMetric::REWARD_VALIDATION_EXHAUSTED);

            $app['trace']->info(TraceCode::REWARD_VALIDATION_EXHAUSTED);

            throw new \RZP\Exception\ServerErrorException(ErrorCode::BAD_REQUEST_RATE_LIMIT_EXCEEDED,500);
        }

        $this->increaseAttempt();

        return (new FriendBuyService())->validateSignature($this);
    }

    /**
     * @return string
     */
    public function getRateLimiterKey(): string
    {
        return self::ATTEMPT_COUNT_REDIS_KEY_PREFIX;
    }

    /**
     * @return int
     */
    public function getAttempts(): int
    {
        $attemptRedisKey = $this->getRateLimiterKey();

        return App::getFacadeRoot()['cache']->get($attemptRedisKey) ?? 0;
    }

    public function increaseAttempt()
    {
        $attemptRedisKey = $this->getRateLimiterKey();

        $attempt = $this->getAttempts() + 1;

        App::getFacadeRoot()['cache']->put($attemptRedisKey,
                                 $attempt,
                                 self::ATTEMPT_COUNT_TTL_IN_SEC);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            Constants::EVENT_TYPE => 'required|string',

            Constants::RECIPIENT_TYPE => 'required|string|in:advocate,friend',

            Constants::ADVOCATE => 'required|array',

            Constants::ADVOCATE . '.' . Constants::CUSTOMER_ID => 'required|string|size:14',

            Constants::ACTOR => 'required|array',

            Constants::ACTOR . '.' . Constants::CUSTOMER_ID => 'required|string|size:14',

        ];
    }

    public function getMerchantId()
    {
        $request = $this->all();

        return $request[Constants::ACTOR][Constants::CUSTOMER_ID];
    }

    public function getReferrerId()
    {
        $request = $this->all();

        return $request[Constants::ADVOCATE][Constants::CUSTOMER_ID];
    }
}
