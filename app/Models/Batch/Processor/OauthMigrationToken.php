<?php

namespace RZP\Models\Batch\Processor;

use Razorpay\OAuth\Client as OAuthClient;
use Razorpay\OAuth\Application as OAuthApp;

use RZP\Models\Merchant;
use RZP\Services\AuthService;
use RZP\Exception\BaseException;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\AccessMap;
use RZP\Models\Batch\Helpers\OauthMigration as H;
use RZP\Models\Batch\{Type, Entity, Status, Header};
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Feature\{
    Constants as FConstants,
    Type as FType,
    Entity as FEntity,
    Core as FCore
};

class OauthMigrationToken extends Base
{
    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * @var FCore
     */
    protected $featureCore;

    /**
     * @var AccessMap\Core
     */
    protected $accessMapCore;

    /**
     * @var OAuthApp\Entity
     */
    protected $appId;

    /**
     * @var OAuthClient\Entity
     */
    protected $client;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->authService = $this->app['authservice'];

        $this->accessMapCore = (new AccessMap\Core);

        $this->featureCore = (new FCore);
    }

    protected function processEntry(array & $entry)
    {
        $this->repo->transactionOnLiveAndTest(function() use (& $entry)
        {
            $tokenInput[Header::MERCHANT_ID]    = $entry[Header::MERCHANT_ID];
            $tokenInput[H::PARTNER_MERCHANT_ID] = $this->merchant->getId();
            $tokenInput[H::CLIENT_ID]           = $this->client->getId();
            $tokenInput[H::USER_ID]             = $this->params[H::USER_ID];
            $tokenInput[H::REDIRECT_URI]        = $this->params[H::REDIRECT_URI];

            // Getting the merchant here instead of later in the connect call as this
            // would act as a validation for the merchant_id input before even making
            // auth-service call for token.
            $subMerchant = $this->repo->merchant->findOrFailPublic($tokenInput[Header::MERCHANT_ID]);
            $entityOwner = $this->repo->merchant->findOrFailPublic($tokenInput[H::PARTNER_MERCHANT_ID]);

            $token = $this->createOAuthToken($tokenInput);

            $this->connectMerchantToPartner($entityOwner, $subMerchant);

            $this->assignS2SIfApplicable($entry);

            $this->updateOutputData($entry, $token);
        });
    }

    /**
     * Assign 'allow_s2s_apps' feature to a pure platform sub-merchant
     * if the batch is run for competitor's apps as the s2s routes
     * don't work for them via oauth if the feature is not assigned.
     * This is done to keep a check on competitors' sub-merchant onboarding.
     *
     * @param array $entry
     */
    protected function assignS2SIfApplicable(array $entry)
    {
        if (in_array($this->appId, FType::S2S_APPLICATION_IDS, true) === false)
        {
            return;
        }

        $featureParams = [
            FEntity::ENTITY_TYPE => E::MERCHANT,
            FEntity::ENTITY_ID   => $entry[Header::MERCHANT_ID],
            FEntity::NAME        => FConstants::ALLOW_S2S_APPS
        ];

        $this->featureCore->create($featureParams, true);
    }

    protected function performPreProcessingActions()
    {
        $this->setUpClientAndApp();

        return parent::performPreProcessingActions();
    }

    protected function setUpClientAndApp()
    {
        $clientId = $this->params[H::CLIENT_ID];

        /** @var OAuthClient\Entity $client */
        $this->client = (new OAuthClient\Repository)->findOrFail($clientId);

        $this->appId = $this->client->application->getId();
    }

    /**
     * @param  array $entry
     *
     * @return array
     *
     * @throws BadRequestValidationFailureException
     */
    protected function createOAuthToken(array & $entry)
    {
        try
        {
             $token = $this->authService->createOAuthMigrationToken($entry);

             if (empty($token) === false)
             {
                return  $token;
             }

             throw new BaseException('Empty token received');
        }
        catch (\Throwable $t)
        {
            $this->trace->traceException($t);

            throw new BadRequestValidationFailureException('OAuth token creation failed');
        }
    }

    protected function connectMerchantToPartner(Merchant\Entity $entityOwner, Merchant\Entity $subMerchant)
    {
        $mapInput = [OAuthClient\Entity::APPLICATION_ID => $this->appId];

        $this->accessMapCore->addMappingForOAuthApp($entityOwner, $subMerchant, $mapInput);
    }

    protected function updateOutputData(array & $entry, array $token)
    {
        $entry[Header::STATUS]        = Status::SUCCESS;
        $entry[Header::ACCESS_TOKEN]  = $token[Header::ACCESS_TOKEN];
        $entry[Header::REFRESH_TOKEN] = $token[Header::REFRESH_TOKEN];
        $entry[Header::PUBLIC_TOKEN]  = $token[Header::PUBLIC_TOKEN];
        $entry[Header::EXPIRES_IN]    = $token[Header::EXPIRES_IN];

        $entry = array_only($entry, Header::HEADER_MAP[Type::OAUTH_MIGRATION_TOKEN][Header::OUTPUT]);
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }

    public function addSettingsIfRequired(& $input)
    {
        $config =[];

        if (isset($input['config']) === true) {
            $config = $input['config'];
        }

        $config[H::CLIENT_ID]     = $input[H::CLIENT_ID];
        $config[H::USER_ID]       = $input[H::USER_ID];
        $config[H::REDIRECT_URI]  = $input[H::REDIRECT_URI];

        $input['config']          = $config;
    }
}
