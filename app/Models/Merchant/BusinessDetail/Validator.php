<?php

namespace RZP\Models\Merchant\BusinessDetail;

use App;
use RZP\Base;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Constants\Timezone;
use RZP\Base\RepositoryManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Admin\Org;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\Detail\Upload as DetailUpload;

class Validator extends Base\Validator
{
    /**
     * Application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    protected $orgId;

    public function __construct($entity = null)
    {
        parent::__construct($entity);

        $this->app = \App::getFacadeRoot();

        $this->orgId = $this->app['basicauth']->getOrgId();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

    }

    protected static $createRules = [
        Entity::MERCHANT_ID                                                   => 'required|string|size:14',
        Entity::WEBSITE_DETAILS                                               => 'sometimes|array',
        Entity::WEBSITE_DETAILS . '.' . Constants::TERMS                      => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::ABOUT                      => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CONTACT                    => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRIVACY                    => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::REFUND                     => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRICING                    => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::LOGIN                      => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CANCELLATION               => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::COMMENTS                   => 'sometimes|string|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PHYSICAL_STORE             => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::SOCIAL_MEDIA               => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_OR_APP             => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::OTHERS                     => 'sometimes|string',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_NOT_READY          => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_COMPLIANCE_CONSENT => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_PRESENT            => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::ANDROID_APP_PRESENT        => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::IOS_APP_PRESENT            => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::OTHERS_PRESENT             => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::WHATSAPP_SMS_EMAIL         => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::SOCIAL_MEDIA_URLS          => 'sometimes|array|nullable',
        Entity::APP_URLS                                                      => 'sometimes|array',
        Entity::APP_URLS.'.'.Constants::PLAYSTORE_URL                         => 'sometimes|custom:active_url|max:255|nullable',
        Entity::APP_URLS.'.'.Constants::APPSTORE_URL                          => 'sometimes|custom:active_url|max:255|nullable',
        Entity::BLACKLISTED_PRODUCTS_CATEGORY                                 => 'sometimes|string|max:255|nullable',
        Entity::BUSINESS_PARENT_CATEGORY                                      => 'sometimes|string|nullable',
        Entity::PLUGIN_DETAILS                                                => 'sometimes|array',
        Entity::LEAD_SCORE_COMPONENTS                                         => 'sometimes|array',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::GSTIN_SCORE          => 'sometimes|numeric|digits_between:1,3|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::DOMAIN_SCORE         => 'sometimes|numeric|digits_between:1,3|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::REGISTERED_YEAR      => 'sometimes|numeric|digits:4|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::AGGREGATED_TURNOVER_SLAB   => 'sometimes|string|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::WEBSITE_VISITS       => 'sometimes|numeric|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::ECOMMERCE_PLUGIN     => 'sometimes|boolean|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::ESTIMATED_ANNUAL_REVENUE   => 'sometimes|string|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::TRAFFIC_RANK         => 'sometimes|string|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::CRUNCHBASE           => 'sometimes|boolean|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::TWITTER_FOLLOWERS    => 'sometimes|numeric|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::LINKEDIN             => 'sometimes|boolean|nullable',
        Entity::ONBOARDING_SOURCE                                             => 'filled|in:xpress_onboarding,xpress_onboarding_test',
        Entity::PG_USE_CASE                                                   => 'sometimes|string|max:500|min:50|nullable',
        Entity::MIQ_SHARING_DATE                                              => 'sometimes|integer',
        Entity::TESTING_CREDENTIALS_DATE                                      => 'sometimes|integer',
        Entity::METADATA                                                      => 'sometimes|array',
    ];

    protected static $editRules   = [
        Entity::WEBSITE_DETAILS                                               => 'sometimes|array',
        Entity::WEBSITE_DETAILS . '.' . Constants::TERMS                      => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::ABOUT                      => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CONTACT                    => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRIVACY                    => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::REFUND                     => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PRICING                    => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::LOGIN                      => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::CANCELLATION               => 'sometimes|custom:website_details|max:255|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::COMMENTS                   => 'sometimes|string|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::PHYSICAL_STORE             => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::SOCIAL_MEDIA               => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_OR_APP             => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::OTHERS                     => 'sometimes|string',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_NOT_READY          => 'sometimes|boolean',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_COMPLIANCE_CONSENT => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::WEBSITE_PRESENT            => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::ANDROID_APP_PRESENT        => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::IOS_APP_PRESENT            => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::OTHERS_PRESENT             => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::WHATSAPP_SMS_EMAIL         => 'sometimes|boolean|nullable',
        Entity::WEBSITE_DETAILS . '.' . Constants::SOCIAL_MEDIA_URLS          => 'sometimes|array|nullable',
        Entity::APP_URLS                                                      => 'sometimes|array',
        Entity::APP_URLS.'.'.Constants::PLAYSTORE_URL                         => 'sometimes|custom:active_url|max:255|nullable',
        Entity::APP_URLS.'.'.Constants::APPSTORE_URL                          => 'sometimes|custom:active_url|max:255|nullable',
        Entity::BLACKLISTED_PRODUCTS_CATEGORY                                 => 'sometimes|string|max:255|nullable',
        Entity::BUSINESS_PARENT_CATEGORY                                      => 'sometimes|string|nullable',
        Entity::PLUGIN_DETAILS                                                => 'sometimes|array',
        Entity::LEAD_SCORE_COMPONENTS                                         => 'sometimes|array',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::GSTIN_SCORE          => 'sometimes|numeric|digits_between:1,3|nullable',
        Entity::LEAD_SCORE_COMPONENTS . '.' . Constants::DOMAIN_SCORE         => 'sometimes|numeric|digits_between:1,3|nullable',
        Entity::ONBOARDING_SOURCE                                             => 'filled|in:xpress_onboarding,xpress_onboarding_test',
        Entity::PG_USE_CASE                                                   => 'sometimes|string|nullable|max:500|min:50',
        Entity::MIQ_SHARING_DATE                                              => 'sometimes|integer',
        Entity::TESTING_CREDENTIALS_DATE                                      => 'sometimes|integer',
        Entity::METADATA                                                      => 'sometimes|array',
    ];

    /**
    * @throws BadRequestValidationFailureException
     * @throws BadRequestException
     * */
    public static function validateMIQSharingAndTestingDate(array &$input, $merchantDetails)
    {
        $minValue =  Carbon::now()->setTimezone(Timezone::IST)->subDays(7)->modify('today')->getTimestamp();
        $maxValue =  Carbon::now()->setTimezone(Timezone::IST)->modify('today')->getTimestamp();

        if(isset($input[Entity::MIQ_SHARING_DATE]) === true)
        {
            if($input[Entity::MIQ_SHARING_DATE] === 0 )
            {
                unset($input[Entity::MIQ_SHARING_DATE]);
            }
            else if ($input[Entity::MIQ_SHARING_DATE] < $minValue or $input[Entity::MIQ_SHARING_DATE] > $maxValue)
            {
               throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INVALID_MIQ_SHARING_DATE, null);
            }
        }

        if(isset($input[Entity::TESTING_CREDENTIALS_DATE]) === true)
        {
            if($input[Entity::TESTING_CREDENTIALS_DATE] === 0)
            {
                unset($input[Entity::TESTING_CREDENTIALS_DATE]);
            }
            else if ($input[Entity::TESTING_CREDENTIALS_DATE] < $minValue or $input[Entity::TESTING_CREDENTIALS_DATE] > $maxValue)
            {
                throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INVALID_TESTING_CREDENTIALS_DATE, null);
            }
        }

        if((isset($input['miq_sharing_date']) === true or
                isset($input['testing_credentials_date']) === true ) and
            $merchantDetails->merchant->org->isFeatureEnabled(\RZP\Models\Feature\Constants::ADDITIONAL_ONBOARDING) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED, null);
        }
    }

    protected function validateFieldTypes($fields, $merchantId)
    {
        $validationData = ['validationSuccess' => true];
        $patterns = [
            'alphanumeric' => '/^[a-zA-Z0-9]+$/',
            'number' => '/^[0-9]+$/',
            'alphabet' => '/^[a-zA-Z]+$/',
            'amount' => '/^\d+(\.\d{1,2})?$/',
            'date' => '/^\d{4}-\d{2}-\d{2}$/', // Format: YYYY-MM-DD
            'string' => '/^[a-zA-Z0-9\s\W]+$/', // Allows alphanumeric, spaces, and special characters
            'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'bool' => '/^(true|false)$/i'
        ];

        foreach ($fields as $field)
        {
            $name = $field['name'];
            $type = $field['type'];
            $value = $field['value'];

            if (!empty($value) && !preg_match($patterns[$type], (string) $value))
            {
                $validationData = [
                    'validationSuccess' => false,
                    'fieldName' => $name,
                    'fieldType' => $type,
                    'fieldValue' => $value
                ];

                $this->getTrace()->info(TraceCode::ORG_DEFINED_CUSTOM_MERCHANT_FIELDS_FIELD_VALIDATION,
                [
                    'merchant_id' => $merchantId,
                    'data' => $validationData
                ]);
                break;
            }
        }

        return $validationData;
    }

    public function validateOrgDefinedMerchantFields(array &$input, $merchantId, $org, $isBulkFlow = false)
    {
        if (isset($input[Entity::METADATA]) and
            array_key_exists(Entity::ORG_DEFINED_MERCHANT_FIELDS, $input[Entity::METADATA]) === true && !$isBulkFlow)
        {

            $isPermissionEnabled = (new Org\Service)->isRequiredPermissionEnabledforOrg($org->getId(), Permission\Name::ORG_DEFINED_CUSTOM_MERCHANT_FIELDS);
            //permission check
            if ($isPermissionEnabled === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED, null);
            }

            //field type check
            $validationData = $this->validateFieldTypes($input[Entity::METADATA][Entity::ORG_DEFINED_MERCHANT_FIELDS], $merchantId);
            if ($validationData['validationSuccess'] === false)
            {
                throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INVALID_FIELD_TYPE, null, $validationData);
            }
        }
    }

    public function validateWebsiteDetails($attribute, $value)
    {
        if(empty($value) === true)
        {
            return;
        }

        try
        {
            if($this->orgId !== null)
            {
                $this->orgId = Org\Entity::verifyIdAndSilentlyStripSign($this->orgId);

                $org = $this->repo->org->findOrFailPublic($this->orgId);

                if($org !== null and $org->isFeatureEnabled(Feature\Constants::VAS_KYC_RBI) === true)
                {
                    // Check if the Website is Razorpay URL for banking merchants
                    if (preg_match(DetailUpload\Validator::RAZORPAY_URL, $value) === 1)
                    {
                        throw new BadRequestValidationFailureException('Invalid ' . $attribute . " : ". $value);
                    }

                    $this->trace->info(TraceCode::MERCHANT_VALIDATE, [
                        'attribute_name'   => $attribute,
                        'attribute_value' => $value,
                    ]);
                }
            }
        }
        catch (BadRequestValidationFailureException $ex)
        {
            $this->trace->traceException($ex);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,$ex,$ex->getMessage()
            );
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);
        }

        // Check if the URL is active
        $this->validateActiveUrl($attribute, $value);
    }
}
