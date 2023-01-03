<?php

namespace RZP\Models\Partner\Config;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Models\Partner;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Methods;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Constants::PARTNER_ID           => 'required_without:' . Constants::APPLICATION_ID . '|alpha_num|size:14',
        Constants::APPLICATION_ID       => 'required_without:' . Constants::PARTNER_ID . '|alpha_num|size:14',
        Constants::SUBMERCHANT_ID       => 'filled|alpha_num|size:14',
        Entity::REVISIT_AT              => 'sometimes|integer',
        Entity::DEFAULT_PLAN_ID         => 'required|alpha_num|size:14',
        Entity::IMPLICIT_PLAN_ID        => 'sometimes|alpha_num|size:14|nullable',
        Entity::EXPLICIT_PLAN_ID        => 'sometimes|alpha_num|size:14|nullable',
        Entity::COMMISSION_MODEL        => 'sometimes|string|custom', // will make required once dashboard admin changes go live
        Entity::IMPLICIT_EXPIRY_AT      => 'sometimes|integer|nullable',
        Entity::COMMISSIONS_ENABLED     => 'required|boolean',
        Entity::EXPLICIT_REFUND_FEES    => 'required_with:' . Entity::EXPLICIT_PLAN_ID . '|boolean',
        Entity::EXPLICIT_SHOULD_CHARGE  => 'required_with:' . Entity::EXPLICIT_PLAN_ID . '|boolean',
        Entity::SETTLE_TO_PARTNER       => 'sometimes|boolean',
        Entity::TDS_PERCENTAGE          => 'sometimes|integer',
        Entity::HAS_GST_CERTIFICATE     => 'sometimes|boolean',
        Entity::DEFAULT_PAYMENT_METHODS => 'sometimes|array|custom',
        Entity::SUB_MERCHANT_CONFIG     => 'nullable|array',
        Entity::PARTNER_METADATA        => 'nullable|array|custom'
    ];

    protected static $editRules = [
        Entity::REVISIT_AT              => 'sometimes|integer',
        Entity::DEFAULT_PLAN_ID         => 'sometimes|alpha_num|size:14',
        Entity::IMPLICIT_PLAN_ID        => 'sometimes|alpha_num|size:14|nullable',
        Entity::EXPLICIT_PLAN_ID        => 'sometimes|alpha_num|size:14|nullable',
        Entity::COMMISSION_MODEL        => 'sometimes|string|custom', // will make required once dashboard admin changes go live
        Entity::IMPLICIT_EXPIRY_AT      => 'sometimes|integer|nullable',
        Entity::COMMISSIONS_ENABLED     => 'sometimes|boolean',
        Entity::EXPLICIT_REFUND_FEES    => 'sometimes|boolean',
        Entity::EXPLICIT_SHOULD_CHARGE  => 'sometimes|boolean',
        Entity::SETTLE_TO_PARTNER       => 'sometimes|boolean',
        Entity::TDS_PERCENTAGE          => 'sometimes|integer',
        Entity::HAS_GST_CERTIFICATE     => 'sometimes|boolean',
        Entity::DEFAULT_PAYMENT_METHODS => 'sometimes|array|custom',
        Entity::SUB_MERCHANT_CONFIG     => 'sometimes|string',
        Entity::PARTNER_METADATA        => 'sometimes|array|custom'
    ];

    protected static $partnerUpsertRules = [
        Entity::PARTNER_METADATA => 'sometimes|array|custom'
    ];

    protected static $partnerMetadataSettingsRules = [
        Constants::BRAND_NAME              => 'sometimes|string|max:255',
        Constants::BRAND_COLOR             => 'sometimes|regex:(^[0-9a-fA-F]{6}$)',
        Constants::TEXT_COLOR              => 'sometimes|regex:(^[0-9a-fA-F]{6}$)',
        Constants::LOGO_URL                => 'sometimes|max:2000'
    ];

    protected static $createValidators = [
        'add_implicit_expiry_date',
    ];

    public function validateEmptyConfig($config)
    {
        if (empty($config) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_APPLICATION_SUBMERCHANT_CONFIG_EXISTS,
                null, [
                    Entity::ENTITY_ID => $config->{Entity::ENTITY_ID},
                    Entity::ORIGIN_ID => $config->{Entity::ORIGIN_ID},
                    Entity::ID        => $config->{Entity::ID},
                ]);
        }
    }

    /**
     * @param $attribute
     * @param $type
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateCommissionModel($attribute, $type)
    {
        CommissionModel::validate($type);
    }

    public function validateAddImplicitExpiryDate(array $input)
    {
        // expiry date should not be set for subvention model
        if (((empty($input[Entity::IMPLICIT_EXPIRY_AT]) === false)) and
            (empty($input[Entity::COMMISSION_MODEL]) === false) and
            ($input[Entity::COMMISSION_MODEL] === CommissionModel::SUBVENTION))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_EXPIRY_DATE_SET_FOR_SUBVENTION);
        }
    }

    /**
     * Blocks non settlement partner types to set/update this attribute
     *
     * @param Merchant\Entity      $partner
     * @param array                $input
     * @param Merchant\Entity|null $subMerchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateSettleToPartner(
        Merchant\Entity $partner,
        array $input,
        Merchant\Entity $subMerchant = null)
    {
        if (empty($input[Entity::SETTLE_TO_PARTNER]) === true)
        {
            return;
        }

        $partnerType = $partner->getPartnerType();

        if (in_array($partnerType, Partner\Constants::$settlementPartnerTypes, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_CONFIGURATION_INVALID,
                Entity::SETTLE_TO_PARTNER,
                $input);
        }
    }

    /**
     * Blocks non Default Payment Methods Partner Types to set/update Payment Methods
     *
     * @param Merchant\Entity $partner
     * @param array           $input
     *
     * @throws Exception\BadRequestException
     */
    public function validatePaymentMethodsForPartnerType(Merchant\Entity $partner, array $input)
    {
        if (empty($input[Entity::DEFAULT_PAYMENT_METHODS]) === true)
        {
            return;
        }

        $partnerType = $partner->getPartnerType();

        if (in_array($partnerType, Partner\Constants::$defaultPaymentMethodsPartnerTypes, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_CONFIGURATION_INVALID,
                Entity::DEFAULT_PAYMENT_METHODS,
                $input);
        }
    }

    public function validateDefaultPaymentMethods(string $attribute, $value)
    {
        if (isset($value) === false)
        {
            return;
        }

        (new Methods\Validator())->validateInput('set_methods', $value);
    }

    /**
     * if the request is coming from partner dashboard then
     *  1. check if the exp is enabled
     *  2. check if the merchant is an aggregator partner
     *  3. check if the mid from request and from auth are same
     *  4. validate the request input if present
     *
     * if the request is coming from dashboard guest app then
     *  1. check if the exp is enabled
     *  2. check if the merchant is an aggregator partner
     *
     * @param array|null $input
     *
     * @throws Exception\BadRequestException
     */
    public function validateRequestOrigin(array $input = null)
    {
        $app = App::getFacadeRoot();

        if ($app['basicauth']->isProxyAuth() === true and $app['basicauth']->isAdminAuth() === false)
        {
            $merchantId = $app['basicauth']->getMerchantId();

            $this->validateSubmerchantWhitelabelOnboardingExpEnabled($merchantId);

            (new Merchant\Validator())->validateIsAggregatorPartner($app['basicauth']->getMerchant());

            if (isset($input[Constants::PARTNER_ID]) === true and $input[Constants::PARTNER_ID] !== $merchantId)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_MERCHANT_ID);
            }

            unset($input[Constants::PARTNER_ID]);

            if (empty($input) === false)
            {
                $this->validateInput('partner_upsert', $input);
            }
        }
        else if ($app['request.ctx']->isDashboardGuest() === true)
        {
            $merchantId = $input[Constants::PARTNER_ID] ?? null;

            if (empty($merchantId) === true)
            {
                return;
            }

            $this->validateSubmerchantWhitelabelOnboardingExpEnabled($merchantId);

            $merchant = (new Merchant\Service())->getMerchantFromMid($merchantId);

            (new Merchant\Validator())->validateIsAggregatorPartner($merchant);
        }
    }

    public function validatePartnerMetadata(string $attribute, $value)
    {
        if (isset($value) == false)
        {
            return;
        }

        $this->validateInput('partner_metadata_settings', $value);
    }

    /**
     * @throws Exception\BadRequestException
     */
    private function validateSubmerchantWhitelabelOnboardingExpEnabled(string $merchantId)
    {
        $app = App::getFacadeRoot();

        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $app['config']->get('app.partner_submerchant_whitelabel_onboarding')
        ];

        $expEnabled = (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');

        if ($expEnabled !== true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_WHITELABEL_ONBOARDING_EXP_NOT_ENABLED,
                null,
                ['partner_id' => $merchantId]
            );
        }
    }
}
