<?php

namespace App\User;

use Auth;

use App\Trace\TraceCode;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\Clock\SystemClock;
use Razorpay\Api\Errors\ErrorCode;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Razorpay\Api\Errors\BadRequestError;
use Lcobucci\JWT\Encoding\ChainedFormatter;

class Identity {

    const INVALID_CLIENT_ID = "invalid client id";

    //
    // list of client ids which are valid
    // for now we are defining client ids as same as service provider names
    //
    const VALID_SERVICE_PROVIDERS = [
        'opfin',
        'thirdwatch',
        'billme',
    ];

    const USER_EMAIL          = 'user_email';

    //
    // this is 30 seconds. This number will be added to the current time
    // to derive the token expiry time at the time of creation
    //
    const IDENTITY_TOKEN_TTL = "PT30S";

    /**
     * it'll generate the user identity token from for the logged in user
     * the token will be signed with the symmetric key specific to the the
     * service provider. Also the token will have TTL for 30 sec
     *
     * @param string $clientId
     * @param array $params
     * @return array
     * @throws BadRequestError
     */
    public function generateIdentityToken(string $clientId, array $params): array
    {
        $this->validateRequest($clientId, $params);

        $serviceProvider = $clientId;

        $user = Auth::user();

        $sysClock = new SystemClock(new \DateTimeZone('UTC'));

        $providerDetails = app('config')['auth']['service_provider'][$serviceProvider];

        $issuer = parse_url(config('app.url'), PHP_URL_HOST);

        $tokenBuilder = new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates());

        $config = Configuration::forSymmetricSigner(new Sha256(), Key\InMemory::plainText($providerDetails['signing_secret']));

        $token =  $tokenBuilder->issuedBy($issuer)
                               ->permittedFor($serviceProvider)
                               ->issuedAt($sysClock->now())
                               ->expiresAt($sysClock->now()->add(new \DateInterval(self::IDENTITY_TOKEN_TTL)))
                               ->withClaim(self::USER_EMAIL, $user->email)
                               ->getToken($config->signer(), $config->signingKey());

        app('trace')->info(TraceCode::GENERATE_JWT_DASHBOARD, [
            'user_email'  => $user->email,
            'user_id'     => $user->id,
            'client'      => $clientId
        ]);

        return [
            $providerDetails['redirect_url'],
            $token,
        ];
    }

    /**
     * check is required query params are present and if it contains the valid values
     *
     * @param string $clientId
     * @param array $_
     * @throws BadRequestError
     */
    public function validateRequest(string $clientId, array $_)
    {
        if (in_array($clientId, self::VALID_SERVICE_PROVIDERS) === false)
        {
            throw new BadRequestError(
                self::INVALID_CLIENT_ID,
                ErrorCode::BAD_REQUEST_ERROR,
                400);
        }
    }
}
