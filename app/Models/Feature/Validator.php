<?php

namespace RZP\Models\Feature;

use App;
use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Base\Fetch;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use Illuminate\Http\Request;
use RZP\Models\Terminal\Category;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\Detail\BusinessType;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_ID   => 'required|string|max:20',
        Entity::ENTITY_TYPE => 'required|string|max:255|in:merchant,application,org,partner_application',
        Entity::NAME        => 'required|string|max:25|custom'
    ];

    protected static $createValidators = [
        'skipWorkflowPayoutSpecific',
        'covid19Relief',
        'rzpTrustedBadge',
    ];

    protected static $onboardingSubmissionsUpsertRules = [
        Constants::MARKETPLACE                                     => 'filled|array|max:3',
        Constants::MARKETPLACE . "." . Constants::USE_CASE         => 'filled|string',
        Constants::MARKETPLACE . "." . Constants::SETTLING_TO      => 'filled|string',
        Constants::MARKETPLACE . "." . Constants::VENDOR_AGREEMENT => 'filled|file',

        Constants::SUBSCRIPTIONS                                   => 'filled|array|max:3',

        Constants::VIRTUAL_ACCOUNTS                                             => 'filled|array|max:2',
        Constants::VIRTUAL_ACCOUNTS . "." . Constants::USE_CASE                 => 'filled|string',
        Constants::VIRTUAL_ACCOUNTS . "." . Constants::EXPECTED_MONTHLY_REVENUE => 'filled|string',

        Constants::QR_CODES                                        => 'filled|array'
    ];

    protected static $onboardingQuestionsRules = [
        Constants::FEATURES     => "required|array"
    ];

    protected static $merchantsWithFeaturesRules = [
        Constants::FEATURES     => "required|array"
    ];

    protected static $onboardingSubmissionsFetchRules = [
        Fetch::TO           => 'sometimes|epoch',
        Fetch::FROM         => 'sometimes|epoch',
        Fetch::COUNT        => 'sometimes|integer',
        Fetch::SKIP         => 'sometimes|integer',
        Constants::STATUS   => 'sometimes|string|custom',
        Constants::PRODUCT  => 'sometimes|string|custom',
    ];

    protected static array $bulkFetchFeaturesRules = [
        Constants::FEATURES => 'required|array|filled',
        Entity::ENTITY_TYPE => 'required|string|in:merchant',
        Entity::ENTITY_ID   => 'required|string',
    ];

    protected function validateName($attribute, $value)
    {
        $allFeatures = array_keys(Constants::$featureValueMap);

        if (in_array($value, $allFeatures) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid feature',
                $attribute,
                $value);
        }
    }

    public function validateZoho(Request $request)
    {
        if (Merchant\Preferences::checkZohoHeaders($request->headers) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment failed');
        }
    }

    /**
     * Validates that the feature is not in already assigned list of merchant
     * features.
     *
     * @param array $assignedFeatureNames
     *
     * @throws Exception\BadRequestException
     */
    public function validateFeatureIsNotAlreadyAssigned(array $assignedFeatureNames)
    {
        $feature = $this->entity;

        $name = $feature->getName();

        if (in_array($name, $assignedFeatureNames, true) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ALREADY_ASSIGNED,
                Entity::FEATURE,
                [
                    Entity::ID                => $feature->getId(),
                    Entity::NAME              => $feature->getName(),
                    Entity::OLD_FEATURES      => $assignedFeatureNames,
                    PublicEntity::MERCHANT_ID => $feature->getMerchantId(),
                ]);
        }
    }

    public function validateStatus($attribute, $value)
    {
        $onboardingStatuses = Constants::ONBOARDING_STATUSES;

        if (in_array($value, $onboardingStatuses, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid status: $value",
                $attribute);
        }
    }

    public function validateProduct($attribute, $value)
    {
        $productFeatures = Constants::PRODUCT_FEATURES;

        if (in_array($value, $productFeatures, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid product: $value",
                $attribute);
        }
    }

    public function validateSkipWorkflowPayoutSpecific($input)
    {

        if ($input[Entity::NAME] === Constants::SKIP_WF_AT_PAYOUTS)
        {
            $app = App::getFacadeRoot();

            $merchantId = null;

            $treatment = null;

            if ($input[Entity::ENTITY_TYPE] === Constants::MERCHANT)
            {
                $merchantId = $input[Entity::ENTITY_ID];

                $treatment = $app->razorx->getTreatment(
                    $merchantId,
                    Merchant\RazorxTreatment::SKIP_WORKFLOW_PAYOUT_SPECIFIC_FEATURE,
                    $this->getMode()
                );
            }

            if (($treatment === null) or
                ($treatment !== 'on'))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
                    Entity::NAME,
                    Constants::SKIP_WF_AT_PAYOUTS
                );
            }
        }
    }

    public function validateRzpTrustedBadge($input)
    {
        if ($input[Entity::NAME] === Constants::RZP_TRUSTED_BADGE && $input[Entity::ENTITY_TYPE] === Constants::MERCHANT)
        {
            $app = App::getFacadeRoot();

            $merchantId = $input[Entity::ENTITY_ID];

            $merchant = $app['repo']->merchant->find($merchantId);

            if ($merchant->isRazorpayOrgId() === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
                    Entity::NAME,
                    Constants::RZP_TRUSTED_BADGE,
                    'Cannot Enable Trusted Badge feature, since merchant not part of Razorpay org'
                );
            }

            $disputes = $app['repo']->dispute->getLostOrClosedDisputeInLast4MonthsByMerchantId($merchantId);

            if ($disputes !== null)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
                    Entity::NAME,
                    [
                        'feature' => Constants::RZP_TRUSTED_BADGE,
                    ],
                    'Cannot Enable Trusted Badge feature, since merchant lost disputes in last 4 months'
                );
            }

            if (in_array($merchant->getCategory2(), [Category::LENDING], true) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
                    Entity::NAME,
                    [
                        'feature' => Constants::RZP_TRUSTED_BADGE,
                        'category' => $merchant->getCategory2(),
                    ],
                    'Cannot Enable Trusted Badge feature, since merchant category is Lending or DMT'
                );
            }

            if ($merchant->getActivatedAt() === null || $merchant->isActivateForFourMonths() === false)
            {
                $activationDate = Carbon::createFromTimestamp($merchant->getActivatedAt())
                                        ->timezone(\RZP\Constants\Timezone::IST)
                                        ->toDateString();
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
                    Entity::NAME,
                    [
                        'feature' => Constants::RZP_TRUSTED_BADGE,
                        'activation_date' => $activationDate,
                    ],
                    'Cannot Enable Trusted Badge feature, since merchant has not been activated for 4 months'
                );
            }
        }
    }

    public function validateCovid19Relief($input)
    {
        if ($input[Entity::NAME] === Constants::COVID_19_RELIEF && $input[Entity::ENTITY_TYPE] === Constants::MERCHANT) {
            $app = App::getFacadeRoot();

            $merchantId = $input[Entity::ENTITY_ID];

            $merchant = $app['repo']->merchant->find($merchantId);

            $variant = App::getFacadeRoot()->razorx->getTreatment(
                $merchant->getId(),
                Merchant\RazorxTreatment::COVID_19_DONATION_SHOW,
                $this->getMode()
            );

            if ($variant !== 'on')
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
                    Entity::NAME,
                    Constants::COVID_19_RELIEF,
                    'Feature is not live right now'
                );
            }

            if ($merchant->merchantDetail === null or $merchant->merchantDetail->getBusinessType() === null)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
                    Entity::NAME,
                    Constants::COVID_19_RELIEF,
                    'Merchant business type is not available'
                );
            }

            $businessType = $merchant->merchantDetail->getBusinessType();

            if (in_array($businessType, [BusinessType::NGO, BusinessType::TRUST]))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
                    Entity::NAME,
                    Constants::COVID_19_RELIEF,
                    'Cannot Enable covid 19 relief feature, since merchant business type is either NGO or TRUST'
                );
            }
        }
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateFeatureName(?string $featureName)
    {
        if (empty(trim($featureName)) === true)
        {
            throw new Exception\BadRequestValidationFailureException('Feature name not provided');
        }

        $allFeatures = array_keys(Constants::$featureValueMap);

        if (in_array($featureName, $allFeatures) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid feature', $featureName);
        }
    }

    public function validateFeatureNames(array $featureNames)
    {
        $allFeatures = array_keys(Constants::$featureValueMap);

        if(empty($featureNames) === false)
        {
            $featuresDiff = array_diff($featureNames, $allFeatures);

            if (count($featuresDiff) > 0)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Invalid features");
            }
        }
    }

    public function validateForRouteLaPennyTestingFeature($featureNames)
    {
        if ((in_array(Constants::ROUTE_LA_PENNY_TESTING, $featureNames) === true) and
            (count($featureNames) > 1))
        {
                throw new Exception\BadRequestValidationFailureException(
                    'Cannot assign route_la_penny_testing feature with other features.'
                );
        }
    }
}
