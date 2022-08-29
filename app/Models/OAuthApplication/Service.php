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
     * @description This method has some code duplicated from OAuthTokenCreate controller
     * @param Merchant\Entity $merchant
     * @param array $metaData
     * @return array|mixed|null
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\ServerErrorException
     */
    public function createOrGetApplication(Merchant\Entity $merchant, array $metaData)
    {
        $oAuthAppType    = $metaData[Constants::TYPE];

        $oAuthAppName    = $metaData[Constants::NAME];

        $oAuthAppWebsite = $metaData[Constants::WEBSITE];

        // Returns empty if type is empty
        // TODO: Instead of hitting AuthDB directly, we should fetch from ID MerchantApplication and then AuthService
        $validApp = $this->getApplication($merchant, $oAuthAppType);

        // Create App if it does not exist
        if (empty($validApp))
        {
            $input = [
                Constants::NAME    => $oAuthAppName,
                Constants::WEBSITE => $oAuthAppWebsite,
                Constants::TYPE    => $oAuthAppType,
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

    /**
     * @param Merchant\Entity $merchant
     * @param string $oAuthAppType
     * @return mixed|null
     * @throws Exception\ServerErrorException
     */
    public function getApplication(Merchant\Entity $merchant, string $oAuthAppType)
    {
        // Returns empty if type is empty
        $oAuthApps = $this->authservice->getMultipleApplications(['type' => $oAuthAppType], $merchant->getId());

        // auth-service may send 400 response with 200 status
        if (!array_key_exists(Constants::ITEMS,$oAuthApps))
        {
            throw new Exception\ServerErrorException(
                'Error completing the request',
                ErrorCode::SERVER_ERROR_AUTH_SERVICE_FAILURE,
                [
                    'message' => 'Get OAuth app failed',
                ]
            );
        }

        $validApp = null;

        foreach ($oAuthApps[Constants::ITEMS] as $oAuthApp)
        {
            if ($oAuthApp[Constants::TYPE] === $oAuthAppType)
            {
                $validApp = $oAuthApp;
                break;
            }
        }

        return $validApp;
    }

}
