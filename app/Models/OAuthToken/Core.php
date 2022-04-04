<?php

namespace RZP\Models\OAuthToken;

use Mail;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Models\Batch\Type;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Constants\HyperTrace;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\AccessMap;
use RZP\Models\Feature\Core as FCore;
use RZP\Models\Feature\Type as FType;
use RZP\Models\Feature\Entity as FEntity;
use Razorpay\OAuth\Client as OAuthClient;
use RZP\Models\Feature\Constants as FConstants;
use RZP\Models\Batch\Helpers\OauthMigration as H;

class Core extends Base\Core
{
    public function create($input)
    {
        $validator = new Validator;

        $validator->validateInput('create', $input);

        $output = $this->repo->transactionOnLiveAndTest(function() use (& $input)
        {
            $tokenInput[Header::MERCHANT_ID]    = $input[Header::MERCHANT_ID];
            $tokenInput[H::PARTNER_MERCHANT_ID] = $this->merchant->getId();
            $tokenInput[H::CLIENT_ID]           = $input[H::CLIENT_ID];
            $tokenInput[H::USER_ID]             = $input[H::USER_ID];
            $tokenInput[H::REDIRECT_URI]        = $input[H::REDIRECT_URI];

            // Getting the merchant here instead of later in the connect call as this
            // would act as a validation for the merchant_id input before even making
            // auth-service call for token.
            $subMerchant = $this->repo->merchant->findOrFailPublic($tokenInput[Header::MERCHANT_ID]);
            $entityOwner = $this->repo->merchant->findOrFailPublic($tokenInput[H::PARTNER_MERCHANT_ID]);

            $token = Tracer::inspan(['name' => HyperTrace::CREATE_OAUTH_MIGRATION_TOKEN], function () use($tokenInput) {

                return $this->app['authservice']->createOAuthMigrationToken($tokenInput);
            });

            //Set Client and App
            $client = (new OAuthClient\Repository)->findOrFail($input[H::CLIENT_ID]);

            $appId = $client->application->getId();

            //connect merchant to partner
            $mapInput = [OAuthClient\Entity::APPLICATION_ID => $appId];

            Tracer::inspan(['name' => HyperTrace::ADD_MAPPING_FOR_OAUTH_APP], function () use($entityOwner, $subMerchant, $mapInput) {

                (new AccessMap\Core)->addMappingForOAuthApp($entityOwner, $subMerchant, $mapInput);
            });

            Tracer::inspan(['name' => HyperTrace::ASSIGN_S2S_IF_APPLICABLE], function () use($input, $appId) {

                $this->assignS2SIfApplicable($input, $appId);
            });

            $this->updateOutputData($input, $token);

            return $input;
        });

        return $output;
    }

    /**
     * Assign 'allow_s2s_apps' feature to a pure platform sub-merchant
     * if the batch is run for competitor's apps as the s2s routes
     * don't work for them via oauth if the feature is not assigned.
     * This is done to keep a check on competitors' sub-merchant onboarding.
     *
     * @param array $entry
     * @param $appId
     */
    protected function assignS2SIfApplicable(array $entry, $appId)
    {
        $featureCore = (new FCore);

        if (in_array($appId, FType::S2S_APPLICATION_IDS, true) === false)
        {
            return;
        }

        $featureParams = [
            FEntity::ENTITY_TYPE => E::MERCHANT,
            FEntity::ENTITY_ID   => $entry[Header::MERCHANT_ID],
            FEntity::NAME        => FConstants::ALLOW_S2S_APPS
        ];

        Tracer::inspan(['name' => HyperTrace::ADD_FEATURE_REQUEST], function () use ($featureCore, $featureParams) {

            $featureCore->create($featureParams, true);
        });
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
}
