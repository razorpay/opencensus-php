<?php

namespace RZP\Models\OAuthApplication;

use Request;

use RZP\Constants\Entity as E;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Services\AuthService;
use RZP\Models\Merchant as Merchant;
use RZP\Models\Feature as FeatureModel;
use RZP\Constants\Entity as EntityConstants;
use Razorpay\OAuth\Application\Entity as App;

/*
 * Currently most of the business logic resides in controller
 */
class Service extends Base\Service
{

    /**
     * @var AuthService
     */
    protected $authservice;

    public function __construct()
    {
        parent::__construct();

        $this->authservice = $this->app['authservice'];
    }

    /**
     * This method has some code duplicated from OAuthTokenCreate controller
     * @throws Exception\ServerErrorException
     * @throws Exception\BadRequestException
     */
    public function createOrGetApplication(Merchant\Entity $merchant, string $oAuthAppType)
    {
        // Returns empty if type is empty
        // TODO: Instead of hitting AuthDB directly, we should fetch from ID MerchantApplication and then AuthService
        $oAuthApps = $this->authservice->getMultipleApplications(array('type' => $oAuthAppType), $merchant->getId());

        // auth-service may send 400 response with 200 status
        if (!array_key_exists('items',$oAuthApps))
        {
            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_AUTH_SERVICE_FAILURE,
                [
                    'message' => 'Get Apple Watch OAuth app failed',
                ]
            );
        }

        $validApp = null;

        foreach ($oAuthApps['items'] as $oAuthApp)
        {
            if ($oAuthApp['type'] === $oAuthAppType)
            {
                $validApp = $oAuthApp;
                break;
            }
        }

        // Create App if it does not exist
        if (empty($validApp))
        {

            $input = [
                'name'    => Constants::APPLE_WATCH_APP['NAME'],
                'website' => Constants::APPLE_WATCH_APP['WEBSITE'],
                'type'    => $oAuthAppType
            ];

            $data = $this->authservice->createApplication($input, $merchant->getId());

            if (!empty($data[App::ID]))
            {
                (new Merchant\Core)->createMerchantApplication($merchant, $data[App::ID], Merchant\MerchantApplications\Entity::MERCHANT);
            }

            $featureParams = [
                FeatureModel\Entity::ENTITY_TYPE => E::APPLICATION,
                FeatureModel\Entity::ENTITY_ID   => $data[App::ID],
                FeatureModel\Entity::NAME        => FeatureModel\Constants::RAZORPAYX_FLOWS_VIA_OAUTH
            ];

            (new FeatureModel\Core)->create($featureParams);

            $validApp = $data;
        }

        return $validApp;
    }
}
