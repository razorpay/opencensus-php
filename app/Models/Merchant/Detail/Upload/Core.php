<?php


namespace RZP\Models\Merchant\Detail\Upload;

use Throwable;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Pricing;
use RZP\Models\Feature;
use RZP\Constants\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Exception\BaseException;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Models\User\Service as UserService;
use RZP\Models\Pricing\Service as PricingService;
use RZP\Models\Merchant\Service as MerchantService;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Core as MDetailCore;
use RZP\Models\Merchant\Website\Core as MWebsiteCore;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Detail\Upload\Processors\Factory;
use RZP\Models\Merchant\Detail\Upload\Constants as UConstants;
use RZP\Models\Merchant\BvsValidation;

class Core extends Base\Core
{
    protected $mutex;

    protected $userService;

    protected $auth;

    /**
     * @var MDetailCore
     */
    private $merchantDetailCore;

    /**
     * @var BusinessDetail\Service
     */
    private $businessDetailService;

    private $merchantWebsite;

    private  $pricingService;

    private  $merchantService;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->auth = $this->app['basicauth'];

        $this->userService = new UserService();

        $this->merchantDetailCore = new MDetailCore();

        $this->businessDetailService = new BusinessDetail\Service();

        $this->merchantWebsite = new MWebsiteCore();

        $this->pricingService = new PricingService();

        $this->merchantService = new MerchantService();
    }

    public function uploadMerchant(array $input)
    {
        (new Validator)->validateInput('uploadMerchant', $input);

        $format = $input[Constants::FORMAT];

        $parser = Factory::getInstance($format);

        $merchantDetailsInput = $parser->parse($input[Constants::FILE]);

        return $this->mutex->acquireAndRelease(
            $merchantDetailsInput['contact_email'],
            function () use ($merchantDetailsInput) {

                return $this->repo->transactionOnLiveAndTestAndAsv(function () use(
                    $merchantDetailsInput
                ) {
                    $user = $this->createUser(
                        $merchantDetailsInput['contact_email'],
                        $merchantDetailsInput['business_name']
                    );

                    $merchant = $this->createMerchant(
                        $user, $merchantDetailsInput['contact_email'],
                        $merchantDetailsInput['business_name']
                    );

                    $merchantDetailsInput[DetailEntity::SUBMIT] = '1';

                    return $this->merchantDetailCore->saveMerchantDetails($merchantDetailsInput, $merchant);
                });
        });
    }

    /**
     * *
     * Create merchant and save required/activation details.
     *
     * @param array $entry
     * @return array
     * @throws Throwable
     * @throws LogicException
     */
    public function processMerchantEntry(array $entry): array
    {
        (new Validator)->validateRequestInput($entry);

        $lockKey = $entry[Header::MIQ_CONTACT_EMAIL];

        return $this->mutex->acquireAndRelease($lockKey, function () use ($entry)
        {
            $parser = Factory::getInstance(Constants::BULK_UPLOAD_MIQ);

            $this->trace->info(TraceCode::BATCH_SERVICE_UPLOAD_MIQ_CREATE_REQUEST, [
                    'entry'      =>     $parser->getMaskedEntryForLogging($entry),
                ]
            );

            // Creating a copy of entry for preprocessing
            $processedEntry = array_slice($entry, 0);

            $parser->preProcessMerchantEntry($processedEntry);

            $merchant = $this->repo->transactionOnLiveAndTestAndAsv(function () use ($processedEntry, $parser, &$entry)
            {
                $user = $this->createUser($processedEntry[Header::MIQ_CONTACT_EMAIL], $processedEntry[Header::MIQ_MERCHANT_NAME],
                    $processedEntry[UConstants::IS_DS_MERCHANT], $processedEntry[Header::MIQ_CONTACT_NUMBER]);

                $merchant = $this->createMerchant($user, $processedEntry[Header::MIQ_CONTACT_EMAIL], $processedEntry[Header::MIQ_MERCHANT_NAME],
                    $processedEntry[MerchantEntity::ORG_ID], $processedEntry[UConstants::IS_DS_MERCHANT]);

                $feeBearer = $parser->getMerchantFeeBearerType($processedEntry);

                $this->setFeeModelAndFeeBearer($merchant, $processedEntry, $feeBearer);

                if(empty($merchant) === true)
                {
                    throw new Exception\RuntimeException("Failed to create merchant", null,
                        null, ErrorCode::SERVER_ERROR);
                }

                // Add only_ds feature only to merchant, if the 'is_ds_merchant' field is set.
                if($processedEntry[UConstants::IS_DS_MERCHANT] === '1')
                {
                    $this->addOnlyDSRelevantFeatures($merchant->getId());
                }

                $entry[Header::MIQ_OUT_MERCHANT_ID] = $merchant->getId();

                $entry[Header::MIQ_OUT_FEE_BEARER] = $merchant->getFeeBearer();

                $merchantDetailsInput = $parser->getMerchantDetailInput($processedEntry);

                $this->merchantDetailCore->saveMerchantDetails($merchantDetailsInput, $merchant);

                $websiteDetails = $parser->getWebsiteDetailInput($processedEntry);

                $this->businessDetailService->saveBusinessDetailsForMerchant($merchant->getId(), $websiteDetails);

                $org = $merchant->org;

                $orgDefinedMerchantFields = $parser->getOrgDefinedMerchantFields($processedEntry, $org);

                if (!empty($orgDefinedMerchantFields))
                {
                    $additionalFieldsValidationResponse = (new Validator)->validateOrgDefinedMerchantFieldsInput($entry, $org->getId());
                }

                if (!empty($additionalFieldsValidationResponse) and empty($additionalFieldsValidationResponse[Header::ERROR_CODE]))
                {
                    //if no error in field validations then proceed to save the details
                    $this->businessDetailService->saveBusinessDetailsForMerchant($merchant->getId(), $orgDefinedMerchantFields, true);
                }

                // The activation form milestone is set to L2 as here we are submitting the KYC form.
                $submitData = [
                    DetailEntity::ACTIVATION_FORM_MILESTONE => "L2",
                    DetailEntity::SUBMIT => '1',
                ];

                $response = $this->merchantDetailCore->saveMerchantDetails($submitData, $merchant);

                $merchantWebsite = $parser->getMerchantWebsiteInput($websiteDetails['website_details'],
                    $processedEntry[Header::MIQ_WEBSITE],$merchant->getId(),$processedEntry);

                $this->merchantWebsite->createOrEditWebsiteDetails($merchant->merchant_detail, $merchantWebsite);

                if ($response[DetailEntity::SUBMITTED] === false)
                {
                    $this->trace->info(TraceCode::MERCHANT_ACTIVATION_FORM_SUBMISSION_FAILURE,
                        [
                            'merchant_id' => $merchant->getId(),
                        ]);

                    $entry[Header::STATUS] = Status::FAILURE;

                    $entry[Header::ERROR_CODE] = ErrorCode::SERVER_ERROR;

                    $entry[Header::ERROR_DESCRIPTION] = 'Failed to submit activation details';
                } else {
                    //storing errors from KYC verification calls
                    $bvsResponse = $this->merchantDetailCore->getBVSResponseforKYCValidations($merchant->getId());
                    $entry[Header::ERROR_CODE] = $bvsResponse[0];
                    $entry[Header::ERROR_DESCRIPTION] = $bvsResponse[1];

                    //storing error for orgDefinedMerchantFields
                    if (!empty($additionalFieldsValidationResponse[Header::ERROR_CODE]))
                    {
                        $entry[Header::ERROR_CODE] = $entry[Header::ERROR_CODE] . ', ' . $additionalFieldsValidationResponse[Header::ERROR_CODE];
                        $entry[Header::ERROR_DESCRIPTION] = $entry[Header::ERROR_DESCRIPTION] . ', ' . $additionalFieldsValidationResponse[Header::ERROR_DESCRIPTION];
                    }
                }

                return $merchant;
            });

            // Creating pricing plan only, when merchant is created and KYC form submitted successfully.
            if(!empty($merchant) && empty($entry[Header::STATUS]))
            {
                try
                {
                    // create forward pricing plan and assign to merchant.
                    $plan = (new Pricing\Core)->create([ Pricing\Entity::PLAN_NAME => $merchant->getId(),
                        Pricing\Entity::RULES => $parser->getPricingRulesInput($processedEntry)
                    ], $merchant->getOrgId()
                    );

                    $merchant->setPricingPlan($plan[0][Pricing\Entity::PLAN_ID]);

                    $this->repo->saveOrFail($merchant);

                    $entry[Header::STATUS] = Status::SUCCESS;
                }
                catch (BaseException $e)
                {
                    $error = $e->getError();

                    $entry[Header::STATUS]            = Status::FAILURE;

                    $entry[Header::ERROR_CODE]        = $error->getPublicErrorCode();

                    $entry[Header::ERROR_DESCRIPTION] = $error->getDescription();
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException($e, null, TraceCode::MERCHANT_UPLOAD_MIQ_PRICING_PLAN_CREATION_FAILED, [
                        Header::MIQ_CONTACT_EMAIL          => $entry[Header::MIQ_CONTACT_EMAIL],
                        Header::MIQ_OUT_MERCHANT_ID        => $merchant->getId(),
                    ]);

                    $entry[Header::STATUS]   = Status::FAILURE;

                    $entry[Header::ERROR_CODE] = ErrorCode::SERVER_ERROR;

                    $entry[Header::ERROR_DESCRIPTION] = 'Failed to create pricing plan';
                }
            }

            $this->trace->info(TraceCode::BATCH_SERVICE_UPLOAD_MIQ_CREATE_RESPONSE, [
                    'response'    =>  $parser->getMaskedEntryForLogging($entry),
                ]
            );

            return $entry;
        });
    }

    public function processUpdateMerchantEntry(array $entry): array
    {
        (new Validator)->validateUpdateRequestInput($entry);

        $lockKey = $entry[Header::MIQ_MERCHANT_ID];

        return $this->mutex->acquireAndRelease($lockKey, function () use ($entry) {
            $parser = Factory::getInstance(Constants::BULK_UPLOAD_MIQ);

            $this->trace->info(TraceCode::BATCH_SERVICE_MERCHANT_UPDATE_MIQ_REQUEST, [
                    'entry' => $parser->getMaskedEntryForLogging($entry),
                ]
            );

            // Creating a copy of entry for preprocessing
            $processedEntry = array_slice($entry, 0);

            $parser->preProcessMerchantEntry($processedEntry);

            $this->repo->transactionOnLiveAndTestAndAsv(function () use ($processedEntry, $parser, &$entry)
            {
                $merchant = $this->repo->merchant->findOrFail($entry[Header::MIQ_MERCHANT_ID]);
                // update fee model
                $this->updateFeeModel($merchant, $processedEntry);

                //update merchant details

                $merchantDetail = $this->repo->merchant_detail->getByMerchantId($entry[Header::MIQ_MERCHANT_ID]);

                $merchantDetailData = $parser->getUpdatedMerchantDetailInput();

                foreach ($merchantDetailData as $key=>$value )
                {
                    if(strtolower($entry[$key]) != 'na')
                    {
                        ($merchantDetail->setAttribute($value,$entry[$key]));
                    }
                }

                $merchantDetail->setLocked(false);

                $merchantDetail[DetailEntity::ACTIVATION_STATUS] = null;

                $this->repo->merchant_detail->saveOrFail($merchantDetail);

                //update website details

                if(strtolower($entry[Header::MIQ_WEBSITE]) !== 'na')
                {
                    $websiteDetail = $this->repo->merchant_website->getWebsiteDetailsForMerchantId($entry[Header::MIQ_MERCHANT_ID]);

                    $adminWebsiteDetails = $websiteDetail['admin_website_details']['website'][$entry[Header::MIQ_WEBSITE]];

                    $newWebsiteDetail = $parser->getUpdatedWebsiteDetailInput($entry, $adminWebsiteDetails);

                    $this->businessDetailService->saveBusinessDetailsForMerchant($entry[Header::MIQ_MERCHANT_ID], $newWebsiteDetail);

                    $merchantWebsite = $parser->getUpdateMerchantWebsiteInput($newWebsiteDetail['website_details'],
                        $processedEntry[Header::MIQ_WEBSITE],$merchant->getId(),$processedEntry);

                    $this->merchantWebsite->createOrEditWebsiteDetails($merchant->merchant_detail, $merchantWebsite);
                }

                $org = $merchant->org;

                $orgDefinedMerchantFields = $parser->getOrgDefinedMerchantFields($processedEntry, $org);

                if (!empty($orgDefinedMerchantFields))
                {
                    $additionalFieldsValidationResponse = (new Validator)->validateOrgDefinedMerchantFieldsInput($entry, $org->getId());
                }

                if (!empty($additionalFieldsValidationResponse) and empty($additionalFieldsValidationResponse[Header::ERROR_CODE]))
                {
                    //if no error in field validations then proceed to save the details
                    $this->businessDetailService->saveBusinessDetailsForMerchant($merchant->getId(), $orgDefinedMerchantFields, true);
                }

                $submitData = [
                    DetailEntity::ACTIVATION_FORM_MILESTONE => "L2",
                    DetailEntity::SUBMIT => '1',
                ];

                $response = $this->merchantDetailCore->saveMerchantDetails($submitData, $merchant);

                if ($response[DetailEntity::SUBMITTED] === false)
                {
                    $this->trace->info(TraceCode::MERCHANT_UPDATION_FORM_SUBMISSION_FAILURE,
                        [
                            'merchant_id' => $merchant->getId(),
                        ]);

                    $entry[Header::STATUS] = Status::FAILURE;

                    $entry[Header::ERROR_CODE] = ErrorCode::SERVER_ERROR;

                    $entry[Header::ERROR_DESCRIPTION] = 'Failed to submit updation details';
                }
                else
                {
                    //storing errors from KYC verification calls
                    $bvsResponse = $this->merchantDetailCore->getBVSResponseforKYCValidations($merchant->getId());
                    $entry[Header::ERROR_CODE] = $bvsResponse[0];
                    $entry[Header::ERROR_DESCRIPTION] = $bvsResponse[1];

                    //storing error for orgDefinedMerchantFields
                    if (!empty($additionalFieldsValidationResponse[Header::ERROR_CODE]))
                    {
                        $entry[Header::ERROR_CODE] = $entry[Header::ERROR_CODE] . ', ' . $additionalFieldsValidationResponse[Header::ERROR_CODE];
                        $entry[Header::ERROR_DESCRIPTION] = $entry[Header::ERROR_DESCRIPTION] . ', ' . $additionalFieldsValidationResponse[Header::ERROR_DESCRIPTION];
                    }
                }
            });

            $entry[Header::STATUS] = Status::SUCCESS;

            $this->trace->info(TraceCode::BATCH_SERVICE_UPDATE_MIQ_CREATE_RESPONSE, [
                    'response'    =>  $parser->getMaskedEntryForLogging($entry),
                ]
            );

            return $entry;
        });
    }

    public function processUpdatePricingEntry(array $entry): array
    {
        (new Validator)->validateUpdatePricingRequestInput($entry);

        $lockKey = $entry[Header::MIQ_MERCHANT_ID];

        return $this->mutex->acquireAndRelease($lockKey, function () use ($entry) {
            $parser = Factory::getInstance(Constants::BULK_UPLOAD_MIQ);

            $this->trace->info(TraceCode::BATCH_SERVICE_PRICING_UPDATE_MIQ_REQUEST, [
                    'entry' => $parser->getMaskedEntryForLogging($entry),
                ]
            );

            // Creating a copy of entry for preprocessing
            $processedEntry = array_slice($entry, 0);

            $parser->preProcessMerchantEntry($processedEntry);

            $merchant = $this->repo->merchant->findOrFail($entry[Header::MIQ_MERCHANT_ID]);

            try {

                $planId = $merchant->getPricingPlanId();

                $plan = $this->repo->pricing->getPlanByIdOrFailPublic($planId, 'org_' . $merchant->org->getId());

                if ($this->repo->merchant->checkMerchantsCountWithPricingPlanIdNotEqualOne($planId))
                {
                    $newPlan = $this->pricingService->replicatePlanAndAssign($merchant, $plan);

                    $merchant->refresh();

                    $plan = $newPlan;
                }

                $parser->filterRules($plan, $entry);

                $this->repo->saveOrFail($merchant);

                $entry[Header::STATUS] = Status::SUCCESS;
            }
            catch (BaseException $e)
            {
                $error = $e->getError();
                $this->trace->info(TraceCode::BATCH_SERVICE_UPDATE_MIQ_CREATE_RESPONSE, [
                        'Error Response'    =>  $error->getDescription(),
                    ]
                );
                $entry[Header::STATUS]            = Status::FAILURE;

                $entry[Header::ERROR_CODE]        = $error->getPublicErrorCode();

                $entry[Header::ERROR_DESCRIPTION] = $error->getDescription();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, null, TraceCode::MERCHANT_UPLOAD_MIQ_PRICING_PLAN_UPDATION_FAILED, [
                    Header::MIQ_CONTACT_EMAIL          => $entry[Header::MIQ_CONTACT_EMAIL],
                    Header::MERCHANT_ID                => $merchant->getId(),
                ]);

                $entry[Header::STATUS]   = Status::FAILURE;

                $entry[Header::ERROR_CODE] = ErrorCode::SERVER_ERROR;

                $entry[Header::ERROR_DESCRIPTION] = 'Failed to update pricing plan';
            }

            $this->trace->info(TraceCode::BATCH_SERVICE_UPDATE_MIQ_CREATE_RESPONSE, [
                    'response'    =>  $parser->getMaskedEntryForLogging($entry),
                ]
            );

            return $entry;
        });
    }

    public function processAdditionalMerchantFieldsEntry(array $entry): array
    {
        (new Validator)->validateAdditionalMerchantFieldsRequestInput($entry);

        $this->trace->info(TraceCode::BATCH_SERVICE_ORG_DEFINED_MERCHANT_FIELDS_REQUEST, [
                'entry' => $entry,
            ]
        );

        $lockKey = $entry[Header::MERCHANT_ID];

        return $this->mutex->acquireAndRelease($lockKey, function () use ($entry) {
            $parser = Factory::getInstance(Constants::BULK_UPLOAD_MIQ);

            $this->repo->transactionOnLiveAndTestAndAsv(function () use ($parser, &$entry) {
                $merchant = $this->repo->merchant->findOrFail($entry[Header::MERCHANT_ID]);

                $org = $merchant->org;

                $orgDefinedMerchantFields = $parser->getOrgDefinedMerchantFields($entry, $org);

                if (!empty($orgDefinedMerchantFields))
                {
                    $additionalFieldsValidationResponse = (new Validator)->validateOrgDefinedMerchantFieldsInput($entry, $org->getId());
                }

                if (!empty($additionalFieldsValidationResponse) and empty($additionalFieldsValidationResponse[Header::ERROR_CODE]))
                {
                    //if no error in field validations then proceed to save the details
                    $this->businessDetailService->saveBusinessDetailsForMerchant($merchant->getId(), $orgDefinedMerchantFields, true);
                    $entry[Header::STATUS] = Status::SUCCESS;
                }
                else if(!empty($additionalFieldsValidationResponse[Header::ERROR_CODE]))
                {
                    $entry[Header::STATUS] = Status::FAILED;
                    $entry[Header::ERROR_CODE] = $additionalFieldsValidationResponse[Header::ERROR_CODE];
                    $entry[Header::ERROR_DESCRIPTION] = $additionalFieldsValidationResponse[Header::ERROR_DESCRIPTION];
                }

                $this->trace->info(TraceCode::BATCH_SERVICE_ORG_DEFINED_MERCHANT_FIELDS_RESPONSE, [
                        'response'    =>  $entry
                    ]
                );
            });

            return $entry;
        });
    }

    protected function createUser(string $email, string $businessName, string $onlyDs = null, string $contactMobile = null)
    {
        $confirm_token = gen_uuid();

        $password = gen_uuid();

        $userInput = [
            'email'                 => $email,
            'password'              => $password,
            'password_confirmation' => $password,
            'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
            'confirm_token'         => $confirm_token,
            'name'                  => $businessName,
            'contact_mobile'        => $contactMobile,
        ];

        // If the create request is for the only DS merchant, then set the only_ds_upload_miq field in the input.
        // It's required to unblock the user creation at the core layer.
        if($onlyDs == 1)
        {
            $userInput[UConstants::ONLY_DS_UPLOAD_MIQ] = true;
        }

        return $this->userService->create($userInput);
    }

    protected function createMerchant($user, string $email, string $businessName, string $orgId = null, string $onlyDs = null)
    {
        $merchantInput = [
            'name'  => $businessName,
            'email' => $email,
            'org_id'=> $orgId ?? $this->auth->getOrgId()
        ];

        // If the create request is for the only DS merchant, then set the only_ds_upload_miq field in the input.
        // It's required to unblock the merchant creation at the core layer.
        if($onlyDs == 1)
        {
            $merchantInput[UConstants::ONLY_DS_UPLOAD_MIQ] = true;
        }

        $merchantData = $this->userService->createMerchantFromUser($merchantInput, $user, '', false, [], false);

        return $this->repo->merchant->findOrFailPublic($merchantData[MerchantEntity::ID]);
    }

    /**
     * Update merchant details if input fields present.
     *
     * @param MerchantEntity $merchant
     * @param array $input
     * @param string $feeBearer
     * @return void
     */
    private function setFeeModelAndFeeBearer(MerchantEntity $merchant, array $input, string $feeBearer): void
    {
        if(empty($input[Header::MIQ_FEE_MODEL]) === false)
        {
            $merchant->setFeeModel($input[Header::MIQ_FEE_MODEL]);
        }

        // default platform gets assigned if nothing explicitly assigned.
        if(empty($feeBearer) === false)
        {
            $merchant->setFeeBearer($feeBearer);
        }

        $this->repo->saveOrFail($merchant);
    }

    private function updateFeeModel(MerchantEntity $merchant, array $input): void
    {
        if(strtolower($input[Header::MIQ_FEE_MODEL]) != 'na')
        {
            $merchant->setFeeModel($input[Header::MIQ_FEE_MODEL]);
        }

        $this->repo->saveOrFail($merchant);
    }

    /**
     * Add required feature flags for only DS merchant onboarding.
     *
     * @param string $merchantId
     * @return void
     */
    public function addOnlyDSRelevantFeatures(string $merchantId): void
    {
        $featureParams = [
            Feature\Entity::ENTITY_ID   => $merchantId,
            Feature\Entity::ENTITY_TYPE => 'merchant',
            Feature\Entity::NAMES       => [Feature\Constants::ONLY_DS],
            Feature\Entity::SHOULD_SYNC => false
        ];

        (new Feature\Service)->addFeatures($featureParams);
    }
}
