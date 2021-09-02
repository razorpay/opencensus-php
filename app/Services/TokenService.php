<?php

namespace RZP\Services;

use App;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Foundation\Application;
use Illuminate\Support\Facades\Redis;

/**
 * Class Token
 *
 * Used to wrap "token for otp-auth" functionality. The token is generated after OTP verification. And stored in redis
 *
 * @package RZP\Services
 */
class TokenService
{
    const TOKEN_EXPIRES_IN_SECONDS = 900; // 15 minutes (in seconds)

    const REDIS_EXPIRY_PARAM       = 'ex';

    const CONTEXT                  = 'context';

    protected $redis;

    public function __construct(Application $app)
    {
        $this->redis = Redis::connection('mutex_redis');
    }

    /**
     * Will generate a token, add to redis and return.
     * It will be unique in almost all cases, unless generate is called on the same context at the exact same time.
     * In which case one generate will override the other, but the final expected result still remains the same
     * @param string $context
     * @return string
     */
    public function generate(string $context)
    {
        $timestamp =  Carbon::now(Timezone::IST)->getTimestamp();

        $token = $context . '.' . $timestamp;

        $this->redis->set($token, $context, self::REDIS_EXPIRY_PARAM, self::TOKEN_EXPIRES_IN_SECONDS);

        return $token;
    }

    /**
     * Will raise exception if the token is not found
     *
     * @param $token
     * @param $context
     * @throws BadRequestException
     */
    public function verify($token, $context)
    {
        $value = $this->redis->get($token);

        if (is_null($value) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_OTP_AUTH_TOKEN,
                null,
                [
                    self::CONTEXT => $context
                ]
            );
        }

        if ($value !== $context)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_OTP_AUTH_TOKEN,
                null,
                [
                    self::CONTEXT => $context
                ]
            );
        }
    }

    public function invalidate($token)
    {
        $this->redis->del($token);
    }
}
