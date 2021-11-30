<?php

namespace RZP\Models\Merchant;

use App;
use Hash;

use RZP\Base;
use RZP\Constants\Country;
use RZP\Exception;
use RZP\Models\User;
use FuzzyWuzzy\Fuzz;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\User\Role;
use RZP\Models\Settlement;
use RZP\Constants\Product;
use RZP\Models\Admin\Admin;
use RZP\Models\Address;
use RZP\Models\Payment\Event;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Balance;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Workflow\Action\Differ;
use RZP\Models\Admin\Permission\Name as Permission;
use \RZP\Models\Workflow\Action\Core as ActionCore;
use RZP\Exception\BadRequestValidationFailureException;
use \RZP\Models\Workflow\Action\Entity as ActionEntity;
use RZP\Models\Merchant\Detail\ActivationFlow as ActivationFlow;
use RZP\Models\RiskWorkflowAction\Constants as RiskActionConstants;
use RZP\Models\Merchant\ProductInternational\ProductInternationalField;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;
use RZP\Models\Merchant\Detail\InternationalActivationFlow\InternationalActivationFlow;

/**
 * Class Validator
 *
 * @package RZP\Models\Merchant
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    // Maximum image size - 1M.
    const MAXIMAGESIZE = 1024 * 1024;
    const PREFERENCES     = 'preferences';
    const PERSONALISATION = 'personalisation';

    const BATCH_ID                          = 'Batch Id';
    const BULK_SUBMERCHANT_ASSIGN           = 'Bulk Submerchant Assign';
    // Rate limit on items sending for bulk submerchant assign.
    const MAX_BULK_SUBMERCHANT_ASSIGN_LIMIT = 15;

    // Thresholds for billing label validation
    const THRESHOLD_FOR_WEBSITE_SIMILARITY = 80;

    const THRESHOLD_FOR_BUSINESS_NAME_SIMILARITY = 80;

    const NEW_BILLING_LABEL = 'new_billing_label';

    const OLD_BILLING_LABEL = 'old_billing_label';

    const IS_FROM_SUGGESTIONS =  'is_value_from_suggestions';

    const SIMILARITY_WITH_WEBSITE = 'similarity_with_website';

    const SIMILARITY_WITH_BUSINESS_NAME = 'similarity_with_business_name';

    const VALIDATION_STATUS = 'validation_status';

    const PASSED = 'passed';

    const FAILED = 'failed';

    const BILLING_LABEL_INVALID_MESSAGE = 'Invalid value, the brand name must be similar to business name or website name';

    const EMAIL_UPDATE_SAME_AS_CURRENT_VALIDATION_FAILURE_MESSAGE = 'Provided Email Should Be different than current one';

    const EXTENSIONMIMEMAP = [
        'jpeg'  => 'image/jpeg',
        'jpg'   => 'image/jpeg',
        'png'   => 'image/png',
    ];

    const ACTION_PERMISSION_MAP_FOR_MERCHANT_EDIT_BULK = [
        Action::SUSPEND               => Permission::EDIT_MERCHANT_SUSPEND_BULK,
        Action::UNSUSPEND             => Permission::EDIT_MERCHANT_SUSPEND_BULK,
        Action::LIVE_DISABLE          => Permission::EDIT_MERCHANT_TOGGLE_LIVE_BULK,
        Action::LIVE_ENABLE           => Permission::EDIT_MERCHANT_TOGGLE_LIVE_BULK,
        Action::HOLD_FUNDS            => Permission::EDIT_MERCHANT_HOLD_FUNDS_BULK,
        Action::RELEASE_FUNDS         => Permission::EDIT_MERCHANT_HOLD_FUNDS_BULK
    ];

    const MERCHANT_RISK_ATTRIBUTES = [
        Entity::MAX_PAYMENT_AMOUNT
    ];

    protected static $createRules = [
        Entity::ID                          => 'sometimes|alpha_num|size:14|unique:merchants',
        Entity::NAME                        => 'sometimes|string|max:200',
        Entity::EMAIL                       => 'sometimes|email',
        Entity::ORG_ID                      => 'sometimes|alpha_num|size:14',
        Entity::GROUPS                      => 'sometimes|array',
        Entity::ADMINS                      => 'sometimes|array',
        Entity::COUPON_CODE                 => 'sometimes|string',
        Constants::PARTNER_INTENT           => 'sometimes|boolean',
        Entity::EXTERNAL_ID                 => 'sometimes|string|max:255',
        Entity::SIGNUP_SOURCE               => 'sometimes|string|max:32',
        Entity::CODE                        => 'custom',
        Entity::SIGNUP_VIA_EMAIL            => 'sometimes|in:0,1',
    ];

    protected static $editRules = [
        Entity::NAME                                  => 'sometimes|string|max:200',
        Entity::HOLD_FUNDS                            => 'sometimes|in:0,1',
        Entity::WEBSITE                               => 'sometimes|url|max:255|nullable',
        Entity::CATEGORY                              => 'sometimes|string|digits:4',
        Entity::CATEGORY2                             => 'sometimes|string|max:30|custom',
        Entity::BILLING_LABEL                         => 'sometimes|max:255',
        Entity::TRANSACTION_REPORT_EMAIL              => 'sometimes|array',
        Entity::RECEIPT_EMAIL_ENABLED                 => 'sometimes|boolean',
        Entity::RECEIPT_EMAIL_TRIGGER_EVENT           => 'sometimes|nullable|string|in:authorized,captured',
        Entity::LINKED_ACCOUNT_KYC                    => 'sometimes|boolean',
        Entity::CHANNEL                               => 'sometimes|string|max:32|custom',
        Entity::RISK_RATING                           => 'sometimes|min:0|max:5',
        Entity::RISK_THRESHOLD                        => 'sometimes|integer|min:0|max:100',
        Entity::FEE_BEARER                            => 'sometimes|in:customer,platform,dynamic',
        Entity::FEE_MODEL                             => 'sometimes|in:prepaid,postpaid',
        Entity::REFUND_SOURCE                         => 'sometimes|string|max:32|in:balance,credits',
        Entity::MAX_PAYMENT_AMOUNT                    => 'sometimes|integer',
        // max: 5 days (don't change max value without consult), min:60 minutes
        Entity::AUTO_REFUND_DELAY                     => 'sometimes|string|custom',
        Entity::DEFAULT_REFUND_SPEED                  => 'sometimes|filled|string|in:normal,optimum',
        Entity::AUTO_CAPTURE_LATE_AUTH                => 'sometimes|boolean',
        Entity::CONVERT_CURRENCY                      => 'sometimes|nullable|boolean',
        Entity::ORG_ID                                => 'sometimes|alpha_num|size:14',
        Entity::GROUPS                                => 'sometimes|array',
        Entity::ADMINS                                => 'sometimes|array',
        Entity::WHITELISTED_IPS_LIVE                  => 'sometimes|array|max:15',
        Entity::WHITELISTED_IPS_LIVE . '.*'           => 'required_with:' . Entity::WHITELISTED_IPS_LIVE . '|ipv4',
        Entity::WHITELISTED_IPS_TEST                  => 'sometimes|array|max:15',
        Entity::WHITELISTED_IPS_TEST . '.*'           => 'required_with:' . Entity::WHITELISTED_IPS_TEST . '|ipv4',
        Entity::DASHBOARD_WHITELISTED_IPS_LIVE        => 'sometimes|array|max:20',
        Entity::DASHBOARD_WHITELISTED_IPS_LIVE . '.*' => 'distinct|required_with:' .
                                                         Entity::DASHBOARD_WHITELISTED_IPS_LIVE . '|ipv4',
        Entity::DASHBOARD_WHITELISTED_IPS_TEST        => 'sometimes|array|max:20',
        Entity::DASHBOARD_WHITELISTED_IPS_TEST . '.*' => 'distinct|required_with:' .
                                                         Entity::DASHBOARD_WHITELISTED_IPS_TEST . '|ipv4',
        Entity::FEE_CREDITS_THRESHOLD                 => 'sometimes|integer|nullable',
        Entity::AMOUNT_CREDITS_THRESHOLD              => 'sometimes|integer|nullable',
        Entity::REFUND_CREDITS_THRESHOLD              => 'sometimes|integer|nullable',
        Entity::PARTNERSHIP_URL                       => 'sometimes|max:2000',
        'reset_methods'                               => 'sometimes|boolean',
        Entity::PURPOSE_CODE                          => 'sometimes|string|max:5',
        Entity::EMAIL                                 => 'sometimes|email|unique:merchants',
    ];

    protected static $editBillingLabelRules = [
        Entity::BILLING_LABEL            => 'sometimes|filled|string|min:3|max:255|custom:billing_label'
    ];

    protected static $uniqueEmailRules = [
        Entity::EMAIL                       => 'required|email|unique:merchants'
    ];

    protected static $editCreditsRules = [
        Balance\Entity::AMOUNT_CREDITS      => 'required|integer|min:0|max:50000000'
    ];

    protected static $editEmailRules = [
        Entity::EMAIL                               => 'required|email|unique:merchants',
    ];

    protected static $editEmailNonUniqueRules = [
        Entity::EMAIL                               => 'required|email',
    ];

    protected static $changeEmailTokenRules = [
        User\Entity::PASSWORD                    => 'required|between:8,50|confirmed|numbers|letters',
        User\Entity::PASSWORD_CONFIRMATION       => 'required|between:8,50',
        User\Entity::TOKEN                       => 'required|string|size:50',
        Entity::MERCHANT_ID                      => 'required|string',
    ];

    protected static $editMerchantEmailSelfServeRules = [
        Entity::EMAIL                           => 'required|email|custom:edit_email_not_same_as_current',
        Constants::REATTACH_CURRENT_OWNER       => 'sometimes|boolean',
        Constants::SET_CONTACT_EMAIL            => 'sometimes|boolean',
    ];

    protected static $editPreSignupRules = [
        Entity::NAME                        => 'required|min:4|string|max:200',
        Entity::WEBSITE                     => 'sometimes|active_url|max:255|nullable',
        Entity::EMAIL                       => 'sometimes|email|unique:merchants',
    ];

    protected static $editNameRules = [
        Entity::NAME                        => 'required|min:4|string|max:200',
    ];

    protected static $editConfigRules = [
        Entity::BRAND_COLOR              => 'sometimes|regex:(^[0-9a-fA-F]{6}$)',
        Entity::TRANSACTION_REPORT_EMAIL => 'sometimes|array',
        Entity::LOGO_URL                 => 'sometimes|max:2000',
        Entity::INVOICE_LABEL_FIELD      => 'sometimes|filled|string|max:50|in:business_name,business_dba',
        Entity::AUTO_CAPTURE_LATE_AUTH   => 'sometimes|boolean',
        Entity::HANDLE                   => 'sometimes|nullable|min:3|max:4|custom|unique:merchants,handle,null',
        Entity::DISPLAY_NAME             => 'sometimes|nullable|string|min:3|max:255',
        Entity::FEE_CREDITS_THRESHOLD    => 'sometimes|integer|nullable',
        Entity::AMOUNT_CREDITS_THRESHOLD => 'sometimes|integer|nullable',
        Entity::REFUND_CREDITS_THRESHOLD => 'sometimes|integer|nullable',
        Entity::DEFAULT_REFUND_SPEED     => 'sometimes|filled|string|in:normal,optimum',
        Entity::FEE_BEARER               => 'sometimes|in:customer,platform',
        Entity::NOTES                    => 'sometimes|notes',
    ];

    protected static $actionRules = [
        Entity::ACTION                                      => 'required|custom',
        ProductInternationalMapper::INTERNATIONAL_PRODUCTS  => 'sometimes|array',
        RiskActionConstants::RISK_ATTRIBUTES                => 'sometimes|array',
        Constants::BULK_WORKFLOW_ACTION_ID                  => 'sometimes|string|size:14',
    ];

    protected static $change2faSettingRules = [
        User\Entity::PASSWORD         => 'sometimes|between:6,50',
        Entity::SECOND_FACTOR_AUTH    => 'required|boolean',
    ];

    protected static $change2faSettingValidators = [
        User\Entity::PASSWORD,
    ];

    protected static $bulkTagRules = [
        'action'         => 'required|string|filled|max:10|in:insert,delete',
        'name'           => 'required|string|filled',
        'merchant_ids'   => 'required|array',
        'merchant_ids.*' => 'required|string|filled|size:14'
    ];

    protected static $bulkAssignScheduleRules = [
        'schedule'       => 'required|array',
        'merchant_ids'   => 'required|array',
        'merchant_ids.*' => 'required|string|filled|size:14',
    ];

    protected static $bulkAssignPricingRules = [
        'pricing_plan_id' => 'required|string|size:14',
        'merchant_ids'    => 'required|array',
        'merchant_ids.*'  => 'required|string|filled|size:14',
    ];

    protected static $oauthMailRules = [
        'client_id'    => 'required|alpha_num|size:14',
        'user_id'      => 'required|alpha_num|size:14',
        'merchant_id'  => 'required|alpha_num|size:14'
    ];

    protected static $tallyAuthOtpMailRules = [
        'client_id'    => 'required|alpha_num|size:14',
        'user_id'      => 'required|alpha_num|size:14',
        'merchant_id'  => 'required|alpha_num|size:14',
        'otp'          => 'required',
        'email'        => 'required|email'
    ];

    protected static $merchantMailRules = [
        'type'           => 'required|string',
        'data'           => 'required|array'
    ];

    protected static $getPaymentFailureAnalysisRules = [
        'from'          => 'required|filled|epoch',
        'to'            => 'required|filled|epoch'
    ];

    protected static $instrumentStatusUpdateMerchantMailRules = [
        'contact_name'      => 'required|string',
        'contact_email'     => 'required|string',
        'current_status'    => 'required|string',
        'old_status'        => 'required|string',
        'instrument_name'   => 'required|string',
        'comment'           => 'sometimes|string',
        'profile_link'      => 'sometimes|string'
    ];

    protected static $featureRules = [
        'features'                   => 'required|array',
        'optout_reason'              => 'sometimes|string|max:200',
        Feature\Entity::SHOULD_SYNC  => 'sometimes|boolean',
        'es_enabled'                => 'sometimes|boolean',
    ];

    protected static $addTagsRules = [
        'tags'   => 'required|array',
        'tags.*' => 'required|string',
    ];

    protected static $updateMerchantsBulkRules = [
        'merchant_ids'           => 'required|sequential_array',
        'attributes'             => 'sometimes|associative_array',
        'risk_attributes'        => 'sometimes',
        'action'                 => 'sometimes',
        'international_products' => 'sometimes',
    ];

    protected static $keyAccessRules = [
        Entity::HAS_KEY_ACCESS => 'required|boolean',
    ];

    protected static $updateChannelRules = [
        'channel'       => 'required|string|max:32|custom',
        'merchant_ids'  => 'required|array'
    ];

    protected static $updateBankAccountRules = [
        'bank_account'   => 'required|array',
        'merchant_ids'   => 'required|array'
    ];

    protected static $createBatchRules = [
        'type'        => 'required|string|max:50',
        'data'        => 'required|array'
    ];

    protected static $payoutMailRules = [
        'content'               => 'required|array',
        'content.*.merchant_id' => 'required',
        'content.*.email'       => 'required',
    ];

    protected static $irctcRules = [
        'refund'       => 'sometimes|filled|file|mimes:txt|max:5120',
        'delta_refund' => 'sometimes|filled|file|mimes:txt|max:5120',
        'settlement'   => 'sometimes|filled|file|mimes:txt|max:5120',
    ];

    protected static $createSubMerchantUserRules = [
        'merchant_id'   => 'required|alpha_num|size:14',
        Entity::EMAIL   => 'required|email',
        Entity::PRODUCT => 'sometimes|string|in:primary,banking',
    ];

    protected static $editMethodsRules = [
        //only this method editing is allowed for now
        Methods\Entity::EMI => 'required|array',
    ];

    //only paypal method is allowed for now, change validation to allow more methods
    protected static $editMerchantMethodsRules = [
        Methods\Entity::PAYPAL  => 'sometimes|bool',
        Methods\Entity::PAYTM  => 'sometimes|bool',
    ];

    protected static $resetSettlementScheduleRules = [
        'merchant_ids'   => 'required|sequential_array',
        'merchant_ids.*' => 'required|alpha_num|size:14',
    ];

    protected static $trimMerchantDataRules = [
        'merchant_ids'   => 'required|sequential_array',
        'merchant_ids.*' => 'required|alpha_num|size:14',
    ];


    protected static $editConfigValidators = [
        'csv_email',
    ];

    protected static $editValidators = [
        'csv_email',
    ];

    protected static $featureValidators = [
        'visible_and_editable_features',
        'mode_for_product_features',
    ];

    protected static $editEmailValidators = [
        'is_test_account',
    ];

    protected static $keyAccessValidators = [
        'key_access',
    ];

    protected static $listSubmerchantsRules = [
        Entity::NAME                      => 'sometimes|string',
        Entity::ID                        => 'sometimes|alpha_num|size:14',
        Entity::EMAIL                     => 'sometimes|email',
        Constants::APPLICATION_ID         => 'sometimes|string|size:14',
        Detail\Entity::ACTIVATION_STATUS  => 'sometimes|string|max:30',
        Constants::FROM                   => 'integer',
        Constants::TO                     => 'integer',
        Constants::COUNT                  => 'integer|min:1|max:50',
        Constants::SKIP                   => 'integer',
        ENTITY::PRODUCT                   => 'sometimes|in:primary,banking',

        Entity::MERCHANT_ID               => 'sometimes|array',
        MerchantApplications\Entity::TYPE => 'sometimes|string|in:managed,referred,oauth',
    ];

    protected static $partnerSubmerchantMapRules = [
        'partner_type'              => 'required|string',
        'submerchant_id'            => 'required|string',
        'partner_merchant_id'       => 'required|string',
    ];

    protected static $merchantPartnerStatusRules = [
        'email' => 'required|email',
    ];


    protected static $bulkSyncBalanceRules = [
        Constants::INTERVAL => 'sometimes|integer|min:15|max:120'
    ];

    protected static $submitSupportCallRequestRules = [
        'contact' => 'required|contact_syntax',
    ];

    protected static $toggleInternationalRules = [
        Entity::INTERNATIONAL => 'required|boolean'
    ];

    protected static $onboardMerchantInputRules = [
        Terminal\Entity::GATEWAY               => 'required|in:hitachi,paysecure,fulcrum',
        'gateway_input'                        => 'sometimes',
        Terminal\Entity::GATEWAY_ACQUIRER      => 'sometimes',
        'currency_code'                        => 'sometimes',
    ];

    protected static $onboardMerchantInputHitachiRules = [
        Terminal\Entity::GATEWAY               => 'required|in:hitachi',
        'gateway_input'                        => 'required',
    ];

    protected static $onboardMerchantInputPaysecureRules = [
        Terminal\Entity::GATEWAY               => 'required|in:paysecure',
        Terminal\Entity::GATEWAY_ACQUIRER      => 'required|in:axis',
    ];

    protected static $onboardMerchantInputFulcrumRules = [
        Terminal\Entity::GATEWAY               => 'required|in:fulcrum',
        'currency_code'                        => 'required',
    ];

    protected static $restrictSettingsMerchantRules = [
        Entity::MERCHANT_ID => 'required|alpha_num|size:14',
        Entity::ACTION      => 'required|in:add,remove',
    ];

    protected static $bulkSubmerchantAssignRules = [
        'idempotency_key'   => 'required',
        'submerchant_id'    => 'required|alpha_num|size:14',
        'terminal_id'       => 'required|alpha_num|size:14',
    ];

    protected static $suspendedMerchantRemoveRules = [
        'skip'  => 'sometimes|integer',
        'limit' => 'sometimes|integer',
    ];

    protected static $updatePartnerIntentRules = [
        Constants::PARTNER_INTENT       => 'required|boolean',
    ];

    protected static $updatePartnerTypeRules = [
        Entity::PARTNER_TYPE    => 'required|string|custom:partner_type_for_update',
    ];

    protected static $preferencesRules = [
        'contact_id'  => 'filled|public_id',
    ];

    protected static $personalisationRules = [
        'contact_id'  => 'filled|public_id',
    ];

    protected static $holidayNotifyRules = [
        'lists'   => 'required|string',
        'action'  => 'required|string',
    ];

    protected static $entityBatchActionRules = [
        Constants::BATCH_ACTION  => 'required|string|custom',
        Constants::ENTITY        => 'required|string|custom',
        Constants::IDEMPOTENT_ID => 'required',
        Entity::ID               => 'required|alpha_num|size:14',
    ];

    protected static $requestInternationalProductRules = [
        'products' => 'required|array|filled',
    ];

    protected static $accessMapBatchRules = [
        Constants::BATCH_ACTION  => 'required|string|custom',
        Constants::ENTITY        => 'required|string|custom',
        Constants::IDEMPOTENT_ID => 'required',
    ];

    protected static $codeRules = [
        Entity::CODE            => 'sometimes|string|min:3|max:20|regex:"^([0-9A-Za-z-._])+$"',
    ];

    protected static $completeSubmerchantOnboardingRules = [
        'submerchant_id'            => 'required|string',
        'partner_merchant_id'       => 'required|string',
    ];

    protected static $toggleFeeBearerRules = [
        Entity::FEE_BEARER      => 'required|string|in:platform,customer|custom:toggle_fee_bearer',
    ];

    protected static $transactionLimitSelfServeRules = [
        Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT      => 'required|integer|min:1',
        Constants::TRANSACTION_LIMIT_INCREASE_REASON      => 'required|string|min:100',
        Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL => 'sometimes|file|mimes:pdf,jpeg,jpg,png,zip',
    ];

    protected static $fetchCouponsRequestRules = [
        'order_id'                      => 'required|string',
        'contact'                       => 'sometimes|string',
        'email'                         => 'sometimes|email',
    ];

    protected static $fetchCouponsResponseRules = [
        'code'                          => 'required|string',
        'summary'                       => 'required|string',
        'description'                   => 'sometimes|string',
        'tnc'                           => 'sometimes|array',
    ];

    protected static $applyCouponRequestRules = [
        'order_id'                      => 'required|string',
        'contact'                       => 'sometimes|string',
        'email'                         => 'sometimes|email',
        'code'                          => 'required|string',
    ];

    protected static $applyCouponResponseRules = [
        'promotion'                     => 'required|array',
        'promotion.reference_id'        => 'required|string',
        'promotion.type'                => 'sometimes|string',
        'promotion.code'                => 'required|string',
        'promotion.value'               => 'required|integer',
        'promotion.value_type'          => 'sometimes|string',
        'promotion.description'         => 'sometimes|string',
        'amount'                        => 'sometimes|integer',
        'line_items'                    => 'sometimes|array',
        'line_items.*.sku'              => 'exclude_if:line_items,null|string',
        'line_items.*.variant_id'       => 'exclude_if:line_items,null|string',
        'line_items.*.price'            => 'exclude_if:line_items,null|integer',
        'line_items.*.offer_price'      => 'exclude_if:line_items,null|integer',
        'line_items.*.tax_amount'       => 'exclude_if:line_items,null|integer',
        'shipping_fee'                  => 'sometimes|integer',
        'cod_fee'                       => 'sometimes|integer',
        'line_items_total'              => 'sometimes|integer'
    ];

    protected static $applyCouponInvalidRequestResponseRules = [
        'failure_reason'                => 'sometimes|string',
        'failure_code'                  => 'required|string',
    ];

    protected static $couponCodeUrlUpdateRequestRules = [
        'url'                           => 'required|active_url'
    ];

    protected static $shippingInfoRequestRules = [
        Address\Entity::ZIPCODE => 'required|string|between:2,10',
        Address\Entity::COUNTRY => 'sometimes|string|between:2,64|custom',
    ];

    protected static $addressShippingInfoResponseRules = [
        'id'                           => 'required|integer',
        'serviceable'                  => 'required|boolean',
        'cod'                          => 'required|boolean',
        'cod_fee'                      => 'sometimes|integer|nullable',
        'shipping_fee'                 => 'sometimes|integer|nullable',
    ];

    protected static $serviceabilityUrlUpdateRequestRules = [
        'url'                          => 'required|url'
    ];

    protected static $updateSlabRequestRules = [
        'amount' => 'required|integer',
        'fee'    => 'required|integer',
    ];

    public function validateMerchantForProductInternational(Entity $merchant)
    {
        $merchant = $merchant?: $this->entity;

        $internationalActivationFlow  = $merchant->merchantDetail->getInternationalActivationFlow();

        $activationFlow = $merchant->merchantDetail->getActivationFlow();

        if (($internationalActivationFlow === InternationalActivationFlow::BLACKLIST) or
            ( $activationFlow ===  ActivationFlow::BLACKLIST ))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Blacklisted flow');
        }
    }


    protected function validateIsTestAccount(array $input)
    {
        $merchant = $this->entity;

        $isTestAccount = (new Account)->isTestAccount($merchant->getId());

        if ($isTestAccount === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_OPERATION_NOT_ALLOWED_FOR_TEST_ACCOUNT);
        }

    }

    /**
     * Throws an exception if a merchant tries to enable an uneditable
     * feature for live mode, or via the should_sync flag
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    public function validateModeForProductFeatures(array $input)
    {
        $requestedFeatures = array_keys($input['features']);

        $shouldSync = (bool) ($input[Feature\Entity::SHOULD_SYNC] ?? false);

        $uneditableFeatures = array_values(array_intersect($requestedFeatures,
            Feature\Constants::PRODUCT_FEATURES));

        if ((count($uneditableFeatures) > 0) and (
            ($this->isLiveMode() === true) or
            ($shouldSync === true)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE,
                Feature\Entity::NAMES,
                ['features' => $uneditableFeatures, 'should_sync' => $shouldSync]);
        }
    }

    protected function validateChannel($attribute, $channel)
    {
        Settlement\Channel::validate($channel);
    }

    public function validateKeyAccess(array $input)
    {
        $merchant = $this->entity;

        if (empty($input[Entity::HAS_KEY_ACCESS]) === true)
        {
            return;
        }

        if (empty($merchant->merchantDetail->getWebsite()) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Key access cannot be granted with out website details');
        }
    }

    public function validateUserDoesNotBelongToMerchantsInMultipleOrgsForEmailUpdate($user)
    {
        $app = App::getFacadeRoot();

        $merchantOrgIdsForUser = array_unique($user->merchants()->get()->pluck('org_id')->toArray());

        $numberOfOrgIdsForUser = sizeof($merchantOrgIdsForUser);

        $orgId = $app['basicauth']->getMerchant()->getOrgId();

        // if user has no merchant or user has merchant[s] belongs to requested org
        if (($numberOfOrgIdsForUser === 0) or
            (($numberOfOrgIdsForUser === 1) and ($merchantOrgIdsForUser[0] === $orgId)))
        {
            return;
        }

        throw new Exception\BadRequestValidationFailureException(
            'We are unable to change your email Id to ' . $user->getEmail() . '. Please reach out to our support team to perform this action');

    }

    protected function validateHandle($attribute, $handle)
    {
        if ($handle !== null)
        {
            if ($handle !== strtoupper($handle))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_HANDLE_UPPERCASE_ONLY);
            }
        }
    }

    public function validateLogo($imageDetails)
    {
        $fileSize = $imageDetails['size'];
        $width    = $imageDetails['width'];
        $height   = $imageDetails['height'];

        // File size should not be more than 1M.
        if ($fileSize > self::MAXIMAGESIZE)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_BIG);
        }

        // The image should be square
        if ($width !== $height)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_SQUARE
            );
        }

        // The minimum dimensions should be 256*256
        if ($width < 256)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_SMALL
            );
        }
    }

    public function validateImage($mimeType, $extension)
    {
        $acceptedMimeArray = self::EXTENSIONMIMEMAP;

        // Checks if extension is defined in the array and if the extension and mime type match.
        if ((!isset($acceptedMimeArray[$extension])) or
            ($acceptedMimeArray[$extension] !== $mimeType))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_IMAGE
            );
        }
    }

    /**
     * Validates if billing label value has similarity with business
     * or website name or value belongs from suggestion
     * It uses fuzzy logic to check similarity
     * Threshold for website similarity is 80%
     * Threshold for business name similarity is 80%
     * @param $billingLabel : billing label value which need to be validated
     * @param $attribute
     * @return void
     * @throws BadRequestValidationFailureException
     */
    public function validateBillingLabel($attribute, $billingLabel)
    {
        $merchant = $this->entity;

        $suggestions = (new core())->getBillingLabelSuggestions($merchant);

        $billingLabel = (new core())->preProcessStringForBillingLabelUpdate($billingLabel);

        $businessName = $merchant->merchantDetail->getBusinessName();

        $website = $merchant->merchantDetail->getWebsite();

        $traceData = [
            self::NEW_BILLING_LABEL => $billingLabel,
            self::OLD_BILLING_LABEL => $merchant->getBillingLabel(),
            self::IS_FROM_SUGGESTIONS => false
        ];

        if (in_array($billingLabel, $suggestions, true) === true)
        {
            $traceData[self::IS_FROM_SUGGESTIONS] = true;
        }

        $similarityWithWebsite = $this -> getSimilarityWithWebsiteForBillingLabelUpdate(
            $billingLabel,
            $merchant);

        $traceData[self::SIMILARITY_WITH_WEBSITE] = $similarityWithWebsite;

        $traceData[Detail\Entity::BUSINESS_WEBSITE] = $website;

        $similarityWithBusinessName = $this -> getSimilarityWithBusinessNameForBillingLabelUpdate(
            $billingLabel,
            $merchant);

        $traceData[self::SIMILARITY_WITH_BUSINESS_NAME] = $similarityWithBusinessName;

        $traceData[Detail\Entity::BUSINESS_NAME] = $businessName;

        if ((in_array($billingLabel, $suggestions, true) === true) or
            ($similarityWithWebsite >= self::THRESHOLD_FOR_WEBSITE_SIMILARITY) or
            ($similarityWithBusinessName >= self::THRESHOLD_FOR_BUSINESS_NAME_SIMILARITY))
        {
            $traceData[self::VALIDATION_STATUS] = self::PASSED;

            $this->getTrace()->info(
                TraceCode::MERCHANT_BILLING_LABEL_UPDATE_VALIDATION,
                $traceData
            );

            return;
        }

        $traceData[self::VALIDATION_STATUS] = self::FAILED;

        $this->getTrace()->info(
            TraceCode::MERCHANT_BILLING_LABEL_UPDATE_VALIDATION,
            $traceData
        );

        throw new Exception\BadRequestValidationFailureException(
            self::BILLING_LABEL_INVALID_MESSAGE . ". website: " . $website . ", business name: " . $businessName);
    }

    /**
     * Gives similarity of a string with website/domain name
     * website url should be a valid url
     * It uses fuzzy logic to check similarity
     * @param $value string
     * @param $merchant \RZP\Models\Merchant\Entity
     * @return int max of (fuzzy ratio, token_sort_ratio)
     */
    protected function getSimilarityWithWebsiteForBillingLabelUpdate($value, $merchant): int
    {
        $websiteUrl = $merchant->merchantDetail->getWebsite();

        $merchantCore = new core();

        if ((isset($websiteUrl) === false) or
            ($merchantCore->isValidSchemeAndHostForBillingLabelUpdate($websiteUrl) === false))
        {
            return 0;
        }

        $websiteUrl = (new core())->preProcessStringForBillingLabelUpdate($websiteUrl);

        $host = parse_url($websiteUrl, PHP_URL_HOST);

        $extractedDomains = (new TLDExtract())->extract($host);

        if(count($extractedDomains) >= 2)
        {
            $hostWithoutTld = $extractedDomains[0];

            // divide in subdomain and second level domain
            $hostParts = explode('.', $hostWithoutTld);

            // take second level domain(just below top level domain) as website name
            $websiteName = $hostParts[count($hostParts)-1];

            return $this->getSimilarityOfStringsForBillingLabelUpdate($websiteName, $value);


        }

        return 0;
    }

    /**
     * Gives similarity of a string with business name
     * It uses fuzzy logic to check similarity
     * @param $newBillingLabel
     * @param $merchant
     * @return int  max of (fuzzy ratio, token_sort_ratio)
     */
    protected function getSimilarityWithBusinessNameForBillingLabelUpdate($newBillingLabel, $merchant): int
    {
        $businessName = $merchant->merchantDetail->getBusinessName();

        if(isset($businessName) === false)
        {
            return 0;
        }

        $merchantCore = new core();

        $businessName =  $merchantCore->preProcessStringForBillingLabelUpdate($businessName);

        $businessName =  $merchantCore->removeBusinessTypesForBillingLabelUpdate($businessName);

        $newBillingLabel = $merchantCore->removeBusinessTypesForBillingLabelUpdate($newBillingLabel);

        return $this->getSimilarityOfStringsForBillingLabelUpdate($businessName, $newBillingLabel);
    }

    /**
     * Gives similarity percentage of two strings
     * uses fuzzy logic to check similarity
     * @param $string1
     * @param $string2
     * @return int percentage similarity between strings {max of (fuzzy ratio, token_sort_ratio)}
     */
    protected function getSimilarityOfStringsForBillingLabelUpdate($string1, $string2) : int
    {
        $fuzz = new Fuzz();

        $percentageFromRatio = $fuzz->ratio($string1, $string2);

        $percentageFromTokenSort = $fuzz->tokenSortRatio($string1, $string2);

        return max($percentageFromRatio, $percentageFromTokenSort);
    }

    public function validateEditEmailNotSameAsCurrent($attribute, $email)
    {
        $app = App::getFacadeRoot();

        $currentEmail = $app['basicauth']->getUser()->getEmail();

        if ($currentEmail === $email)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::EMAIL_UPDATE_SAME_AS_CURRENT_VALIDATION_FAILURE_MESSAGE
            );
        }
    }

    public function validateCategory2($attribute, $value)
    {
        $category = $value;

        if (Terminal\Category::isMerchantCategoryValid($category) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Category : '.$category.' invalid for merchant',
                Entity::CATEGORY2
            );
        }
    }

    public function validateMerchantForMarketplaceTransfer($account, $mode)
    {
        if (($account === null) or
            ($account->isLinkedAccount() === false) or
            ($account->getParentId() !== $this->entity->getId()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_INVALID_ACCOUNT_ID,
                'transfers.account'
            );
        }

        if (($mode === Mode::LIVE) and
            ($account->isActivated() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSFER_ACCOUNT_NOT_ACTIVATED
            );
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validatePartnerWithSettingsAccess(Entity $merchant)
    {
        if ($merchant->isPartnerWithSettingsAccess() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNonPurePlatformPartner(Entity $merchant)
    {
        // Block non partners and pure platforms
        if ($merchant->isNonPurePlatformPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }

    /**
     * @param $email
     * @param $orgId
     *
     * @throws Exception\BadRequestException
     */
    public function validateMerchantEmailUnique($email, $orgId)
    {
        $merchants = app('repo')->merchant->fetchByEmailAndOrgId(mb_strtolower($email), $orgId);

        if ($merchants->count() > 0)
        {
            // throw exception if merchant by that email already exists
            $description = PublicErrorDescription::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS . $merchants->pluck(Entity::ID)->first();

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_ALREADY_EXISTS,
                Entity::EMAIL,
                $merchants->pluck(Entity::ID)->toArray(),
                $description
            );
        }
    }

    public function validateAdminPermissionForAction($action)
    {
        if (array_key_exists($action, self::ACTION_PERMISSION_MAP_FOR_MERCHANT_EDIT_BULK) === true)
        {
            $app = App::getFacadeRoot();

            $admin = $app['basicauth']->getAdmin();

            // Check for admin permissions
            $admin->hasPermissionOrFail(self::ACTION_PERMISSION_MAP_FOR_MERCHANT_EDIT_BULK[$action]);
        }
    }

    /**
     * if the contructive action is being performed by non-risk l3 on a merchant tagged by risk ops,
     * then the validator should throw validation exception
     * @param $merchant
     * @param $action
     */
    public function validateRiskPermissionForAction($merchant, $action, $admin = null)
    {
        //if the action is constructive action
        if(in_array($action, Constants::RISK_CONSTRUCTIVE_ACTION_LIST) === false)
        {
            return;
        }

        $tags = $merchant->tagNames();

        $taggedByRiskOps = false;

        $riskTags= explode(',', RiskActionConstants::RISK_TAGS_CSV);

        //Check if the merchant is tagged by Risk team
        foreach ($tags as $tag)
        {
            if (in_array(strtolower($tag), $riskTags) === true)
            {
                $taggedByRiskOps = true;

                break;
            }
        }

        //if the merchant is not tagged, no further check required
        if ($taggedByRiskOps === false)
        {

            return;
        }

        //if the merchant is tagged, we need check the permission
        $app = App::getFacadeRoot();

        if (isset($admin) === false)
        {
            $admin = $app['basicauth']->getAdmin();
        }

        $adminPermissions = $admin->getPermissionsList();

        if (in_array(Permission::MERCHANT_RISK_CONSTRUCTIVE_ACTION, $adminPermissions, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Merchant is tagged by risk team hence constructive action can be performed on this only by risk team');
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsPurePlatformPartner(Entity $merchant)
    {
        // Block non partners and non pure-platforms
        if ($merchant->isPurePlatformPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }

    /**
     * Submerchant creation without providing an email explicitly is only allowed if
     * 1. The partner is of type fully-managed
     * 2. The partner is of type aggregator and has the feature allowing optional emails
     * 3. The merchant is not a partner but has aggregator feature (for backward compatibility)
     *
     * @param  array $input
     * @param  bool  $linkedAccount
     *
     * @throws Exception\BadRequestException
     */
    public function validateSubMerchantInput(array $input, bool $linkedAccount)
    {
        if (empty($input['email']) === true)
        {
            $this->validateEmptyEmailFlow($input, $linkedAccount);
        }
        else
        {
            $this->validateInput('unique_email', array_only($input, 'email'));
        }

        $this->validateInput('edit_name', array_only($input, 'name'));
    }

    protected function validateEmptyEmailFlow(array $input, bool $linkedAccount)
    {
        /** @var Entity $merchant */
        $merchant = $this->entity;

        //
        // Allow empty email if
        // 1. Marketplace is making linked account create request
        // 2. Else, a. Is a partner that is allowed optional email
        //          b. Is not a partner but has aggregator feature.
        //
        if (($merchant->isMarketplace() and $linkedAccount) === true)
        {
            return;
        }

        if ($merchant->isPartner() === true)
        {
            if (($merchant->isFullyManagedPartner() === false) and
                ($merchant->isOptionalEmailAllowedAggregator() === false))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED);
            }
        }
        else
        {
            if ($merchant->hasAggregatorFeature() === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SUBMERCHANT_WITHOUT_EMAIL_NOT_ALLOWED);
            }
        }
    }

    protected function validateCsvEmail($input)
    {
        if (empty($input[Entity::TRANSACTION_REPORT_EMAIL]) === true)
        {
            return;
        }

        $emails = $input[Entity::TRANSACTION_REPORT_EMAIL];

        foreach ($emails as $email)
        {
            $email = trim($email); // Remove whitespace
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "The provided transaction report email is invalid: $email",
                    Entity::TRANSACTION_REPORT_EMAIL
                );
            }
        }
    }

    public function validateBeforeActivate()
    {
        $merchant = $this->entity;

        $detailValidator = $merchant->merchantDetail->getValidator();

        $detailValidator->validateActivationFormSubmitted();

        $this->validateIsNotActivated($merchant);

        $detailValidator->validateIsNotArchived();

        // Don't validate these rest of the attributes for Marketplace accounts
        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $this->validateActivationMandatoryAttributes();
    }

    /**
     * @param array $attributes
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateMandatoryAttributes(array $attributes)
    {
        $merchant = $this->entity;

        $website = $merchant->merchantDetail->getWebsite();

        // If the merchant has submitted activation form with website/app data
        // i.e merchant will have access to keys.
        // or if website is not null [this check to be removed later]
        if (($merchant->getHasKeyAccess() === true) or
            (empty($website) === false))
        {
            $attributes[] = Entity::WEBSITE;
        }

        foreach ($attributes as $attribute)
        {
            $value = $merchant->getAttribute($attribute);

            if (empty($value) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Please set value for attribute: ' . $attribute);
            }
        }
    }

    public function validateActivationMandatoryAttributes()
    {
        $attributes = Constants::ACTIVATION_MANDATORY_FIELDS;

        $this->validateMandatoryAttributes($attributes);
    }

    public function validateInstantActivationMandatoryAttributes()
    {
        $attributes = Constants::INSTANT_ACTIVATION_MANDATORY_FIELDS;

        $this->validateMandatoryAttributes($attributes);
    }

    public function validateBeforeInstantlyActivate()
    {
        $merchant = $this->entity;

        $detailValidator = $merchant->merchantDetail->getValidator();

        $this->validateIsNotActivated($merchant);

        $detailValidator->validateIsNotArchived();

        // LA's should directly be activated. They should not go through the instant activations flow
        if ($merchant->isLinkedAccount() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_INSTANTLY_ACTIVATED,
                Entity::PARENT_ID,
                [
                    Entity::PARENT_ID => $merchant->getParentId(),
                ]);
        }

        $this->validateInstantActivationMandatoryAttributes();
    }

    /**
     * Ensures that the merchant feature requested is a visible feature and an editable feature.
     *
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    public function validateVisibleAndEditableFeatures(array $input)
    {
        $featureNames = array_keys($input['features']);

        $visibleFeatures = array_keys(Feature\Constants::$visibleFeaturesMap);
        $editableFeature = Feature\Constants::$merchantEditableFeatures;
        $featureDependency = array_keys(Feature\Constants::$featureDependencyMap);

        foreach ($featureNames as $feature)
        {
            // Feature must be a "visible feature" and editable by the merchant
            if ((in_array($feature, $visibleFeatures, true) === false) or
                (in_array($feature, $editableFeature, true) === false))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
                    'feature',
                    [$feature]);
            }
            // Only Merchant who have feature ES_ON_DEMAND enabled can change ES features
            else if (($input['es_enabled'] === false) and
                    ($feature === Feature\Constants::ES_AUTOMATIC))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
                    'feature',
                    [$feature]);
            }
            else
            {
                if (in_array($feature, $featureDependency, true) == true)
                {
                    $requiredFeatures = Feature\Constants::$featureDependencyMap[$feature];
                    foreach ($requiredFeatures as $requiredFeature)
                    {
                        if ($this->entity->isFeatureEnabled($requiredFeature) === false)
                        {
                            throw new Exception\BadRequestException(
                                ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
                                'feature',
                                [$feature]);
                        }
                    }
                }
            }
        }
    }

    protected function validateAutoRefundDelay($attribute, $autoRefundDelayPeriod)
    {
        if ($autoRefundDelayPeriod === null)
        {
            return;
        }

        $autoRefundDelay = explode(' ', $autoRefundDelayPeriod);

        $min = $max = null;
        $time = $autoRefundDelay[0];
        $duration = $autoRefundDelay[1];

        if (filter_var($time, FILTER_VALIDATE_INT) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Auto refund delay time period should be an integer', $attribute, $time);
        }

        switch ($duration)
        {
            case 'mins':
                $min = (int) (Entity::MIN_AUTO_REFUND_DELAY / 60);
                $max = (int) (Entity::MAX_AUTO_REFUND_DELAY / 60);
                break;

            case 'hours':
                $min = (int) (ceil(Entity::MIN_AUTO_REFUND_DELAY / 3600));
                $max = (int) (ceil(Entity::MAX_AUTO_REFUND_DELAY / 3600));
                break;

            case 'days':
                $min = (int) (ceil(Entity::MIN_AUTO_REFUND_DELAY / 86400));
                $max = (int) (ceil(Entity::MAX_AUTO_REFUND_DELAY / 86400));
                break;

            default:
                throw new Exception\BadRequestValidationFailureException(
                    'Auto refund delay should be in mins, hours or days', $attribute, $duration);
        }

        if (($time < $min) or ($time > $max))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Auto refund delay should be between ' . $min . ' and ' . $max . ' ' . $duration);
        }
    }

    protected function validateMerchantDetailExists($merchant)
    {
        $merchantDetails = $merchant->merchantDetail;

        if ($merchantDetails === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_DOES_NOT_EXISTS);
        }
    }

    public function validateBatchAction($attribute, $BatchAction)
    {
        if (BatchAction::exists($BatchAction) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_ACTION_NOT_SUPPORTED);
        }
    }

    public function validateEntity($attribute, $BatchActionEntity)
    {
        if (BatchActionEntity::exists($BatchActionEntity) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BATCH_ACTION_ENTITY_NOT_SUPPORTED);
        }
    }

    public function validateAction($attribute, $action)
    {
        if (Action::exists($action) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED);
        }

        $validator = 'validate' .studly_case($action);

        if (method_exists($this, $validator))
        {
            $this->$validator();
        }
    }

    protected function validateArchive()
    {
        $merchant = $this->entity;

        if ($merchant->isArchived() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ARCHIVED);
        }

        $this->validateMerchantDetailExists($merchant);
    }

    protected function validateUnarchive()
    {
        $merchant = $this->entity;

        if ($merchant->isArchived() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ARCHIVED);
        }
    }

    public function validateSuspend()
    {
        $merchant = $this->entity;

        if ($merchant->isSuspended() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED);
        }
    }

    public function validateUnsuspend()
    {
        $merchant = $this->entity;

        if ($merchant->isSuspended() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_SUSPENDED);
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateLiveDisable()
    {
        $merchant = $this->entity;

        $this->validateIsActivated($merchant);
        $this->validateSuspend();

        if ($merchant->isLive() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE);
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateLiveEnable()
    {
        $merchant = $this->entity;

        $this->validateIsActivated($merchant);
        $this->validateSuspend();

        if ($merchant->isLive() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_LIVE);
        }
    }

    protected function validateSetReceiptEmailEventAuthorized()
    {
        $merchant = $this->entity;

        if ($merchant->getReceiptEmailTriggerEvent() === Event::AUTHORIZED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_TRIGGER_EVENT_ALREADY_AUTHORISED);
        }
    }

    protected function validateSetReceiptEmailEventCaptured()
    {
        $merchant = $this->entity;

        if ($merchant->getReceiptEmailTriggerEvent() === Event::CAPTURED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_TRIGGER_EVENT_ALREADY_CAPTURED);
        }
    }

    public function validateHoldFunds()
    {
        $merchant = $this->entity;

        if ($merchant->getHoldFunds() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ALREADY_ON_HOLD);
        }
    }

    public function validateReleaseFunds()
    {
        $merchant = $this->entity;

        if ($merchant->getHoldFunds() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ALREADY_RELEASED);
        }

        $this->validateHasBankAccount();
    }

    protected function validateEnableReceiptEmails()
    {
        $merchant = $this->entity;

        if ($merchant->isReceiptEmailsEnabled() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_RECEIPT_EMAILS_ALREADY_ENABLED);
        }
    }

    protected function validateDisableReceiptEmails()
    {
        $merchant = $this->entity;

        if ($merchant->isReceiptEmailsEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_RECEIPT_EMAILS_ALREADY_DISABLED);
        }
    }

    public function validateEnableInternational()
    {
        $merchant = $this->entity;

        $productInternational = new ProductInternational\ProductInternationalField($merchant);

        if ($merchant->isInternational() === true and
            $merchant->getProductInternational() === $productInternational->getEnabledValueForLiveProducts())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ALREADY_ENABLED);
        }

        $plan = app('repo')->pricing->getPricingPlanByIdWithoutOrgId($merchant->getPricingPlanId());

        if ($plan->hasInternationalPricing() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Pricing not present for international.');
        }

        //to check eligibilty of merchant to be internationally enabled
        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);
        (new Core)->shouldActivateProductInternational($merchant, $merchantDetails);
    }

    public function validateEnableProductInternational($internationalProducts)
    {
        if (empty($internationalProducts) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRODUCT_INTERNATIONAL_REQUIRED,
                null,
                ['products' => $internationalProducts]);
        }
    }

    public function validateDisableInternational()
    {
        $merchant = $this->entity;

        $productInternational = new ProductInternationalField($merchant);

        if ($merchant->isInternational() === false and
        $merchant->getProductInternational() === $productInternational->getDisabledValueForLiveProducts())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ALREADY_DISABLED);
        }
    }

    /**
     * Throw an error if the merchant is already a partner
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIfAlreadyPartner(Entity $merchant)
    {
        if ($merchant->isPartner() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_IS_ALREADY_PARTNER);
        }
    }

    /**
     * Throw an error if the merchant is not a partner
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsPartner(Entity $merchant)
    {
        if ($merchant->isPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
                Entity::PARTNER_TYPE,
                [
                    Entity::ID           => $merchant->getId(),
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]);
        }
    }

    /**
     * Validates merchant is partner.
     * Throws an error if the merchant is not a partner or if merchant is a reseller partner
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNotResellerPartner(Entity $merchant)
    {
        $this->validateIsPartner($merchant);

        if ($merchant->isResellerPartner() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_NOT_ALLOWED_FOR_RESELLER,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]
            );
        }
    }

    /**
     * Validates that the merchant is a partner and an aggregator
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsAggregatorPartner(Entity $merchant)
    {
        $this->validateIsPartner($merchant);

        if ($merchant->isAggregatorPartner() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                ]
            );
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNotLinkedAccount(Entity $merchant)
    {
        if ($merchant->isLinkedAccount() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_PARTNER);

        }
    }

    public function validatePartnerIsNotSubmerchant(Entity $partner, Entity $submerchant)
    {
        if ($submerchant->getId() === $partner->getId())
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PARTNER_CANNOT_BE_SUBMERCHANT_TO_ITSELF);
        }
    }

    public function validatePartnerType(string $partnerType)
    {
        if (in_array($partnerType, Constants::$partnerTypes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_TYPE_INVALID,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_TYPE => $partnerType,
                ]);
        }
    }

    public function validateLinkedAccount(Entity $merchant)
    {
        if ($merchant->isLinkedAccount() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCOUNT_IS_NOT_LINKED_ACCOUNT);
        }
    }

    /**
     * Validate if the input application is
     *
     * @param string $inputAppId
     * @param array  $partnerAppIds
     *
     * @throws Exception\BadRequestException
     */
    public function validatePartnerApplicationId(string $inputAppId, array $partnerAppIds)
    {
        if (in_array($inputAppId, $partnerAppIds, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_APPLICATION_ID,
                Constants::APPLICATION_ID,
                [
                    'partner_app_ids'         => $partnerAppIds,
                    Constants::APPLICATION_ID => $inputAppId,
                ]);
        }
    }

    public function validateLinkedAccountDashboardAccess(bool $dashboardAccess, Entity $merchant)
    {
        $merchantUsersCount = $merchant->users()->count();

        if ($dashboardAccess === true and $merchantUsersCount > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_DASHBOARD_ACCESS_ALREADY_GIVEN);
        }

        if ($dashboardAccess === false and $merchantUsersCount === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_LINKED_ACCOUNT_DASHBOARD_USERS);
        }
    }

    public function validateUserIsOwnerForMerchant($userId, $merchantId)
    {
        $userMapping = app('repo')->merchant->getMerchantUserMapping($merchantId, $userId);

        $userRoleForMerchant = $userMapping->pivot->role;

        if ($userRoleForMerchant !== Role::OWNER)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }
    }

    public function validateLinkedAccountReversals(bool $allowReversals, Entity $merchant)
    {
        $merchantUsersCount = $merchant->users()->count();

        $canReverse = $merchant->isFeatureEnabled(Feature\Constants::ALLOW_REVERSALS_FROM_LA);

        if ($merchantUsersCount === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_LINKED_ACCOUNT_DASHBOARD_USERS);
        }

        if (($allowReversals === true) and ($canReverse === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_GIVEN);
        }

        if (($allowReversals === false) and ($canReverse === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_REVERSAL_ABILITY_ALREADY_REMOVED);
        }
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsNotActivated(Entity $merchant)
    {
        if ($merchant->isActivated() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED,
                Entity::ACTIVATED);
        }
    }

    public function validateBeforeKycVerified()
    {
        $merchant = $this->entity;

        $detailValidator = $merchant->merchantDetail->getValidator();

        $detailValidator->validateActivationFormSubmitted();

        $this->validateIsActivated($merchant);

        $detailValidator->validateIsNotArchived();
    }

    /**
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateIsActivated(Entity $merchant)
    {
        if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED,
                Entity::ACTIVATED);
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateHasBankAccount()
    {
        $merchant = $this->entity;

        $bankAccount = $merchant->bankAccount;

        if ($bankAccount === null)
        {
            // check partner bank account exists
            $partner = (new Core)->getSettledToPartnersTypeOfMerchantIfExists($merchant);

            $partnerbankAccountExits = (new Core)->isValidBankAccountForSettledToPartner($merchant, $partner);

            if ($partnerbankAccountExits === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
            }
        }
    }

    public function validateNowIsWorkingHour()
    {
        if (is_rzp_business_hour() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Now is not a working hour. Please try this request on Mon-Fri between 9 AM - 6 PM.');
        }
    }

    public function validateBusinessBankingActivated()
    {
        $merchant = $this->entity;

        if ($merchant->isBusinessBankingEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FORBIDDEN_BUSINESS_BANKING_NOT_ENABLED,
                null,
                [
                    'merchant_id'      => $merchant->getId(),
                    'merchant_name'    => $merchant->getName(),
                    'business_banking' => $merchant->isBusinessBankingEnabled()
                ]);
        }
    }

    public function validateAndTranslateToAccountNumberForBankingIfApplicable(array & $input)
    {
        $product       = array_get($input, Entity::PRODUCT);
        $accountNumber = array_get($input, Balance\Entity::ACCOUNT_NUMBER);

        if ((empty($product) === true) or
            (empty($accountNumber) === false))
        {
            $this->validateAndTranslateAccountNumberForBanking($input);
        }
    }

    /**
     * There are service methods (list & fetch) for few models which expect
     * mandatory ACCOUNT_NUMBER in query parameter. Such models include
     * transactions, bank_transfers & payouts. This method is called from those
     * service methods to translate ACCOUNT_NUMBER to BALANCE_ID because beyond
     * service layer repository's fetch etc only understands BALANCE_ID.
     *
     * @param array $input
     *
     * @return Balance\Entity
     * @throws Exception\BadRequestException
     */
    public function validateAndTranslateAccountNumberForBanking(array & $input) : Balance\Entity
    {
        $this->validateBusinessBankingActivated();

        // Validates input has valid ACCOUNT_NUMBER.
        (new Base\JitValidator)
            ->rules([Balance\Entity::ACCOUNT_NUMBER => 'required|alpha_num|between:5,22'])
            ->strict(false)
            ->input($input)
            ->validate();

        // Replaces ACCOUNT_NUMBER with corresponding BALANCE_ID.
        $accountNumber = array_pull($input, Balance\Entity::ACCOUNT_NUMBER);

        $merchantId = array_pull($input, Balance\Entity::MERCHANT_ID);

        try
        {
            /** @var Balance\Entity $balance */
            $balance = (new Balance\Repository)->getBalanceByAccountNumberOrFail($accountNumber, $merchantId);

            $input[Balance\Entity::BALANCE_ID] = $balance->getId();

            return $balance;
        }
        catch (\Throwable $ex)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_RAZORPAYX_ACCOUNT_NUMBER_IS_INVALID,
                Balance\Entity::ACCOUNT_NUMBER,
                [
                    'account_number'   => $accountNumber
                ]);
        }
    }

    /**
     * @param  Admin\Entity    $admin
     * @param  Entity          $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateAdminMerchantAccess(Admin\Entity $admin, Entity $merchant)
    {
        if (($admin->canSeeAllMerchants() === true) and ($admin->getOrgId() === $merchant->getOrgId()))
        {
            return;
        }

        if (in_array($merchant->getId(), $admin->merchants()->get()->getIds(), true) === true)
        {
            return;
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
    }

    public function validateBatchId($batchId)
    {
        if (empty($batchId) === true)
        {
            throw new BadRequestValidationFailureException('Batch Id not present');
        }
    }

    /**
     * @param array $input
     * Rate limit on number of submerchant terminal assign in Bulk Route
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateBulkSubmerchantAssignCount(array $input)
    {
        if (count($input) > self::MAX_BULK_SUBMERCHANT_ASSIGN_LIMIT)
        {
            throw new BadRequestValidationFailureException(
                'Current batch size ' . count($input) . ', max limit of ' . self::BULK_SUBMERCHANT_ASSIGN . ' is ' . self::MAX_BULK_SUBMERCHANT_ASSIGN_LIMIT,
                null,
                null
            );
        }
    }

    public function validatePartnerTypeForUpdate($attribute, $value)
    {
        $allowedPartnerTypes = [
            Constants::RESELLER,
            Constants::AGGREGATOR,
            Constants::PURE_PLATFORM,
        ];

        if (in_array($value, $allowedPartnerTypes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_TYPE_INVALID,
                Entity::PARTNER_TYPE,
                [$attribute => $value]);
        }
    }

    public function validateBeforeEnablingInternationalByMerchant($merchant)
    {
        if ($merchant->isInternational() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_INTERNATIONAL);
        }

        $this->validateWebsite($merchant);

        $merchantDetails = $merchant->merchantDetail;

        $internationalActivationFlow = $merchantDetails->getInternationalActivationFlow();

        //
        // @todo We need to remove this check once we implement feature request based international activation process
        //
        if (($internationalActivationFlow !== Detail\ActivationFlow::WHITELIST)
            and ($internationalActivationFlow !== Detail\ActivationFlow::GREYLIST))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_INTERNATIONAL_STATUS_CHANGE_REQUEST,
                Detail\Entity::INTERNATIONAL_ACTIVATION_FLOW,
                [
                    Detail\Entity::INTERNATIONAL_ACTIVATION_FLOW => $internationalActivationFlow
                ]
            );
        }
    }

    /**
     * Validates that merchant has a website
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validateWebsite(Entity $merchant)
    {
        $merchantDetails = $merchant->merchantDetail;

        // Since website is not synced between merchant and merchant_detail,
        // therefore checking for both
        if ((empty($merchant->getWebsite()) === true) and
            (empty($merchantDetails->getWebsite()) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_NOT_SET);
        }
    }

    /**
     * Validates if the productInternational field has values updated for positions which aren't live
     * @param $attribute
     * @param $productInternational
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateProductInternational($attribute, $productInternational)
    {
        $liveProductPos = array_values(ProductInternationalMapper::PRODUCT_POSITION);

        $maxIndexPopulated = max($liveProductPos);

        for ($i = $maxIndexPopulated + 1; $i < (strlen($productInternational) - 1); $i += 1)
        {
            if ($productInternational[$i] !== '0')
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Provided value is not valid');
            }
        }
    }

    public function validateMerchantWorkflowType($type)
    {
        if (array_key_exists($type, Constants::MERCHANT_WORKFLOWS) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_INVALID_WORKFLOW_TYPE);
        }
    }

    /**
     * Validates password for $change2faSetting rules on the basis of a razorx experiment
     * @param $input
     */
    protected function validatePassword($input)
    {
        $app = App::getFacadeRoot();
        $razorx = $app['razorx'];
        $ba = $app['basicauth'];

        $merchantId = $ba->getMerchantId();

        $variant = $razorx->getTreatment(
            $merchantId,
            RazorxTreatment::VALIDATE_USER_2FA_STATUS,
            $ba->getMode());

        if (strtolower($variant) !== 'on')
        {
            if (isset($input[User\Entity::PASSWORD]) === true)
            {
                $inputPassword = $input[User\Entity::PASSWORD];
                $userPassword = $ba->getUser()->getPassword();

                if (Hash::check($inputPassword, $userPassword) === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_INVALID_PASSWORD);
                }

            }
            else
            {
                throw new BadRequestValidationFailureException(
                    'The password field is required');
            }
        }

        return;
    }

    public function validateCode($key, $value)
    {
        $this->validateInput('code', [$key => $value]);
    }

    /**
     * Validates if the product name is either banking or primary
     *
     * @param $product
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateMerchantProduct($product)
    {
        $validProducts = [Product::PRIMARY, Product::BANKING];

        if ((empty($product) === false) and (in_array($product, $validProducts, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INVALID_PRODUCT_NAME);
        }
    }

    public function validateRangeForFailureAnalysis(array $input)
    {
        // max range of query can be 91 days (7862400 seconds)
        if (($input['to'] < $input['from']) or
            (($input['to'] - $input['from']) >= 7862400))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The date range is invalid', null, null );
        }
    }

    /**
     * Validates if only risk attributes
     *
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateRiskAttributes(array $input)
    {
        if(empty($input) === true)
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_MERCHANT_RISK_ATTRIBUTES_REQUIRED);
        }

        foreach(array_keys($input) as $riskAttribute)
        {
            if (in_array($riskAttribute, self::MERCHANT_RISK_ATTRIBUTES) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_MERCHANT_INVALID_RISK_ATTRIBUTE,
                    $riskAttribute);
            }
        }
    }

    /**
     * Validates if risk attributes has diff
     *
     * @param Entity $merchant
     * @param Entity $newMerchant
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateRiskAttributesHasDiff(Entity $merchant, Entity $newMerchant)
    {
        $diff = (new Differ\Core)->createDiff($merchant->toArray(), $newMerchant->toArray());

        if (empty($diff) === true)
        {
            throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_MERCHANT_NO_DIFF_IN_RISK_ATTRIBUTES);
        }
    }

    public function validateToggleFeeBearer($attribute, $feeBearer)
    {
        $merchant = $this->entity;

        $merchantFeeBearer = $merchant->getFeeBearer();

        if ($merchantFeeBearer == $feeBearer)
        {
            throw new Exception\BadRequestValidationFailureException('The new fee bearer is same as the previous fee bearer');
        }
    }

    public function validateIncreaseTransactionLimitConditions(Entity $merchant, array $input, bool $isBusinessRegistered, bool $isMerchantKamOrDirectSales = false)
    {
        $oldLimit = $merchant->getMaxPaymentAmount();

        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);

        $businessCategory = $merchantDetails->getBusinessCategory();

        $this->validateIsActivated($merchant);

        $this->validateInput('transaction_limit_self_serve', $input);

        $this->validateTransactionLimitNotSameAsCurrent($input, $oldLimit);

        $this->validateRequestNotRaisedInLastThirtyDays($merchant);

        if ($isMerchantKamOrDirectSales === false)
        {
            $this->validateNotExceedingMaximumLimit($merchant, $input, $isBusinessRegistered, $businessCategory);

            $this->validateNotUnregiesteredGamingOrGovernmentBusinessCategory($businessCategory, $isBusinessRegistered);

            $this->validateCtsOrFtsLessThanFive($merchant);
        }
    }

    protected function validateTransactionLimitNotSameAsCurrent(array $input, int $oldLimit)
    {
        if($input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT] == $oldLimit)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The new transaction limit is same as the current transaction limit'
            );
        }
    }

    protected function validateRequestNotRaisedInLastThirtyDays(Entity $merchant)
    {
        $workflowType = Constants::INCREASE_TRANSACTION_LIMIT;

        [$entityId, $entity] = (new Core())->fetchWorkflowData($workflowType, $merchant);

        $action = (new ActionCore)->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [Constants::MERCHANT_WORKFLOWS[$workflowType][Constants::PERMISSION]]
        );

        if (empty($action) === false)
        {
            $updatedTime = $action->getAttribute(ActionEntity::UPDATED_AT);

            $currentTime = time();

            $checkTime = strtotime('+30 days', $updatedTime);

            if ($currentTime < $checkTime)
            {
                $updatedDate = date('d-M-Y', $updatedTime);

                $checkDate = date('d-M-Y', $checkTime);

                $description = 'Our partner banks have already evaluated your profile for transaction limit updation on ' . $updatedDate . ', please wait till ' . $checkDate . ' to send another request to our partner banks';

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_EDIT_TRANSACTION_LIMIT_REQUEST_MADE_IN_LAST_30_DAYS,
                    null,
                    [
                        'updatedDate' => $updatedDate,
                        'checkDate' => $checkDate
                    ],
                    $description
                );
            }
        }
    }

    protected function validateNotExceedingMaximumLimit(Entity $merchant, array $input, bool $isBusinessRegistered, $businessCategory)
    {
        $oldLimit = $merchant->getMaxPaymentAmount();

        if ($isBusinessRegistered === true)
        {
            if(array_key_exists($businessCategory,  Constants::registeredMerchantMaximumTransactionLimit) === true)
            {
                $maxCategoryLimit = Constants::registeredMerchantMaximumTransactionLimit[$businessCategory];
            }
            else
            {
                $maxCategoryLimit = Constants::registeredMerchantMaximumTransactionLimit[Detail\BusinessCategory::OTHERS];
            }
        }
        else
        {
            if(array_key_exists($businessCategory,  Constants::unregisteredMerchantMaximumTransactionLimit) === true)
            {
                $maxCategoryLimit = Constants::unregisteredMerchantMaximumTransactionLimit[$businessCategory];
            }
            else
            {
                $maxCategoryLimit = Constants::unregisteredMerchantMaximumTransactionLimit[Detail\BusinessCategory::OTHERS];
            }
        }

        //If a merchant already has maximum category transaction limit set and then if they try to further increase their transaction limit
        //then this message has to be displayed
        //“Your transaction limit cannot be increased any further, as per the guidelines set by our partner banks”
        $this->checkIfEqualToMaximumLimit($oldLimit, $maxCategoryLimit, $input);

        //If the old value set is less than the maximum category transaction limit and tries to increase their transaction limit more than the maximum category transaction limit
        $this->checkIfGreaterThanMaximumLimit($input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT], $maxCategoryLimit);
    }

    protected function checkIfEqualToMaximumLimit(int $oldLimit, int $maxCategoryLimit, array $input)
    {
        if (($oldLimit === $maxCategoryLimit) and
            ($input[Constants::NEW_TRANSACTION_LIMIT_BY_MERCHANT] > $maxCategoryLimit))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Your transaction limit cannot be increased any further, as per the guidelines set by our partner banks'
            );
        }
    }

    protected function checkIfGreaterThanMaximumLimit(int $newLimit, int $maxCategoryLimit)
    {
        if ($newLimit > $maxCategoryLimit)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Enter lower Transaction Limit value'
            );
        }
    }

    protected function validateNotUnregiesteredGamingOrGovernmentBusinessCategory($businessCategory, bool $isBusinessRegistered)
    {
        if (($isBusinessRegistered === false) and
            (($businessCategory === Detail\BusinessCategory::GAMING) or
            ($businessCategory === Detail\BusinessCategory::GOVERNMENT)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCESS_DENIED
            );
        }
    }

    protected function validateCtsOrFtsLessThanFive(Entity $merchant)
    {
        $merchantId = $merchant->getMerchantId();

        $resultArray =(new Core())->getMerchantRiskData($merchantId);

        if(empty($resultArray['domestic_merchant_chargeback_to_sale_ratio_(%)'][0]['lifetime']) === true)
        {
            $this->getTrace()->info(TraceCode::TRANSACTION_LIMIT_CTS_RATIO_NOT_FOUND, [
                Constants::MERCHANT_ID => $merchantId
            ]);
        }

        if(empty($resultArray['domestic_merchant_fraud_to_sale_ratio_(%)'][0]['lifetime']) === true)
        {
            $this->getTrace()->info(TraceCode::TRANSACTION_LIMIT_FTS_RATIO_NOT_FOUND, [
                Constants::MERCHANT_ID => $merchantId
            ]);
        }

        if (((empty($resultArray['domestic_merchant_chargeback_to_sale_ratio_(%)'][0]['lifetime']) === false) and
             ($resultArray['domestic_merchant_chargeback_to_sale_ratio_(%)'][0]['lifetime'] > 5.0)) or
            ((empty($resultArray['domestic_merchant_fraud_to_sale_ratio_(%)'][0]['lifetime']) === false) and
             ($resultArray['domestic_merchant_fraud_to_sale_ratio_(%)'][0]['lifetime'] > 5.0)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EDIT_TRANSACTION_LIMIT_CTS_OR_FTS_MORE_THAN_5
            );
        }
    }

    protected function validateCountry($attribute, $value)
    {
        $isValid = Country::checkIfValidCountry($value);

        if ($isValid === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_COUNTRY, null, [$value]);
        }
    }

    protected static $fetchMerchantsByParamsRules = [
        Detail\Entity::BUSINESS_CATEGORY        => 'sometimes|array',
        Detail\Entity::BUSINESS_SUBCATEGORY     => 'sometimes|string',
        Detail\Entity::BUSINESS_TYPE            => 'sometimes|string',
        Entity::WEBSITE                         => 'sometimes|string',
        Entity::CATEGORY2                       => 'sometimes|string',
        Entity::ORG_ID                          => 'sometimes|string',
        'merchant_ids'                          => 'sometimes|array',
    ];
}
