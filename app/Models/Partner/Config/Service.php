<?php

namespace RZP\Models\Partner\Config;

use RZP\Constants\Entity as CE;
use RZP\Exception;
use RZP\Exception\BaseException;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;

use Razorpay\OAuth\Application as OAuthApp;
use RZP\Models\Merchant\Constants;
use RZP\Models\Partner\Config\Validator;
use RZP\Models\Partner\Metric;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    /**
     * @var OAuthApp\Repository
     */
    private $applicationRepo;

    public function __construct()
    {
        parent::__construct();

        $this->applicationRepo = new OAuthApp\Repository;
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function create(array $input): array
    {
        $application = $this->getApplicationFromInput($input);
        $subMerchant = $this->getSubMerchantFromInput($input);

        $config = (new Core)->create($application, $input, $subMerchant);

        return $config->toArrayPublic();
    }

    /**
     * @param array $input
     *
     * @return OAuthApp\Entity
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    protected function getApplicationFromInput(array $input): OAuthApp\Entity
    {
        $this->validateConfigInput($input);

        $application = null;

        if (empty($input[Constants::APPLICATION_ID]) === false)
        {
            $application = $this->applicationRepo->findOrFailPublic($input[Constants::APPLICATION_ID]);
        }
        else
        {
            if (empty($input[Constants::PARTNER_ID]) === false)
            {
                $partnerMerchantId = $input[Constants::PARTNER_ID];

                $partnerMerchantId = Account\Entity::verifyIdAndSilentlyStripSign($partnerMerchantId);

                $partnerMerchant = $this->repo->merchant->findOrFailPublic($partnerMerchantId);

                // Block non partners
                (new Merchant\Validator)->validateIsPartner($partnerMerchant);

                // Block pure platform if partner id is sent instead of app id
                if ($partnerMerchant->isNonPurePlatformPartner() === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PARTNER_ID_SENT_FOR_PURE_PLATFORM,
                        Constants::PARTNER_ID,
                        [
                            Constants::PARTNER_ID         => $partnerMerchant->getId(),
                            Merchant\Entity::PARTNER_TYPE => $partnerMerchant->getPartnerType(),
                        ]);
                }

                $application = (new Merchant\Core())->fetchPartnerApplication($partnerMerchant);
            }
        }

        return $application;
    }

    /**
     * @param array $input
     *
     * @return Merchant\Entity|null
     */
    protected function getSubMerchantFromInput(array $input)
    {
        $subMerchant = null;

        if (empty($input[Constants::SUBMERCHANT_ID]) === false)
        {
            $subMerchantId = $input[Constants::SUBMERCHANT_ID];

            $subMerchantId = Account\Entity::verifyIdAndSilentlyStripSign($subMerchantId);

            $subMerchant = $this->repo->merchant->findOrFailPublic($subMerchantId);
        }

        return $subMerchant;
    }

    /**
     * If only the partner_id or application_id is passed in the input,
     * an array of configurations are returned which includes the default application config and
     * all the overridden configs for that partner/application.
     *
     * If the submerchant_id is sent along with partner_id or application id,
     * the relevant submerchant config (overridden config, if available; else default config) is returned.
     *
     * @param array $input
     *
     * @return array|null
     * @throws Exception\BadRequestException
     */
    public function fetch(array $input)
    {
        $application = $this->getApplicationFromInput($input);
        $subMerchant = $this->getSubMerchantFromInput($input);

        $core       = new Core;
        $configData = null;

        if (empty($subMerchant) === true)
        {
            $configs    = $core->fetchAllConfigForApp($application);
            $configData = $configs->toArrayPublicEmbedded();
        }
        else
        {
            $config     = $core->fetch($application, $subMerchant);
            $configData = optional($config)->toArrayPublic();
        }

        return $configData;
    }

    public function update(string $id, array $input): array
    {
        $config = (new Core)->edit($id, $input);

        return $config->toArrayPublic();
    }

    public function fetchConfigByPartner(): array
    {
        (new Merchant\Validator)->validateIsPartner($this->merchant);

        $configs = $this->core()->fetchAllConfigsByPartner($this->merchant);

        return $configs->toArrayPublic();
    }

    /**
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    protected function validateConfigInput(array $input)
    {
        // check if both application id and partner id are absent
        if ((empty($input[Constants::APPLICATION_ID]) === true) and
            (empty($input[Constants::PARTNER_ID]) === true))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_APPLICATION_ID_OR_PARTNER_ID_MISSING);
        }

        // check if both application id and partner id are present
        if ((empty($input[Constants::PARTNER_ID]) === false) and (empty($input[Constants::APPLICATION_ID]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_APPLICATION_ID_PARTNER_ID_BOTH_PRESENT,
                null,
                [
                    Constants::APPLICATION_ID => $input[Constants::PARTNER_ID],
                    Constants::PARTNER_ID     => $input[Constants::APPLICATION_ID],
                ]
            );
        }
    }

    public function bulkUpsertSubmerchantPartnerConfig(array $input)
    {
        $response = new Base\PublicCollection();

        foreach ($input as $record)
        {
            try
            {

                $this->processSubMerchantPartnerConfig($record);

                $record['status'] = Constants::SUCCESS;

                $response->push($record);

                $this->trace->count(Metric::PARTNER_CONFIG_ACTION_SUCCESS_TOTAL);

            }
            catch (Exception\BaseException $exception)
            {
                $record['status'] = Constants::FAILURE;

                (new Merchant\Service())->setErrorAttributesToResponse($record, $exception, $response);

            }
        }
        $this->trace->count(Metric::PARTNER_CONFIG_BATCH_ACTION_SUCCESS_TOTAL);

        return $response->toArrayWithItems();
    }

    public function processSubMerchantPartnerConfig(array $record)
    {
        $settings   = [];
        $attributes = [];

        (new Merchant\Service)->segregateInputFieldsAndSettings($record, $attributes, $settings);

        (new Merchant\Validator)->validateInput('access_map_batch', $settings);

        $batch_action = $settings[Constants::BATCH_ACTION];

        $function = camel_case($batch_action);

        $this->$function($attributes);
    }

    /**
     * @param $attributes
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function submerchantPartnerConfigUpsert($attributes)
    {
        $entites = $this->validatePartnerRelationshipAndGetEntities($attributes);

        unset($attributes[CONSTANTS::PARTNER_ID]);
        unset($attributes[CONSTANTS::MERCHANT_ID]);

        $merchant   = $entites[0];
        $partner    = $entites[1];
        $accessMaps = $entites[2];

        $appIds = [];

        foreach ($accessMaps as $accessMap)
        {
            $appId = $accessMap->getEntityId();
            array_push($appIds, $appId);
        }

        return $this->upsertPartnerConfigsForMerchant($merchant, $partner, $appIds, $attributes);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param Merchant\Entity $partner
     * @param array           $appIds
     * @param                 $attribute
     *
     * @return array
     */
    public function upsertPartnerConfigsForMerchant(Merchant\Entity $merchant, Merchant\Entity $partner, array $appIds, $attribute)
    {

        $createResponse = [];

        $updateResponse = $this->core()->updatePartnerConfigForSubmerchant($merchant, $appIds, $attribute);

        $updatedConfigsAppIds = array_column($updateResponse['items'] ?? [], Entity::ORIGIN_ID);

        $appIds = array_diff($appIds, $updatedConfigsAppIds);

        $this->trace->info(
            TraceCode::SUBMERCHANT_PARTNER_CONFIG_UPSERT_RESPONSE,
            [
                "APP_IDS_TO_CREATE_PARTNER_CONFIG" => $appIds,
                "APP_IDS_TO_UPDATE_PARTNER_CONFIG" => $updatedConfigsAppIds

            ]);

        if (empty($appIds) === false)
        {

            $createResponse = $this->core()->createPartnerConfigForSubmerchant($merchant, $partner, $appIds, $attribute);
        }

        $response = ['CREATED_PARTNER_CONFIGS' => $createResponse, 'UPDATED_PARTNER_CONFIGS' => $updateResponse];

        $this->trace->info(
            TraceCode::SUBMERCHANT_PARTNER_CONFIG_UPSERT_RESPONSE,
            [
                "merchant_id" => $merchant->getId(),
                "response"    => $response

            ]);

        return $response;
    }

    /**
     * @param $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    protected function validatePartnerRelationshipAndGetEntities($input)
    {

        $partnerId  = $input[Constants::PARTNER_ID] ?? null;
        $merchantId = $input[Constants::MERCHANT_ID] ?? null;

        $partner = $this->repo->merchant->find($partnerId);

        if (empty($partner) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_ID_DOES_NOT_EXIST);
        }

        $merchant = $this->repo->merchant->find($merchantId);

        if (empty($merchant) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_DOES_NOT_EXIST);
        }

        $accessMaps = $this->repo->merchant_access_map->fetchAccessMapForMerchantIdAndOwnerId($merchantId, $partnerId);

        if ($accessMaps->isEmpty() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER);
        }

        return [$merchant, $partner, $accessMaps];
    }

}
