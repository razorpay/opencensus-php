<?php

namespace RZP\Models\Feature;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Base\Fetch;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use Illuminate\Http\Request;
use RZP\Models\Base\PublicEntity;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_ID   => 'required|string|max:20',
        Entity::ENTITY_TYPE => 'required|string|max:255|in:merchant,application,org',
        Entity::NAME        => 'required|string|max:25|custom'
    ];

    protected static $createValidators = [
        'skipWorkflowPayoutSpecific'
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
    ];

    protected static $onboardingQuestionsRules = [
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
}
