<?php

namespace App\User;

use Auth;

use Razorpay\Api\Errors\ErrorCode;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Builder as JWTBuilder;
use Razorpay\Api\Errors\BadRequestError;

class Identity {

    const INVALID_CLIENT_ID = "invalid client id";

    //
    // list of client ids which are valid
    // for now we are defining client ids as same as service provider names
    //
    const VALID_SERVICE_PROVIDERS = [
        'opfin',
        'thirdwatch',
    ];

    const USER_EMAIL          = 'user_email';

    //
    // this is seconds. This number will be added to the current time
    // to derive the the token expiry time at the time of creation
    //
    const IDENTITY_TOKEN_TTL = 30;

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

        $providerDetails = app('config')['auth']['service_provider'][$serviceProvider];

        $issuer = parse_url(config('app.url'), PHP_URL_HOST);

        $token = (new JWTBuilder())->setIssuer($issuer)
                                   ->setAudience($serviceProvider)
                                   ->setIssuedAt(time())
                                   ->setExpiration(time() + self::IDENTITY_TOKEN_TTL)
                                   ->set(self::USER_EMAIL, $user->email)
                                   ->sign(new Sha256(), $providerDetails['signing_secret'])
                                   ->getToken();

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
