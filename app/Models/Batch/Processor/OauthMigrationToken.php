<?php

namespace RZP\Models\Batch\Processor;

use Razorpay\OAuth\Client as OAuthClient;
use Razorpay\OAuth\Application as OAuthApp;

use RZP\Models\Merchant;
use RZP\Models\Batch\Type;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Status;
use RZP\Models\Batch\Header;
use RZP\Services\AuthService;
use RZP\Models\Merchant\AccessMap;
use RZP\Models\Batch\Helpers\OauthMigration as H;
use RZP\Exception\BadRequestValidationFailureException;

class OauthMigrationToken extends Base
{
    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * @var OAuthApp\Entity
     */
    protected $clientApp;

    /**
     * @var OAuthClient\Entity
     */
    protected $client;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->authService = $this->app['authservice'];
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
            $subMerchant = $this->repo->merchant->findOrFailPublic($entry[Header::MERCHANT_ID]);

            $token = $this->createOAuthToken($tokenInput);

            $this->connectMerchantToPartner($subMerchant);

            $this->updateOutputData($entry, $token);
        });
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

        $this->clientApp = $this->client->application;
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
            return $this->authService->createOAuthMigrationToken($entry);
        }
        catch (\Throwable $t)
        {
            $this->trace->traceException($t);

            throw new BadRequestValidationFailureException('OAuth token creation failed');
        }
    }

    protected function connectMerchantToPartner(Merchant\Entity $subMerchant)
    {
        $appId = $this->clientApp->getId();

        $mapInput = [OAuthClient\Entity::APPLICATION_ID => $appId];

        (new AccessMap\Core)->addMappingForOAuthApp($subMerchant, $mapInput);
    }

    protected function updateOutputData(array & $entry, array $token)
    {
        $entry[Header::STATUS]        = Status::SUCCESS;
        $entry[Header::ACCESS_TOKEN]  = $token[Header::ACCESS_TOKEN];
        $entry[Header::REFRESH_TOKEN] = $token[Header::REFRESH_TOKEN];
        $entry[Header::PUBLIC_TOKEN]  = $token[Header::PUBLIC_TOKEN];

        $entry = array_only($entry, Header::HEADER_MAP[Type::OAUTH_MIGRATION_TOKEN][Header::OUTPUT]);
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
