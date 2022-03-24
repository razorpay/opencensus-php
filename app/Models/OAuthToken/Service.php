<?php

namespace RZP\Models\OAuthToken;

use Request;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Models\User as User;
use RZP\Models\Merchant\Entity as MerchantEntity;


class Service extends Base\Service
{

    /**
     * @var \RZP\Services\AuthService
     */
    protected $authservice;

    public function __construct()
    {
        parent::__construct();

        $this->authservice = $this->app['authservice'];
    }

    public function create()
    {
        $input = Request::all();

        $entity = $this->core()->create($input);

        return $entity;
    }

    /**
     * @throws Exception\ServerErrorException
     * @throws Exception\BadRequestException
     */
    public function createForAppleWatch(array $input, User\Entity $user, MerchantEntity $merchant, string $mode): array
    {

        (new Validator())->validateCreateForAppleWatch($input, $user->getId(),$merchant->getId(),
            $merchant->isActivated() === true, $mode);

        (new User\Core)->verifyOtp($input + ['action' => 'apple_watch_token'],
                                    $merchant,
                                    $user,
                                    $this->app->environment('production') === false);


        $oAuthAppService = new \RZP\Models\OAuthApplication\Service();

        $oAuthApp = $oAuthAppService->createOrGetApplication($merchant,Constants::APPLE_WATCH_TOKEN['TYPE']);

        if (is_null($oAuthApp))
        {
            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_AUTH_SERVICE_FAILURE,
                [
                    'message' => 'Auth service did not return OAuth app',
                ]
            );
        }

        $request = [
            'client_id'     => $oAuthApp['client_details']['prod']['id'],
            'client_secret' => $oAuthApp['client_details']['prod']['secret'],
            'grant_type'    => Constants::APPLE_WATCH_TOKEN['GRANT_TYPE'],
            'scope'         => Constants::APPLE_WATCH_TOKEN['SCOPE'],
            'mode'          => Constants::APPLE_WATCH_TOKEN['MODE'],
            'user_id'       => $user->getId()
        ];

        return $this->authservice->createToken($request);
    }
}
