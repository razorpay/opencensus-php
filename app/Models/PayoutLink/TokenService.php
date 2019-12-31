<?php

namespace RZP\Models\PayoutLink;

use App;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;

/**
 * Class Token
 *
 * Used to wrap "token for otp-auth" functionality. The token is generated after OTP verification. And stored in redis
 *
 * @package RZP\Models\PayoutLink
 */
class TokenService
{
    const TOKEN_EXPIRES_IN_SECONDS = 900; // 15 minutes
    const EX                       = 'ex';

    protected $redis;

    public function __construct()
    {
        $this->redis = App::getFacadeRoot()['redis']->connection();
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

        $this->redis->set($token, '', self::EX, self::TOKEN_EXPIRES_IN_SECONDS);

        return $token;
    }

    /**
     * Will raise exception if the token is not found
     *
     * @param $token
     * @throws BadRequestException
     */
    public function verify($token)
    {
        $value = $this->redis->get($token);

        if (is_null($value) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_OTP_AUTH_TOKEN,
                null,
                [
                    Entity::TOKEN => $token
                ]
            );
        }
    }

    public function invalidate($token)
    {
        $this->redis->del($token);
    }
}
