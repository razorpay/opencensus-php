<?php

namespace RZP\Models\Merchant\Detail;

use Mail;
use Queue;
use Config;
use Lib\PhoneBook;
use Carbon\Carbon;
use RZP\Constants\HyperTrace;
use RZP\Encryption;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Table;
use Rzp\Bvs\Validation\V1\TwirpError;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BVSConstants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\AutoKyc\Bvs\Factory;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Trace\Tracer;
use RZP\Models\State;
use RZP\Models\Coupon;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use phpseclib\Crypt\AES;
use RZP\Trace\TraceCode;
use RZP\Jobs\RequestJob;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Batch\Type;
use RZP\Constants\Product;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\State\Reason;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Metric;
use RZP\Constants\IndianStates;
use RZP\Models\Merchant\AutoKyc;
use RZP\Models\MerchantRiskAlert;
use RZP\Models\Admin\Permission;
use RZP\Encryption\AESEncryption;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Document;
use RZP\Models\Merchant\Constants;
use RZP\lib\ConditionParser\Parser;
use RZP\Models\Partner\Activation;
use RZP\Models\Merchant\Promotion;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\LegalEntity;
use RZP\Models\Merchant\Stakeholder;
use RZP\Listeners\ApiEventSubscriber;
use RZP\lib\ConditionParser\Operator;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\AvgOrderValue;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Action as Action;
use RZP\Mail\Merchant\RejectionSettlement;
use RZP\Models\Comment\Core as CommentCore;
use RZP\Models\Partner\Core as PartnerCore;
use RZP\Services\MerchantRiskClient as MRS;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Key\Validator as KeyValidator;
use Illuminate\Foundation\Bus\DispatchesJobs;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
use RZP\Models\Merchant\BusinessDetail\Service;
use RZP\Models\Comment\Entity as CommentEntity;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Mail\Merchant\MerchantBusinessWebsiteAdd;
use RZP\Models\Base\PublicEntity as PublicEntity;
use RZP\Mail\Merchant\Rejection as RejectionEmail;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Workflow\Action\Core as ActionCore;
use RZP\Models\Merchant\Product as MerchantProduct;
use RZP\Mail\Merchant\RazorpayX\L2SubmissionGreylist;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Mail\Merchant\RazorpayX\L2SubmissionWhitelist;
use RZP\Models\Merchant\Detail\Metric as DetailMetric;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\AccessMap\Core as AccessMapCore;
use RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;
use RZP\Models\Merchant\Balance\Ledger\Core as LedgerCore;
use RZP\Models\Merchant\Fraud\HealthChecker as HealthChecker;
use RZP\Models\Workflow\Action\Core as WorkFlowActionCore;
use RZP\Models\Workflow\Action\Entity as WorkFlowActionEntity;
use RZP\Mail\Admin\NotifyActivationSubmission as NotifyAdmin;
use RZP\Models\Merchant\Account\Constants as AccountConstants;
use RZP\Models\Merchant\Request\Constants as RequestConstants;
use RZP\Models\Merchant\Credits\Balance\Entity as CreditEntity;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\CinAuth;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\LlpinAuth;
use RZP\Mail\Merchant\NeedsClarificationEmail as ClarificationEmail;
use RZP\Mail\Merchant\SubMerchantNCStatusChanged as SubMerchantNCStatusChangedEmail;
use RZP\Notifications\Dashboard\Events as DashboardNotificationEvent;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;
use RZP\Models\Merchant\Detail\BusinessDetailSearch\InMemoryBusinessSearch;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessDetailConstants;
use RZP\Models\Merchant\Detail\NeedsClarification\UpdateContextRequirements;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Notifications\Dashboard\Constants as DashboardNotificationConstants;
use RZP\Models\Merchant\Fraud\HealthChecker\Constants as HealthCheckerConstants;
use RZP\Models\Merchant\Detail\NeedsClarification\Constants as NCConstants;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\ManualVerificationRequestDispatcher;
use RZP\Models\Merchant\Document\Type as DocumentType;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\BankAccount as BankAccountRequestDispatcher;


class Core extends Base\Core
{
    use NotifyTrait;
    use DispatchesJobs;

    protected $kycServiceRetryDelayInSecond;

    protected $mutex;

    private   $mrclient;

    private   $mcore;

    protected $dedupeCore;

    public function __construct()
    {
        parent::__construct();

        $this->kycServiceRetryDelayInSecond = (int) $this->app['config']['applications.kyc']['retry_delay'];

        $this->mutex = $this->app['api.mutex'];

        $this->mrclient = $this->app['merchantRiskClient'];

        $this->mcore = new Merchant\Core();

        $this->dedupeCore = new DeDupe\Core();
    }

    public function setDedupeCore($dedupeCore)
    {
        $this->dedupeCore = $dedupeCore;
    }

    public function isPOIVerificationRequiredForL2(Entity $merchantDetails, array $input)
    {
        if (empty($input[Entity::PROMOTER_PAN]) === true)
        {
            return true;
        }

        if (($merchantDetails->getPromoterPan() === $input[Entity::PROMOTER_PAN]) and
            ($merchantDetails->getPoiVerificationStatus() === BvsValidationConstants::VERIFIED))
        {
            return false;
        }

        return true;
    }

    public function getBvsValidationArtefactDetails(string $merchantId, string $validationArtefact, $validationId = null)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $factory = new Merchant\AutoKyc\Bvs\requestDispatcher\Factory();

        $requestDispatcher = $factory->getBvsRequestDispatcherForArtefact(
            $validationArtefact, $merchant, $merchant->merchantDetail);

        return $requestDispatcher->fetchValidationDetails($validationId);
    }

    public function checkForCorrectAppUrls($appUrls)
    {
        //check For valid playStore Url
        if (empty($appUrls[BusinessDetailConstants::PLAYSTORE_URL]) === false)
        {
            $playstoreUrl = $appUrls[BusinessDetailConstants::PLAYSTORE_URL];
            if (str_starts_with($playstoreUrl, 'https://play.google.com/store/apps/details') === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PLAYSTORE_URL);
            }
        }

        //check For valid appStore Url
        if (empty($appUrls[BusinessDetailConstants::APPSTORE_URL]) === false)
        {
            $appstoreUrl = $appUrls[BusinessDetailConstants::APPSTORE_URL];
            if (str_starts_with($appstoreUrl, 'https://apps.apple.com') === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_APPSTORE_URL);
            }
        }
    }

    protected function getMaskedDataForLogging(array $input)
    {
        $maskedInput = [];

        foreach ($input as $key => $value)
        {
            $maskedInput[$key] = $value;

            if (empty($value) === false and in_array($key, DEConstants::SENSITIVE_FIELDS_FOR_LOGGING, true) === true)
            {
                $maskedInput[$key] = mask_except_last4($value);
            }
        }

        return $maskedInput;
    }

    public function saveMerchantDetails(array $input,
                                        Merchant\Entity $merchant,
                                        string $originProduct = Product::PRIMARY)
    {
        $startTime = microtime(true);

        $this->trace->info(
            TraceCode::MERCHANT_SAVE_ACTIVATION_DETAILS,
            [
                'input'       => $this->getMaskedDataForLogging($input),
                'merchant_id' => $merchant->getId(),
            ]);

        $partnerKycFlow = $input[Activation\Constants::PARTNER_KYC_FLOW] ?? false;

        unset($input[Activation\Constants::PARTNER_KYC_FLOW]);

        $merchantDetails = $this->getMerchantDetails($merchant, $input);

        $oldMerchantDetails = clone $merchantDetails;

        $this->convertStatesToStatesCode($input);

        $merchantDetails->getValidator()->validateIsNotLocked($merchant);

        $merchantDetails->getValidator()->validatePartnerActivationStatus($merchant, $partnerKycFlow);

        $merchantDetails->getValidator()->blockInstantActivationCriticalFields($input);

        if ($merchant->isLinkedAccount() === true)
        {
            $merchantDetails->getValidator()->validateLinkedAccountBusinessNameInput(array_only($input,Entity::BUSINESS_NAME), $merchant->getParentId());
        }

        $activationFormMilestone = $input[Entity::ACTIVATION_FORM_MILESTONE] ?? null;

        //isolating business details
        $businessDetailsInput = [];

        $urlsInput           = [BusinessDetailConstants::PLAYSTORE_URL, BusinessDetailConstants::APPSTORE_URL];
        $paymentsAvenueInput = [BusinessDetailConstants::SOCIAL_MEDIA, BusinessDetailConstants::PHYSICAL_STORE,
                                BusinessDetailConstants::WEBSITE_OR_APP];

        foreach ($urlsInput as $url)
        {
            if (isset($input[$url]) === true)
            {
                $businessDetailsInput[BusinessDetailEntity::APP_URLS][$url] = $input[$url];
                unset($input[$url]);
            }
        }

        foreach ($paymentsAvenueInput as $payInput)
        {
            if (isset($input[$payInput]) === true)
            {
                $businessDetailsInput[BusinessDetailEntity::WEBSITE_DETAILS][$payInput] = $input[$payInput];
                unset($input[$payInput]);
            }
        }

        $businessDetailsInput[BusinessDetailEntity::BUSINESS_PARENT_CATEGORY] = $input[BusinessDetailEntity::BUSINESS_PARENT_CATEGORY] ?? null;
        unset($input[BusinessDetailEntity::BUSINESS_PARENT_CATEGORY]);

        if (empty($businessDetailsInput[BusinessDetailEntity::APP_URLS]) === false or
            empty($businessDetailsInput[BusinessDetailEntity::WEBSITE_DETAILS]) === false or
            empty($businessDetailsInput[BusinessDetailEntity::BUSINESS_PARENT_CATEGORY]) === false)
        {
            if (array_key_exists(BusinessDetailEntity::APP_URLS, $businessDetailsInput))
            {
                $this->checkForCorrectAppUrls($businessDetailsInput[BusinessDetailEntity::APP_URLS]);
            }

            $businessDetails = Tracer::inspan(['name' => HyperTrace::SAVE_BUSINESS_DETAILS_FOR_MERCHANT], function() use ($merchant, $businessDetailsInput) {

                //save App Urls Details
                $businessDetailService = new Service();

                return $businessDetailService->saveBusinessDetailsForMerchant($merchant->getId(), $businessDetailsInput);
            });

            $this->trace->info(TraceCode::ACTIVATION_DETAILS_SAVE_BUSINESS_DETAILS_LATENCY, [
                'merchant_id'                 => $merchant->getId(),
                'start_time'                  => $startTime * 1000,
                'overall_duration'            => (microtime(true) - $startTime) * 1000,
            ]);

            //Calling App Checker Service during onboarding
            (new HealthChecker\Core())->notifyRiskChecker(
                $merchant->getId(), HealthCheckerConstants::PERFORM_HEALTH_CHECK_JOB, [
                                      HealthCheckerConstants::RETRY_COUNT_KEY => HealthCheckerConstants::MAX_RISK_CHECK_RETRIES,
                                      HealthCheckerConstants::EVENT_TYPE      => HealthCheckerConstants::ONBOARDING_CHECKER_EVENT,
                                      HealthCheckerConstants::CHECKER_TYPE    => HealthCheckerConstants::APP_CHECKER,
                                  ]
            );

            $this->trace->info(TraceCode::ACTIVATION_DETAILS_RISK_CHECKER_LATENCY, [
                'merchant_id'                 => $merchant->getId(),
                'start_time'                  => $startTime * 1000,
                'overall_duration'            => (microtime(true) - $startTime) * 1000,
            ]);
        }

        if (empty($input['business_website']) === false)
        {
            //Calling Profanity Checker during onboarding
            (new MRS())->enqueueProfanityCheckerRequest($merchant->getId(), 'site', 'merchant', $merchant->getId(), $input['business_website'], DEConstants:: MRS_PROFANITY_CHECKER_DEPTH, Constants::MERCHANT_ONBOARDING);
        }

        unset($input[Entity::ACTIVATION_FORM_MILESTONE]);

        $this->trace->info(TraceCode::MERCHANT_SAVE_ACTIVATION_DETAILS_EDIT_REQUEST, [
            'merchant_id'                 => $merchant->getId(),
            'start_time'                  => $startTime * 1000,
            'overall_duration'            => (microtime(true) - $startTime) * 1000,
            'input'                       => $input,
        ]);

        $merchantDetails->edit($input);

        $this->trace->info(TraceCode::MERCHANT_SAVE_ACTIVATION_DETAILS_EDITED, [
            'merchant_id'                 => $merchant->getId(),
            'start_time'                  => $startTime * 1000,
            'overall_duration'            => (microtime(true) - $startTime) * 1000,
        ]);

        Tracer::inspan(['name' => HyperTrace::PERFORM_KYC_VERIFICATION], function() use ($merchantDetails, $merchant, $input) {

            $verificationStartTime = microtime(true);
            // do pan validation
            $this->verifyPOIDetailsIfApplicable($merchantDetails, $merchant, $input);

            $this->verifyCompanyPanDetailsIfApplicable($merchantDetails, $merchant, $input);

            $this->verifyGSTINIfApplicable($merchantDetails, $merchant, $input);

            $this->verifyShopEstbNumberIfApplicable($merchantDetails, $merchant, $input);

            $this->verifyCINDetailsIfApplicable($merchantDetails, $merchant, $input);

            $this->attemptPennyTesting($merchantDetails, $merchant, false, $input);

            $this->triggerSyncValidationRequests($merchant, $merchantDetails);

            $this->trace->info(TraceCode::MERCHANT_KYC_VERIFICATION_LATENCY, [
                'merchant_id' => $merchant->getId(),
                'duration'    => (microtime(true) - $verificationStartTime) * 1000,
                'start_time'  => $verificationStartTime
            ]);
        });

        return $this->mutex->acquireAndRelease(
            $merchant->getId(),
            function() use ($input, $merchantDetails, $merchant, $originProduct, $oldMerchantDetails, $activationFormMilestone, $startTime) {

                return $this->repo->transactionOnLiveAndTest(function() use (
                    $input,
                    $merchantDetails,
                    $merchant,
                    $originProduct,
                    $oldMerchantDetails,
                    $activationFormMilestone,
                    $startTime
                ) {

                    $startTimePostAcquiringMutexLock = microtime(true);

                    $this->repo->merchant->lockForUpdate($merchant->getId());

                    $this->repo->merchant_detail->lockForUpdate($merchantDetails->getId());

                    $this->trace->info(TraceCode::MERCHANT_DB_LOCK_ACQUIRE_LATENCY, [
                        'acquired'                => true,
                        'dblock_acquire_duration' => (microtime(true) - $startTimePostAcquiringMutexLock) * 1000,
                        'merchant_id'             => $merchant->getId()
                    ]);

                    $merchantDetails = Tracer::inspan(['name' => HyperTrace::EDIT_MERCHANT_DETAIL_FIELDS], function() use ($merchant, $input) {

                        return $this->editMerchantDetailFields($merchant, $input);
                    });

                    $oldActivationStatus = $merchantDetails->getActivationStatus();

                    $response = $this->createResponse($merchantDetails);

                    $this->trace->info(TraceCode::MERCHANT_SAVE_ACTIVATION_DETAILS_RESPONSE, [
                        'merchant_id'                 => $merchant->getId(),
                        'start_time'                  => $startTime * 1000,
                        'overall_duration'            => (microtime(true) - $startTime) * 1000,
                        'response'                    => $response,
                    ]);

                    if ($this->canSubmit($input, $response, $activationFormMilestone) === true)
                    {
                        $this->validateEmailVerificationIfApplicable($merchant);

                        // blacklisted merchant should not be allowed to submit l2 form
                        $merchantDetails->getValidator()->validateFullActivationForm($merchant);

                        $response = Tracer::inspan(['name' => HyperTrace::SUBMIT_ACTIVATION_FORM], function() use ($merchant, $input, $originProduct) {

                            return $this->submitActivationForm($merchant, $input, $originProduct);
                        });

                        // If activation status changes to under_review and previous activation status is
                        // Needs Clarification, then it means merchant has responded to Needs Clarification.
                        // If merchant is NC responded then we want to trigger activation workflow
                        if ($this->isNcResponded($oldActivationStatus, $merchantDetails->getActivationStatus()))
                        {
                            $this->triggerNeedsClarificationRespondedWorkflow($merchant, $oldMerchantDetails);
                        }
                    }
                    else
                    {
                        $response = $this->updateActivationProgress($merchant);
                    }

                    $this->trace->info(TraceCode::MERCHANT_SAVE_ACTIVATION_DETAILS_LATENCY, [
                        'merchant_id'                 => $merchant->getId(),
                        'start_time'                  => $startTime * 1000,
                        'duration_after_lock_acquire' => (microtime(true) - $startTimePostAcquiringMutexLock) * 1000,
                        'overall_duration'            => (microtime(true) - $startTime) * 1000,
                    ]);

                    return $response;
                });
            },
            Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
            Constants::MERCHANT_MUTEX_RETRY_COUNT);
    }

    /**
     * @param        $statusChangeLogs
     * @param string $status
     *
     * @return int
     */
    public function getStatusChangeCount($statusChangeLogs, string $status)
    {
        $count = 0;

        foreach ($statusChangeLogs as $statusData)
        {
            if ($statusData[State\Entity::NAME] === $status)
            {
                $count++;
            }
        }

        return $count;
    }

    private function triggerNeedsClarificationRespondedWorkflow($merchant, $oldMerchantDetails)
    {
        $statusChangeLogs = (new Merchant\Core)->getActivationStatusChangeLog($merchant);

        // agent who marked NC will be the maker of activation workflow
        $maker = $this->getNcMarkedAgent($statusChangeLogs);

        if (empty($maker))
        {
            return;
        }

        $tags = $this->getNcRespondedTags($merchant->merchantDetail, $statusChangeLogs, $maker);

        $input = [Entity::ACTIVATION_STATUS => Status::ACTIVATED];

        // The reason routeName and Controller is set here because
        // the workflow being triggered is associated with the different route.
        $this->app['workflow']
            ->setPermission(Permission\Name::NEEDS_CLARIFICATION_RESPONDED)
            ->setRouteName(DetailConstants::ACTIVATION_ROUTE_NAME)
            ->setController(DetailConstants::ACTIVATION_CONTROLLER)
            ->setWorkflowMaker($maker)
            ->setMakerFromAuth(false)
            ->setTags($tags)
            ->setRouteParams([Entity::ID => $merchant->getId()])
            ->setInput($input)
            ->setEntity($merchant->merchantDetail->getEntity())
            ->setOriginal($oldMerchantDetails)
            ->setDirty($merchant->merchantDetail);

        try
        {
            $this->app['workflow']->handle();
        }
        catch (Exception\EarlyWorkflowResponse $e)
        {
            // Catching exception because we do not want to abort the code flow
            $workflowActionData = json_decode($e->getMessage(), true);
            $this->app['workflow']->saveActionIfTransactionFailed($workflowActionData);
        }
    }

    /**
     * Method that returns whether merchant has responded on penny testing failure
     * or Manual NC
     *
     * @param string $ncrCountTag
     * @param        $merchant
     *
     * @return bool
     */
    protected function isNcrOnPennyTesting(string $ncrCountTag, $merchantDetails)
    {
        if ($ncrCountTag === 'NCR1')
        {
            $bankDetailsVerificationStatus = $merchantDetails->getBankDetailsVerificationStatus();

            return ($bankDetailsVerificationStatus !== BankDetailsVerificationStatus::VERIFIED);
        }

        return false;
    }

    protected function getNcRespondedTags($merchantDetails, $statusChangeLogs, $maker)
    {
        $ncrCountTag = $this->getNcRespondedCountTag($statusChangeLogs);
        $tags        = [
            $ncrCountTag,
            $this->getNcMarkedAgentTag($maker)
        ];

        if ($this->isNcrOnPennyTesting($ncrCountTag, $merchantDetails))
        {
            $tags[] = "Auto NC";
        }

        if ($this->canAddAutoKycTag($merchantDetails))
        {
            $tags[] = "auto-kyc";
        }

        return $tags;
    }

    protected function canAddAutoKycTag($merchantDetails)
    {
        $autoKyc       = $this->isAutoKycDone($merchantDetails);
        $isWhitelisted = ($merchantDetails->getActivationFlow() === ActivationFlow::WHITELIST);

        return ($autoKyc === true and $isWhitelisted === true);
    }

    protected function isNcResponded($oldActivationStatus, $newActivationStatus)
    {
        return (
            $oldActivationStatus === Status::NEEDS_CLARIFICATION and
            $newActivationStatus === Status::UNDER_REVIEW
        );
    }

    protected function getNcRespondedCountTag($statusChangeLogs)
    {
        $count   = 0;
        $ncFound = false;
        foreach ($statusChangeLogs as $statusData)
        {
            if ($statusData[State\Entity::NAME] === Status::NEEDS_CLARIFICATION)
            {
                $ncFound = true;
                continue;
            }
            if ($statusData[State\Entity::NAME] === Status::UNDER_REVIEW and $ncFound)
            {
                $count++;
                $ncFound = false;
            }
        }

        if ($count >= 3)
        {
            return "NCR3_greater";
        }

        return "NCR" . $count;
    }

    protected function getNcMarkedAgent($statusChangeLogs)
    {
        $adminId = null;
        foreach ($statusChangeLogs as $statusData)
        {
            if ($statusData[State\Entity::NAME] === Status::NEEDS_CLARIFICATION)
            {
                $adminId = $statusData[State\Entity::ADMIN_ID];    // get the latest admin who marked NC
            }
        }

        if (!empty($adminId))
        {
            return $this->repo->admin->findOrFailPublic($adminId);
        }

        return null;
    }

    protected function getNcMarkedAgentTag($maker)
    {
        return "NCR_" . $maker->getName();
    }

    protected function verifyAadhaarWithPanIfApplicable(Merchant\Entity $merchant, Entity $merchantDetails)
    {
        if ($merchant->getOrgId() !== Org\Entity::RAZORPAY_ORG_ID)
        {
            return;
        }

        $businessType = $merchantDetails->getBusinessType();

        // If aadhaar esign is not required then, aadhar with pan is also not required
        if (BusinessType::isAadhaarEsignVerificationRequired($businessType) === false)
        {
            return;
        }

        $stakeholder = $merchantDetails->stakeholder;

        if (empty($stakeholder) === true)
        {
            return;
        }

        if (empty($stakeholder->getBvsProbeId()) === true)
        {
            return;
        }

        // If already verified then skip it
        if ($stakeholder->getAadhaarVerificationWithPanStatus() === 'verified')
        {
            return;
        }

        $this->validateAadhaarWithPan($merchant, $stakeholder->getBvsProbeId());
    }

    public function validateAadhaarWithPan($merchant,$probeId){

        $payload = [
            Constant::ARTEFACT_TYPE   => Constant::AADHAAR,
            Constant::CONFIG_NAME     => Constant::AADHAAR_WITH_PAN,
            Constant::VALIDATION_UNIT => BvsValidationConstants::IDENTIFIER,
            Constant::PROBE_ID        => $probeId,
            Constant::DETAILS         => [
                Constant::NAME => $merchant->merchantDetail->getPromoterPanName(),
            ],
        ];

        $bvsValidation = (new AutoKyc\Bvs\Core($merchant, $merchant->merchantDetail))->verify($merchant->merchantDetail->getId(), $payload);

    }

    public function canActivateMerchant(DetailEntity $merchantDetails, $isRiskyMerchant)
    {
        $activationFormMilestone = $merchantDetails->getActivationFormMilestone();

        $activationFlow = $merchantDetails->getActivationFlow();

        if (empty($activationFlow) === true and
            $merchantDetails->canDetermineActivationFlow() === true)
        {
            $activationFlow = $this->getActivationFlow(
                $merchantDetails->merchant, $merchantDetails, null, false);
        }

        if ($isRiskyMerchant === true)
        {
            return false;
        }

        if ($activationFlow !== ActivationFlow::WHITELIST)
        {
            return false;
        }

        if ($merchantDetails->isUnregisteredBusiness() === true)
        {
            if (($merchantDetails->isPoiVerified() === true) and
                ($activationFormMilestone === DetailConstants::L1_SUBMISSION))
            {
                return true;
            }

            return false;
        }
        else
        {
            if ($activationFormMilestone === DetailConstants::L1_SUBMISSION)
            {
                return true;
            }

            return false;
        }
    }

    public function submitActivationForm(Merchant\Entity $merchant, array $input = null, string $originProduct = Product::PRIMARY)
    {
        $startTime = microtime(true);

        $this->repo->assertTransactionActive();

        $merchantDetails = $this->getMerchantDetails($merchant);

        [$isRiskyMerchant, $action] = $this->dedupeCore->match($merchant);

        $this->trace->info(TraceCode::MERCHANT_FORM_DEDUPE_MATCH, [
            'merchant_id'     => $merchant->getId(),
            'duration'        => (microtime(true) - $startTime) * 1000,
            'start_time'      => $startTime * 1000,
            'isRiskyMerchant' => $isRiskyMerchant,
            'action'          => $action,
        ]);

        if ($this->canActivateMerchant($merchantDetails, $isRiskyMerchant) === true)
        {
            $merchant->activate();

            $this->repo->saveOrFail($merchant);
        }

        $merchantDetails->setActivationFormMilestone(DetailConstants::L2_SUBMISSION);

        $this->autoUpdateMerchantActivationFlows(
            $merchant, $merchantDetails, null, [Detail\Constants::INTERNATIONAL_ACTIVATION], false);

        $statusToBeUpdated = $this->getApplicableActivationStatus($merchantDetails);

        if ($isRiskyMerchant === true)
        {
            $this->handleFlowForRiskyMerchant($merchant, $merchantDetails, $action);
        }

        // If a merchant does not have website or app, we would need to activate them
        // only with PLs, Invoices and should not get API keys in live mode. Merchant's has_key_access
        // should be set to true only if one submits website details, there by will be able to
        // generate/access keys.
        $this->checkAndMarkHasKeyAccess($merchantDetails, $merchant);

        $this->markSubmittedAndLock($merchantDetails);

        $this->initializeValidationDetailsForNoDocOnboarding($merchantDetails);

        $this->updateActivationSource($merchant, $originProduct);

        $this->verifyAadhaarWithPanIfApplicable($merchant, $merchantDetails);

        if ($statusToBeUpdated != null)
        {
            $activationStatusData = [
                Entity::ACTIVATION_STATUS => $statusToBeUpdated,
            ];

            $this->updateActivationStatus($merchant, $activationStatusData, $merchant);
        }

        $eventAttributes = $merchant->toArrayEvent();

        $this->app['eventManager']->trackEvents($merchant, Merchant\Action::SUBMITTED, $eventAttributes);

        $this->app['segment-analytics']->pushTrackEvent($merchant, [], SegmentEvent::L2_SUBMISSION);

        $this->triggerValidationRequests($merchant, $merchantDetails);

        $this->fireActivationTrigger($merchantDetails, $merchant);

        $autoActivated = $this->autoActivateMerchantIfApplicable($merchant);

        $response = $this->updateActivationProgress($merchant);

        $response['auto_activated'] = $autoActivated;

        $this->repo->saveOrFail($merchantDetails);

        $this->submitPartnerActivationFormIfApplicable($merchant, $input);

        $this->trace->info(TraceCode::MERCHANT_FORM_SUBMIT_LATENCY, [
            'merchant_id' => $merchant->getId(),
            'duration'    => (microtime(true) - $startTime) * 1000,
            'start_time'  => $startTime * 1000
        ]);

        return $response;
    }

    public function initializeValidationDetailsForNoDocOnboarding(Entity $merchantDetails): ?array
    {
        if ($merchantDetails->merchant->isNoDocOnboardingEnabled() === false)
        {
            return null;
        }

        $gst = $merchantDetails->getGstin();

        $noDocData = [
            'gst'           => empty($gst) ? [] : [$gst],
            'current_index' => 0,
        ];

        $data = [
            ConfigKey::NO_DOC_ONBOARDING_INFO => $noDocData,
            StoreConstants::NAMESPACE         => ConfigKey::ONBOARDING_NAMESPACE
        ];

        (new StoreCore())->updateMerchantStore($merchantDetails->getMerchantId(), $data, StoreConstants::INTERNAL);

        return $noDocData;
    }

    public function updateActivationProgress(Merchant\Entity $merchant): array
    {
        $merchantDetails = $this->getMerchantDetails($merchant);

        $response = $this->createResponse($merchantDetails);

        $activationProgress = $response['verification']['activation_progress'];

        $merchantDetails->setActivationProgress($activationProgress);

        $this->repo->saveOrFail($merchantDetails);

        $eventAttributes = $merchant->toArrayEvent();

        $eventAttributes['activation_progress'] = $response['verification']['activation_progress'];

        $this->app['eventManager']->trackEvents($merchant, Merchant\Action::ACTIVATION_PROGRESS, $eventAttributes);

        return $response;
    }

    public function updateLegalEntity(array $input, Merchant\Entity $merchant)
    {
        $legalEntityInput = [];

        if (isset($input[Entity::BUSINESS_TYPE]) === true)
        {
            $legalEntityInput[LegalEntity\Entity::BUSINESS_TYPE] = $input[Entity::BUSINESS_TYPE];
        }

        if (isset($input[Entity::BUSINESS_CATEGORY]) === true)
        {
            $legalEntityInput[LegalEntity\Entity::MCC]               = $merchant->getCategory();
            $legalEntityInput[LegalEntity\Entity::BUSINESS_CATEGORY] = $input[Entity::BUSINESS_CATEGORY];

            if (isset($input[Entity::BUSINESS_SUBCATEGORY]) === true)
            {
                $legalEntityInput[LegalEntity\Entity::BUSINESS_SUBCATEGORY] = $input[Entity::BUSINESS_SUBCATEGORY];
            }
        }

        if (isset($input[Merchant\Entity::CATEGORY]) === true)
        {
            $legalEntityInput[LegalEntity\Entity::MCC] = $input[Merchant\Entity::CATEGORY];
        }

        if (empty($legalEntityInput) === true)
        {
            return;
        }

        (new Merchant\Core)->upsertLegalEntity($merchant, $legalEntityInput);
    }

    /**
     * fetches activation_flow and international_activation value
     * using business category and subcategory, then updates in
     * merchant_details table.
     *
     * For unregistered business bucket we skip activation flow
     *
     * @param Merchant\Entity      $merchant
     * @param Entity|null          $merchantDetails
     * @param Merchant\Entity|null $partner
     * @param array|string[]       $activationFlowTypes
     * @param bool                 $batchFlow
     */
    public function autoUpdateMerchantActivationFlows(Merchant\Entity $merchant,
                                                      Merchant\Detail\Entity $merchantDetails = null,
                                                      Merchant\Entity $partner = null,
                                                      array $activationFlowTypes = Detail\Constants::ACTIVATION_FLOWS,
                                                      bool $batchFlow = false
    )
    {
        $this->repo->assertTransactionActive();

        $merchantDetails = $merchantDetails ?: $this->getMerchantDetails($merchant);

        if ((new Merchant\Core)->isUnRegisteredOnBoardingEnabled($merchant,
                                                                 $merchantDetails->isUnregisteredBusiness()) === true)
        {
            $merchantDetails->setActivationFlow();
            $merchantDetails->setInternationalActivationFlow();

            return;
        }

        $this->updateActivationFlows($merchant, $merchantDetails, $partner, $activationFlowTypes, $batchFlow);

        $eventAttributes['activation_flow'] = $merchantDetails->getActivationFlow();

        $internationalActivationFlow = $merchantDetails->getInternationalActivationFlow();

        if (empty($internationalActivationFlow) === false)
        {
            $eventAttributes['international_activation_flow'] = $merchantDetails->getInternationalActivationFlow();
        }

        $this->app['diag']->trackOnboardingEvent(EventCode::ACT_CHANGE_ACTIVATION_FLOW_SUCCESS,
                                                 $this->merchant,
                                                 null,
                                                 $eventAttributes);
    }

    protected function autoUpdateActivationFlow(
        Merchant\Entity $merchant, Merchant\Detail\Entity $merchantDetails, $partner = null, bool $batchFlow = false)
    {
        $activationFlow = $this->getActivationFlow($merchant, $merchantDetails, $partner, $batchFlow);

        $merchantDetails->setActivationFlow($activationFlow);

        $activation_metric_dimensions = $this->fetchActivationMetricDimensions($activationFlow);

        $this->trace->count(Metric::MERCHANT_ACTIVATION, $activation_metric_dimensions);
    }

    protected function autoUpdateInternationalActivationFlow(Merchant\Entity $merchant, $partner = null)
    {
        $merchantDetails = $this->getMerchantDetails($merchant);

        $autoEnableInternational = (new Merchant\Core)->autoEnableInternational($merchant, $merchantDetails);

        if ($autoEnableInternational === false)
        {
            return;
        }

        $internationalActivationFlow = (new Detail\InternationalCore)->getInternationalActivationFlow($merchant, $partner);

        $merchantDetails->setInternationalActivationFlow($internationalActivationFlow);

        $international_activation_metric_dimensions = $this->fetchActivationMetricDimensions($internationalActivationFlow);

        $this->trace->count(Metric::INTERNATIONAL_MERCHANT_ACTIVATION, $international_activation_metric_dimensions);
    }

    /**
     * on business category or subcategory change updates merchant category and category2 data
     *
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     *
     * @throws \RZP\Exception\BadRequestException
     */
    public function autoUpdateMerchantCategoryDetailsIfApplicable(
        Entity $merchantDetails,
        Merchant\Entity $merchant, $shouldResetMethods = false)
    {
        $businessCategory    = $merchantDetails->getBusinessCategory();
        $businessSubcategory = $merchantDetails->getBusinessSubcategory();

        $category  = $merchant->getCategory();
        $category2 = $merchant->getCategory2();

        // for older merchants(non instant activation) where category or category 2 is not set , set details
        $populateCategoryAndCategory2 = ((empty($businessCategory) === false) and
                                         (!(empty($category) === false and empty($category2) === false)));

        if (($populateCategoryAndCategory2 === true) or
            ($merchantDetails->isDirty([Entity::BUSINESS_CATEGORY, Entity::BUSINESS_SUBCATEGORY]) === true))
        {
            (new Merchant\Core)->autoUpdateCategoryDetails($merchant, $businessCategory, $businessSubcategory, $shouldResetMethods);
        }
    }

    /**
     * This function is used for Not Registered Onboarding flow where there is need to set Default Volume/Department
     * in MerchantDetails table. The reason for doing so is if Business type is changed from Non registered
     * to some other business type, then need to skip pre signup form from Dashboard login.
     *
     * @param Entity $merchantDetails
     */
    public function updateToDefaultDepartmentVolumeIfApplicable(Entity &$merchantDetails)
    {
        if ($merchantDetails->isDirty([Entity::BUSINESS_TYPE]) === true)
        {
            $oldMerchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantDetails->getMerchantId());

            if (BusinessType::isUnregisteredBusiness($oldMerchantDetail->getBusinessType()))
            {
                $merchantDetails->setAttribute(Entity::TRANSACTION_VOLUME, TransactionVolume::getDefaultVolume());

                $merchantDetails->setAttribute(Entity::DEPARTMENT, Department::getDefaultDepartment());
            }
        }
    }

    /**
     * Saves the instant activation details and also instantly activates the merchant based on the business details.
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return array
     * @throws \Throwable
     */
    public function saveInstantActivationDetails(array $input, Merchant\Entity $merchant): array
    {
        $this->trace->info(
            TraceCode::MERCHANT_SAVE_INSTANT_ACTIVATION_DETAILS,
            [
                'input' => $input,
            ]);

        $startTime = microtime(true);

        $merchantDetails = $this->getMerchantDetails($merchant, $input);

        $this->convertStatesToStatesCode($input);

        //added after introducing activation_form_milestone and separation of L1 and L2
        $requiredInputFields = [
            Entity::BUSINESS_CATEGORY,
            Entity::PROMOTER_PAN,
            Entity::PROMOTER_PAN_NAME,
            Entity::BUSINESS_DBA,
            Entity::BUSINESS_TYPE,
            Entity::BUSINESS_NAME,
        ];

        foreach ($requiredInputFields as $field)
        {
            if ((empty($input[$field]) === true) and
                (empty($merchantDetails->getAttribute($field)) === false))
            {
                $input[$field] = $merchantDetails->getAttribute($field);
            }
        }

        $merchantDetails->getValidator()->performInstantActivationValidations($input);

        $merchantDetails->getValidator()->validatePartnerActivationStatus($merchant);

        unset($input[Entity::ACTIVATION_FORM_MILESTONE]);

        $merchantDetails->edit($input, 'instant_activation');

        // dual write promoter related fields to stakeholder entity
        (new Stakeholder\Core)->syncMerchantDetailFieldsToStakeholder($merchantDetails, $input);

        //
        // do pan validation
        //
        $this->verifyPOIDetailsIfApplicable($merchantDetails, $merchant, $input);

        //
        // do business pan validation
        //
        $this->verifyCompanyPanDetailsIfApplicable($merchantDetails, $merchant, $input);

        //
        // do cin validation
        //
        $this->verifyCINDetailsIfApplicable($merchantDetails, $merchant, $input);

        $this->triggerSyncValidationRequests($merchant, $merchantDetails);

        $this->validateEmailVerificationIfApplicable($merchant);

        $response = $this->transactionInstantActivationDetails($input, $merchantDetails, $merchant);

        $this->trace->info(TraceCode::MERCHANT_SAVE_INSTANT_ACTIVATION_DETAILS_LATENCY, [
            'duration'    => (microtime(true) - $startTime) * 1000,
        ]);

        return $response;
    }

    protected function validateEmailVerificationIfApplicable(Merchant\Entity $merchant)
    {
        $user = $this->app['basicauth']->getUser();

        /*
         * If auth is not merchant auth then we'll skip this
         */
        if (empty($merchant->getEmail()) === true)
        {
            return;
        }

        if (empty($user) === true)
        {
            return;
        }

        if ($merchant->getOrgId() !== Org\Entity::RAZORPAY_ORG_ID)
        {
            return;
        }

        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $partnerCore = (new PartnerCore());

        if ($partnerCore->isFullyManagedSubMerchant($merchant) === true)
        {
            return;
        }

        $isSignedUpViaEmail = (bool) $user->getAttribute(UserEntity::SIGNUP_VIA_EMAIL);

        if ($isSignedUpViaEmail === true)
        {
            return;
        }

        if ($user->getConfirmedAttribute() === false)
        {
            throw new Exception\BadRequestValidationFailureException('Email is not verified');
        }
    }

    /**
     * This function make all the DB transaction for saving all Instant Activation Details
     *
     * @param array           $input
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     * @param bool            $batchFlow
     * @param bool            $sendActivationMail
     *
     * @return mixed
     * @throws \Throwable
     */
    public function transactionInstantActivationDetails(array $input, Merchant\Detail\Entity $merchantDetails,
                                                        Merchant\Entity $merchant, bool $batchFlow = false,
                                                        bool $sendActivationMail = true)
    {

        $response = $this->mutex->acquireAndRelease(
            $merchant->getId(),
            function() use ($input, $merchantDetails, $merchant, $batchFlow, $sendActivationMail) {

                return $this->repo->transactionOnLiveAndTest(function() use ($input, $merchantDetails, $merchant, $batchFlow, $sendActivationMail) {
                    // The function below, uses isDirty() and hence must be called before saveOrFail over merchantDetails
                    $this->autoUpdateMerchantCategoryDetailsIfApplicable($merchantDetails, $merchant);

                    $this->updateToDefaultDepartmentVolumeIfApplicable($merchantDetails);

                    $this->updateLegalEntity($input, $merchant);

                    $merchantDetails->setActivationFormMilestone(DetailConstants::L1_SUBMISSION);

                    $this->repo->saveOrFail($merchantDetails);

                    $merchantCore = new Merchant\Core();
                    // Sync few input fields to merchant entity
                    $merchant = $merchantCore->syncMerchantEntityFields($merchant, $input);

                    // IN Batch Flow we skip the merchant category sub category check for grey list or blacklist
                    // As desired by the use case
                    if ($batchFlow === true)
                    {
                        $this->processInstantActivationBatch($merchant, $batchFlow);
                    }
                    else
                    {
                        $this->processInstantActivation($merchant, $merchantDetails);
                    }

                    $this->triggerValidationRequests($merchant, $merchantDetails, DetailConstants::L1_SUBMISSION);

                    $response = $this->createResponse($merchantDetails);

                    // used to show the progress of the activation form on the dashboard
                    $activationProgress = $response['verification']['activation_progress'];
                    $merchantDetails->setActivationProgress($activationProgress);

                    $this->repo->saveOrFail($merchantDetails);

                    return $response;
                });
            },
            Constants::MERCHANT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
            Constants::MERCHANT_MUTEX_RETRY_COUNT);

        $activationProgress = $response['verification']['activation_progress'];

        $this->trackActivationProgressEvents($merchant, $activationProgress);

        $this->app->hubspot->trackL1ContactProperties($input, $merchant, $merchantDetails->getActivationFlow());

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent($merchant, [], SegmentEvent::L1_SUBMISSION);

        // Only Linked accounts will have auto Activated set to true.
        $response['auto_activated'] = false;

        return $response;
    }

    public function saveInstantActivationDetailsBatch(array $input, Merchant\Entity $merchant): array
    {
        $this->trace->info(
            TraceCode::MERCHANT_SAVE_INSTANT_ACTIVATION_DETAILS,
            [
                'input' => $input,
            ]);

        $sendActivationMail = filter_var($input["send_activation_email"], FILTER_VALIDATE_BOOLEAN);

        $batchFlow = true;

        unset($input['send_activation_email']);

        $merchantDetails = $this->getMerchantDetails($merchant, $input);

        $merchantDetails->getValidator()->performInstantActivationValidationsBatch($input);

        $merchantDetails->edit($input, 'instant_activation_batch');

        // dual write promoter related fields to stakeholder entity
        (new Stakeholder\Core)->syncMerchantDetailFieldsToStakeholder($merchantDetails, $input);

        return $this->transactionInstantActivationDetails($input, $merchantDetails, $merchant, $batchFlow, $sendActivationMail);
    }

    public function isRasSignupFraudMerchant($merchantId): bool
    {
        return (empty($this->app['cache']->connection()->hget(
                MerchantRiskAlert\Constants::REDIS_DEDUPE_SIGNUP_CHECKER_MAP, $merchantId))
                === false);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetails
     *
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\LogicException
     */
    protected function processInstantActivation(Merchant\Entity $merchant, Entity $merchantDetails)
    {
        [$isRiskyMerchant, $action] = (new DeDupe\Core)->match($merchant);

        if ($isRiskyMerchant === true)
        {
            $this->handleFlowForRiskyMerchant($merchant, $merchantDetails, $action);
        }

        $this->autoUpdateMerchantActivationFlows($merchant, $merchantDetails);

        if ($isRiskyMerchant === true)
        {
            return;
        }

        if ($merchantDetails->isUnregisteredBusiness() === true)
        {
            $isAutoKycEnabled = (new Merchant\Core)->isAutoKycEnabled($merchantDetails, $merchant);

            $canProcessInstantActivation = $this->canProcessInstantActivation($merchantDetails);

            if (($isAutoKycEnabled === true) and
                ($canProcessInstantActivation === true))
            {
                // in case of unregistered business if pan is verified then instantly activate merchant
                (new Detail\ActivationFlow\Whitelist())->process($merchant);
            }
        }
        else
        {
            // $activationFlow will be an instance of the ActivationFlowInterface
            $activationFlow = ActivationFlow\Factory::getActivationFlowImpl($merchantDetails);

            $activationFlow->process($merchant);
        }
    }

    protected function handleFlowForRiskyMerchant(Merchant\Entity $merchant, Entity $merchantDetails, $action)
    {
        if ($merchantDetails->getActivationFormMilestone() !== DetailConstants::L1_SUBMISSION)
        {
            if ($action !== DeDupe\Constants::RAS_SIGNUP_LOCK)
            {
                $this->triggerWorkflowFlowForImpersonatedMerchant($merchant, $merchantDetails);
            }

            if (empty($action) === false)
            {
                switch ($action)
                {
                    case DeDupe\Constants::DEACTIVATE:
                    case DeDupe\Constants::RAS_SIGNUP_LOCK:
                        $merchant->merchantDetail->setLocked(true);
                        break;

                    case DeDupe\Constants::UNREG_DEACTIVATE:
                        if ($merchantDetails->isUnregisteredBusiness() === true)
                        {
                            $merchant->merchantDetail->setLocked(true);
                        }
                        break;
                }
            }
        }

        if ($action === DeDupe\Constants::RAS_SIGNUP_LOCK)
        {
            (new Merchant\Core)->appendTag($merchant, 'risk_review_suspend');

            $merchant->merchantDetail->setFraudType('risk_review_suspend_tag');
        }
        else
        {
            $merchant->deactivate();
        }

        $dedupeTag = $this->dedupeCore->getDedupeTagForAction($merchantDetails, $action);

        if (empty($dedupeTag) === false)
        {
            (new Merchant\Core)->appendTag($merchant, $dedupeTag);
        }

        $eventAttributes = [
            'dedupe'                                 => true,
            DeDupe\Constants::ACTION                 => $action,
            Detail\Entity::ACTIVATION_FORM_MILESTONE => $merchantDetails->getActivationFormMilestone()
        ];

        $this->app['diag']->trackOnboardingEvent(EventCode::MERCHANT_DEDUPE, $merchant, null, $eventAttributes);

        $properties = [
            'dedupe_status'    => $dedupeTag,
            'dedupe_timestamp' => Carbon::now()->getTimestamp(),
            'fields'           => $this->dedupeCore->getDedupeMatchedFields($merchant)
        ];

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $properties, SegmentEvent::DEDUPE);
    }

    protected function triggerWorkflowFlowForImpersonatedMerchant(Merchant\Entity $merchant, Entity $merchantDetails)
    {
        $actions = (new ActionCore)->fetchOpenActionOnEntityOperationWithPermissionList(
            $merchant->getId(), 'merchant_detail', [Permission\Name::IMPERSONATING_MERCHANT_DEDUPE]);
        $actions = $actions->toArray();

        if (empty($actions) === false)
        {
            // If a workflow is already created, then do not create the same workflow;
            return;
        }

        $this->app['workflow']
            ->setPermission(Permission\Name::IMPERSONATING_MERCHANT_DEDUPE)
            ->setRouteName(DEConstants::ACTIVATION_ROUTE_NAME)
            ->setController(DEConstants::ACTIVATION_CONTROLLER)
            ->setWorkflowMaker($merchant)
            ->setWorkflowMakerType(MakerType::MERCHANT)
            ->setRouteParams([DetailEntity::ID => $merchant->getId()])
            ->setInput([])
            ->setEntity($merchant->merchantDetail->getEntity())
            ->setOriginal([])
            ->setDirty($merchantDetails);

        try
        {
            $this->app['workflow']->handle();
        }
        catch (Exception\EarlyWorkflowResponse $e)
        {
            // Catching exception because we do not want to abort the code flow
            $workflowActionData = json_decode($e->getMessage(), true);
            $this->app['workflow']->saveActionIfTransactionFailed($workflowActionData);
        }
    }

    /**
     * Bypassing all the validation black list or greylist Merchants
     *
     * @param Merchant\Entity $merchant
     * @param bool            $sendActivationMail
     * @param bool            $batchFlow
     *
     * @throws LogicException
     * @throws \RZP\Exception\BadRequestException
     * @throws \Throwable
     */
    protected function processInstantActivationBatch(Merchant\Entity $merchant, bool $batchFlow = true)
    {
        $merchantDetails = $merchant->merchantDetail;

        $this->autoUpdateMerchantActivationFlows(
            $merchant, $merchantDetails, null, Detail\Constants::ACTIVATION_FLOWS, $batchFlow);

        $this->trace->info(TraceCode::MERCHANT_PROCESS_WHITELIST_ACTIVATION);

        (new Merchant\Activate)->instantlyActivate($merchant, $merchantDetails, $batchFlow);
    }

    /**
     * Contains preconditions for Processing Instant activation
     *
     * @param Entity $merchantDetails
     *
     * @return bool
     */
    protected function canProcessInstantActivation(Entity $merchantDetails): bool
    {
        switch ($merchantDetails->getBusinessType())
        {
            case BusinessType::NOT_YET_REGISTERED:
            case BusinessType::INDIVIDUAL:
                return $merchantDetails->isPoiVerified();

            default :
                return true;
        }
    }


    /**
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     * @param array           $input
     */
    protected function verifyPOIDetailsIfApplicable(Entity $merchantDetails, Merchant\Entity $merchant, array $input)
    {

        if ((new Merchant\Core())->isAutoKycEnabled($merchantDetails, $merchant) === false)
        {
            $merchantDetails->setPoiVerificationStatus(null);

            $exists = (new Stakeholder\Core)->checkIfStakeholderExists($merchantDetails);
            if ($exists === true)
            {
                $merchantDetails->stakeholder->setPoiStatus(null);
            }

            return;
        }

        $dependentFields = [Detail\Entity::PROMOTER_PAN, Detail\Entity::PROMOTER_PAN_NAME, Detail\Entity::BUSINESS_TYPE];

        $requiredFields = [Detail\Entity::PROMOTER_PAN, Detail\Entity::PROMOTER_PAN_NAME];

        $isAutoKycAttemptRequired = $this->isAutoKycAttemptRequired(
            $dependentFields,
            $requiredFields,
            $input,
            Entity::POI_VERIFICATION_STATUS,
            [POIStatus::FAILED],
            $merchant->getId());

        if ($isAutoKycAttemptRequired === false)
        {
            return;
        }

        $this->updateDocumentVerificationStatus(
            $merchant, $merchantDetails, Constant::PERSONAL_PAN, BvsValidationConstants::IDENTIFIER);

        $eventAttributes = [
            'time_stamp'    => Carbon::now()->getTimestamp(),
            'artefact_type' => 'personal_pan'
        ];

        $this->app['segment-analytics']->pushTrackEvent($merchant, $eventAttributes, SegmentEvent::RETRY_INPUT_ACTIVATION_FORM);
    }

    /**
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     * @param array           $input
     */
    protected function verifyCompanyPanDetailsIfApplicable(Entity $merchantDetails, Merchant\Entity $merchant, array $input)
    {
        // For handling business type switch
        if (BusinessType::isCompanyPanEnableBusinessTypes($merchantDetails->getBusinessTypeValue()) === false and
            $merchant->isNoDocOnboardingEnabled() === false)
        {
            $merchantDetails->setCompanyPanVerificationStatus(null);

            return;
        }

        if ((new Merchant\Core())->isAutoKycEnabled($merchantDetails, $merchant) === false)
        {
            $merchantDetails->setCompanyPanVerificationStatus(null);

            return;
        }

        $dependentFields = [Detail\Entity::COMPANY_PAN, Detail\Entity::BUSINESS_NAME];

        $isAutoKycAttemptRequired = $this->isAutoKycAttemptRequired(
            $dependentFields,
            $dependentFields,
            $input,
            Entity::COMPANY_PAN_VERIFICATION_STATUS,
            [CompanyPanStatus::FAILED],
            $merchant->getId());

        if ($isAutoKycAttemptRequired === false)
        {
            return;
        }

        $this->updateDocumentVerificationStatus($merchant, $merchantDetails, Constant::BUSINESS_PAN);

        $eventAttributes = [
            'time_stamp'    => Carbon::now()->getTimestamp(),
            'artefact_type' => 'business_pan'
        ];

        $this->app['segment-analytics']->pushTrackEvent($merchant, $eventAttributes, SegmentEvent::RETRY_INPUT_ACTIVATION_FORM);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param                 $activationProgress
     */
    protected function trackActivationProgressEvents(Merchant\Entity $merchant, $activationProgress)
    {
        $eventAttributes = $merchant->toArrayEvent();

        $merchantDetail = $merchant->merchantDetail;

        $eventAttributes['activation_progress'] = $activationProgress;

        $eventAttributes[Detail\Constants::POI_STATUS] = $merchantDetail->getPoiVerificationStatus();

        $this->app['eventManager']->trackEvents($merchant, Merchant\Action::ACTIVATION_PROGRESS, $eventAttributes);

        $eventAttributes['activation_flow'] = $merchantDetail->getActivationFlow();

        $this->app['diag']->trackOnboardingEvent(EventCode::ACT_SUBMIT_FORM_SUCCESS, $merchant, null, $eventAttributes);
    }

    public function getMerchantDetails(Merchant\Entity $merchant, array $input = []): Entity
    {
        $merchantDetails = $merchant->merchantDetail;

        if ($merchantDetails === null)
        {
            $this->trace->info(
                TraceCode::MERCHANT_DETAIL_DOES_NOT_EXIST,
                ['merchant_id' => $merchant->getId()]);

            $merchantDetails = Tracer::inspan(['name' => HyperTrace::CREATE_MERCHANT_DETAILS_CORE], function() use ($merchant, $input) {

                return $this->createMerchantDetails($merchant, $input);
            });

            // if merchant details are created, load relation in $merchant
            $merchant->load('merchantDetail');
        }

        return $merchantDetails;
    }

    /**
     * This function is used to patch merchant details fields
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Entity
     * @throws \RZP\Exception\BadRequestException
     */
    public function patchMerchantDetails(Merchant\Entity $merchant, array $input): Entity
    {
        $merchantDetails = $this->getMerchantDetails($merchant, $input);

        $merchantDetails->getValidator()->validateBusinessSubcategoryForCategory($input);

        $shouldResetMethods = $input['reset_methods'] ?? false;

        unset($input['reset_methods']);

        $merchantDetails->edit($input, 'patchMerchantDetails');

        $this->autoUpdateMerchantCategoryDetailsIfApplicable($merchantDetails, $merchant, $shouldResetMethods);

        $this->updateLegalEntity($input, $merchant);

        $this->repo->saveOrFail($merchantDetails);

        return $merchantDetails;
    }

    /**
     * This function is used to sync fields transaction_report_email and website
     * in both merchant and merchantDetail entities
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Entity
     */
    public function syncToMerchantDetailFields(Merchant\Entity $merchant, array $input): Entity
    {
        $merchantDetails = $merchant->merchantDetail;

        $data = [];

        if (isset($input[Merchant\Entity::TRANSACTION_REPORT_EMAIL]) === true)
        {
            $data[Entity::TRANSACTION_REPORT_EMAIL] = implode(',', $input[Entity::TRANSACTION_REPORT_EMAIL]);
        }

        if (isset($input[Merchant\Entity::WEBSITE]) === true)
        {
            $data[Entity::BUSINESS_WEBSITE] = $input[Merchant\Entity::WEBSITE];

            (new Merchant\Core())->updateWhitelistedDomain($merchant, $input);
        }

        if (empty($data) === false)
        {
            $merchantDetails->edit($data);

            $this->repo->saveOrFail($merchantDetails);
        }

        return $merchantDetails;
    }

    /**
     * This function is used to detect the sender for kyc communication
     *
     * @param string|null $source
     *
     * @return string
     */
    protected function getSender(?string $source)
    {
        if ($source !== null)
        {
            return $source;
        }

        //
        // If admin auth then sender will be admin
        //
        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            return \RZP\Constants\Entity::ADMIN;
        }

        return \RZP\Constants\Entity::MERCHANT;
    }

    /**
     * This function is used to get the updated kyc clarification reasons
     * with the sender info and the timestamp
     *
     * @param array       $input
     * @param string      $merchantId
     * @param string|null $source
     *
     * @return array
     */
    public function getUpdatedKycClarificationReasons(array $input, string $merchantId, ?string $source = null): array
    {
        $merchantDetails = $this->repo->merchant_detail->findByPublicId($merchantId);

        $existingKycClarifications      = $merchantDetails->getKycClarificationReasons() ?? [];
        $existingReasons                = $existingKycClarifications[Entity::CLARIFICATION_REASONS] ?? null;
        $existingAdditionalDetails      = $existingKycClarifications[Entity::ADDITIONAL_DETAILS] ?? null;
        $existingClarificationReasonsV2 = $existingKycClarifications[Entity::CLARIFICATION_REASONS_V2] ?? null;

        $newKycClarifications = $input[Entity::KYC_CLARIFICATION_REASONS] ?? [];
        $newAdditionalDetails = $newKycClarifications[Entity::ADDITIONAL_DETAILS] ?? null;
        $newReasons           = $newKycClarifications[Entity::CLARIFICATION_REASONS] ?? null;

        if ((empty($existingClarificationReasonsV2) === true)
            && ((empty($existingReasons) === false) || (empty($existingAdditionalDetails) === false)))
        {
            //add existing clarificationReasons And AdditionalDetails in clarification_reasons_v2
            $existingClarificationReasonsV2                              = $this->getClarificationReasonsV2($existingReasons ?? [], $existingAdditionalDetails ?? []);
            $existingKycClarifications[Entity::CLARIFICATION_REASONS_V2] = $existingClarificationReasonsV2;
        }

        if ((empty($newReasons) === true) and (empty($newAdditionalDetails) === true))
        {
            return $existingKycClarifications;
        }

        $newClarificationReasonsV2 = $this->getClarificationReasonsV2($newReasons ?? [], $newAdditionalDetails ?? []);

        $statusChangeLogs = (new Merchant\Core)->getActivationStatusChangeLog($merchantDetails->merchant);

        $ncCount = $this->getStatusChangeCount($statusChangeLogs, Status::NEEDS_CLARIFICATION);
        $ncCount++;
        $clarificationReasons   = $this->getClarificationReasons($existingReasons, $newReasons, $ncCount, $source);
        $additionalDetails      = $this->getClarificationReasons($existingAdditionalDetails, $newAdditionalDetails, $ncCount, $source);
        $clarificationReasonsV2 = $this->getClarificationReasons($existingClarificationReasonsV2, $newClarificationReasonsV2, $ncCount, $source);

        return [
            Entity::CLARIFICATION_REASONS    => $clarificationReasons,
            Entity::ADDITIONAL_DETAILS       => $additionalDetails,
            Entity::CLARIFICATION_REASONS_V2 => $clarificationReasonsV2,
            Merchant\Constants::NC_COUNT     => $ncCount
        ];
    }

    protected function getClarificationReasonsV2(array $clarificationReasons, array $additionalDetails)
    {
        $fieldsWhichAreQueriedUpon           = array_unique(array_merge(array_keys($clarificationReasons), array_keys($additionalDetails)));
        $fieldsWhichCannotExistIndependently = $this->getFieldsWhichCannotExistIndependently($fieldsWhichAreQueriedUpon);
        foreach ($fieldsWhichCannotExistIndependently as $field)
        {
            if (in_array($field, $fieldsWhichAreQueriedUpon) === true)
            {
                unset($fieldsWhichAreQueriedUpon[array_search($field, $fieldsWhichAreQueriedUpon)]);
            }
        }

        return $this->createClarificationReasonsV2($clarificationReasons, $additionalDetails, $fieldsWhichAreQueriedUpon);
    }

    protected function getFieldsWhichCannotExistIndependently(array $fieldsWhichAreQueriedUpon)
    {
        $fieldsWhichCannotExistIndependently = [];
        foreach ($fieldsWhichAreQueriedUpon as $field)
        {
            $related_fields_data = NeedsClarificationMetaData::RELATED_FIELDS_METADATA[$field][NCConstants::RELATED_FIELDS] ?? null;
            if (empty($related_fields_data) === false)
            {
                foreach ($related_fields_data as $related_field_data)
                {
                    if ($related_field_data[NCConstants::CAN_RF_EXIST_INDEPENDENTLY] === false)
                    {
                        array_push($fieldsWhichCannotExistIndependently, $related_field_data[NCConstants::FIELD_NAME]);
                    }
                }
            }
        }

        return $fieldsWhichCannotExistIndependently;
    }

    protected function createClarificationReasonsV2(array $clarificationReasons, array $additionalDetails, array $fieldsToBePresentInV2)
    {
        $modifiedGroupedDetails = [];
        foreach ($additionalDetails as $additionalDetail => $values)
        {
            if (in_array($additionalDetail, $fieldsToBePresentInV2) === true)
            {
                if (isset($modifiedGroupedDetails[$additionalDetail]) === true)
                {
                    array_push($modifiedGroupedDetails[$additionalDetail], ...$values);
                }
                else
                {
                    $modifiedGroupedDetails[$additionalDetail] = $values;
                }
            }
        }
        foreach ($clarificationReasons as $clarificationReason => $values)
        {
            if (in_array($clarificationReason, $fieldsToBePresentInV2) === true)
            {
                if (isset($modifiedGroupedDetails[$clarificationReason]) === true)
                {
                    array_push($modifiedGroupedDetails[$clarificationReason], ...$values);
                }
                else
                {
                    $modifiedGroupedDetails[$clarificationReason] = $values;
                }
            }
        }

        return $this->addRelatedFieldsInV2($modifiedGroupedDetails);
    }

    protected function addRelatedFieldsInV2(array $clarification_reasons_v2)
    {
        foreach ($clarification_reasons_v2 as $clarification_reason_v2 => $values)
        {
            $related_fields_data = NeedsClarificationMetaData::RELATED_FIELDS_METADATA[$clarification_reason_v2][NCConstants::RELATED_FIELDS] ?? null;
            if (empty($related_fields_data) === false)
            {
                $ncCountReferenceArray = $this->getNcCountRefernceArray($clarification_reasons_v2, $related_fields_data);
                foreach ($values as &$value)
                {
                    $value[NCConstants::RELATED_FIELDS] = [];
                    foreach ($related_fields_data as $related_field_data)
                    {
                        $related_field              = $related_field_data[NCConstants::FIELD_NAME];
                        $can_rf_exist_independently = $related_field_data[NCConstants::CAN_RF_EXIST_INDEPENDENTLY];
                        if ($can_rf_exist_independently === false)
                        {
                            array_push($value[NCConstants::RELATED_FIELDS], [
                                Merchant\Constants::FIELD_NAME => $related_field,
                            ]);
                        }
                        else
                        {
                            if (isset($value[Merchant\Constants::NC_COUNT]) === true)
                            {
                                $currNcCount = $value[Merchant\Constants::NC_COUNT];
                                if (in_array($currNcCount . '$' . $value[Entity::CREATED_AT], $ncCountReferenceArray[$related_field]) === false)
                                {
                                    array_push($value[NCConstants::RELATED_FIELDS], [
                                        Merchant\Constants::FIELD_NAME => $related_field,
                                    ]);
                                }
                            }
                            else
                            {
                                if (array_key_exists($related_field, $clarification_reasons_v2) === false)
                                {
                                    array_push($value[NCConstants::RELATED_FIELDS], [
                                        Merchant\Constants::FIELD_NAME => $related_field,
                                    ]);
                                }
                            }
                        }
                    }
                }
                $clarification_reasons_v2[$clarification_reason_v2] = $values;
            }
        }

        return $clarification_reasons_v2;
    }

    protected function getNcCountRefernceArray(array $clarification_reasons_v2, array $related_fields_data)
    {
        $refArray = [];
        foreach ($related_fields_data as $related_field_data)
        {
            $related_field              = $related_field_data[NCConstants::FIELD_NAME];
            $can_rf_exist_independently = $related_field_data[NCConstants::CAN_RF_EXIST_INDEPENDENTLY];
            $refArray[$related_field]   = [];
            if (($can_rf_exist_independently === true) && (array_key_exists($related_field, $clarification_reasons_v2)) === true)
            {
                $relatedFieldObjs = $clarification_reasons_v2[$related_field];
                foreach ($relatedFieldObjs as $relatedFieldObj)
                {
                    if (isset($relatedFieldObj[Merchant\Constants::NC_COUNT]) === true)
                    {
                        array_push($refArray[$related_field], $relatedFieldObj[Merchant\Constants::NC_COUNT] . '$' . $relatedFieldObj[Entity::CREATED_AT]);
                    }
                }
            }
        }

        return $refArray;
    }

    protected function getClarificationReasons($existingReasons, $newReasons, $ncCount, $source = null)
    {
        //
        // new reason is not null then reassign existing reason as we are appending new reasons in existing
        //
        $existingReasons = $existingReasons ?? [];

        foreach ($existingReasons as $key => $values)
        {
            foreach ($values as &$val)
            {
                if (isset($val[Merchant\Constants::NC_COUNT]) and $val[Merchant\Constants::NC_COUNT] !== $ncCount)
                {
                    $val[Merchant\Constants::IS_CURRENT] = false;
                }
            }

            $existingReasons[$key] = $values;
        }
        if (empty($newReasons) === false)
        {
            foreach ($newReasons as $key => $values)
            {
                foreach ($values as &$val)
                {
                    $val[Merchant\Constants::REASON_FROM] = $this->getSender($source);
                    $val[Entity::CREATED_AT]              = Carbon::now(Timezone::IST)->getTimestamp();
                    $val[Merchant\Constants::NC_COUNT]    = $ncCount;
                    $val[Merchant\Constants::IS_CURRENT]  = true;
                }

                if (isset($existingReasons[$key]) === true)
                {
                    array_push($existingReasons[$key], ...$values);
                }
                else
                {
                    $existingReasons[$key] = $values;
                }
            }
        }

        return $existingReasons;
    }

    public function editMerchantDetailFields(Merchant\Entity $merchant, array $input): Entity
    {
        $merchantDetails = Tracer::inspan(['name' => HyperTrace::GET_MERCHANT_DETAILS_CORE], function() use ($merchant, $input) {

            return $this->getMerchantDetails($merchant, $input);
        });

        if (isset($input[Entity::REVIEWER_ID]) === true)
        {
            $reviewerId = $input[Entity::REVIEWER_ID];

            unset($input[Entity::REVIEWER_ID]);

            AdminEntity::verifyIdAndStripSign($reviewerId);

            $reviewer = $this->repo->admin->findOrFailPublic($reviewerId);

            $merchantDetails->reviewer()->associate($reviewer);
        }

        $updatedLiteOnboardingExpt = (new Merchant\Core)->isRazorxExperimentEnable(
            $merchant->getId(),
            RazorxTreatment::UPDATED_LITE_ONBOARDING);

        if ($updatedLiteOnboardingExpt === true)
        {
            $promoterPanName = $merchantDetails->getPromoterPanName();

            if ($promoterPanName !== null)
            {
                $merchantDetails->setBankAccountName($promoterPanName);
            }
        }

        $merchantDetails->edit($input);

        $kycClarificationReasons = Tracer::inspan(['name' => HyperTrace::GET_UPDATED_KYC_CLARIFICATION_REASONS], function() use ($input, $merchantDetails) {

            return $this->getUpdatedKycClarificationReasons($input, $merchantDetails->getMerchantId());
        });

        if (empty($kycClarificationReasons) === false)
        {
            $merchantDetails->setKycClarificationReasons($kycClarificationReasons);
        }

        $this->updateBusinessCategory($merchantDetails, $input);

        Tracer::inspan(['name' => HyperTrace::AUTO_UPDATE_MERCHANT_CATEGORY_DETAILS_IF_APPLICABLE], function() use ($merchantDetails, $merchant) {

            $this->autoUpdateMerchantCategoryDetailsIfApplicable($merchantDetails, $merchant);
        });

        $this->repo->saveOrFail($merchantDetails);

        $this->updateLegalEntity($input, $merchant);

        // Sync few input fields to merchant entity
        (new Merchant\Core)->syncMerchantEntityFields($merchant, $input);

        Tracer::inspan(['name' => HyperTrace::SYNC_MERCHANT_DETAIL_FIELDS_TO_STAKEHOLDER], function() use ($merchantDetails, $input) {

            // dual write promoter related fields to stakeholder entity
            (new Stakeholder\Core)->syncMerchantDetailFieldsToStakeholder($merchantDetails, $input);
        });

        if (isset($input['stakeholder']) === true)
        {
            Tracer::inspan(['name' => HyperTrace::SAVE_STAKEHOLDER], function() use ($merchant, $input) {

                (new Stakeholder\Core)->saveStakeholder(null, $merchant->getId(), $input['stakeholder'], 'activation');
            });
        }

        if (isset($input['merchant_avg_order_value']) === true)
        {
            (new AvgOrderValue\Core)->createOrEditAvgOrderValue($merchantDetails, $input['merchant_avg_order_value']);
        }
        else
        {
            $liteOnboardingExpt = (new Merchant\Core)->isRazorxExperimentEnable(
                $merchant->getId(),
                RazorxTreatment::LITE_ONBOARDING);

            if ($liteOnboardingExpt === true)
            {
                $aovInput = [
                    AvgOrderValue\Entity::MIN_AOV => -1,
                    AvgOrderValue\Entity::MAX_AOV => -1,
                ];

                (new AvgOrderValue\Core)->createOrEditAvgOrderValue($merchantDetails, $aovInput);
            }
        }

        $this->repo->saveOrFail($merchant);

        $segmentProperties = $this->getSegmentPropertiesForFormSubmit($merchant, $input);

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $segmentProperties, SegmentEvent::KYC_FORM_SAVED);

        $this->triggerRequestToBvs($merchant, Status::NEEDS_CLARIFICATION, $input);

        return $merchantDetails;
    }

    protected function getSegmentPropertiesForFormSubmit($merchant, $input)
    {
        $properties = [];

        foreach (DEConstants::KYC_FORM_SUBMIT_SEGMENT_PROPERTIES as $key)
        {
            if (isset($input[$key]) === true)
            {
                $properties[$key] = $input[$key];
            }
            else
            {
                $properties[$key] = $merchant->merchantDetail->getAttribute($key) ?? 'NULL';
            }
        }

        return $properties;
    }

    protected function updateBusinessCategory($merchantDetails, array $input)
    {
        // If category and subcategory are not set
        if ((isset($input[Entity::BUSINESS_CATEGORY]) === false) and
            (isset($input[Entity::BUSINESS_SUBCATEGORY]) === false))
        {
            return;
        }

        if ((isset($input[Entity::BUSINESS_CATEGORY]) === false) and
            (isset($input[Entity::BUSINESS_SUBCATEGORY]) === true))
        {
            $category = BusinessCategory::getCategoryFromSubCategory($input[Entity::BUSINESS_SUBCATEGORY]);
            $merchantDetails->setBusinessCategory($category);
        }
    }

    /**
     * Fills up dummy file IDs, required fields for merchant activation.
     *
     * This function is being used for creating and activating sub merchant .
     *
     * In this case kyc is handled by partner , so we upload dummy files .
     *
     * @param Merchant\Entity $merchant
     *
     * @throws \RZP\Exception\BadRequestException
     */
    public function saveDummyActivationFiles(Merchant\Entity $merchant)
    {
        $merchantDetails = $merchant->merchantDetail;

        $requiredDocuments = $this->getRequireActivationDocuments($merchantDetails);

        $merchantDetailsParams  = [];
        $merchantDocumentParams = [];

        foreach ($requiredDocuments as $requiredDocument)
        {
            $merchantDetailsParams[$requiredDocument] = DEConstants::DUMMY_ACTIVATION_FILE;

            $merchantDocumentParams[$requiredDocument] = [
                Document\Constants::FILE_ID => DEConstants::DUMMY_ACTIVATION_FILE,
                Document\Constants::SOURCE  => Document\Source::UFH,
            ];
        }

        (new Document\Core)->storeInMerchantDocument($merchant, $merchant, $merchantDocumentParams);
    }

    public function createMerchantDetails(Merchant\Entity $merchant, array $input = [])
    {
        $merchantDetail = (new Entity)->build($input);

        if ($merchant->getEmail() !== null)
        {
            $merchantDetail->setContactEmail($merchant->getEmail());
        }

        $merchantDetail->merchant()->associate($merchant);

        try
        {
            $this->repo->saveOrFail($merchantDetail);

            $this->trace->info(
                TraceCode::CREATE_MERCHANT_DETAIL,
                ['merchant_id' => $merchant->getId()]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::CREATE_MERCHANT_DETAIL_FAILED,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }

        return $merchantDetail;
    }

    /**
     * On submission of activation form by user, send email
     * to the customer and sales team notifying them about the activity
     *
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     */
    protected function fireActivationTrigger(Entity $merchantDetails, Merchant\Entity $merchant)
    {
        $merchantId = $merchant->id;

        $customer = [
            'id'            => $merchantId,
            'name'          => $merchantDetails['contact_name'],
            'email'         => $merchantDetails['contact_email'],
            'business_name' => $merchantDetails['business_name'],
            'dba'           => $merchantDetails['business_dba'],
            'website'       => $merchantDetails['business_website']
        ];

        // For marketplace linked accounts - skip sending this email
        if ($merchant->isLinkedAccount() === false)
        {
            $this->merchantNotifyActivationSubmission($merchantDetails, $merchant);
        }

        $this->adminNotifyActivationSubmission($merchantDetails);

        $zapierData = $this->activationZapierData($customer, $merchant);

        $this->postFormSubmissionToZapier($zapierData, 'submissions', $merchant);

        $eventAttributes = [
            Detail\Constants::POA_STATUS                       => $merchantDetails->getPoaVerificationStatus(),
            Detail\Constants::BANK_DETAILS_VERIFICATION_STATUS => $merchantDetails->getBankDetailsVerificationStatus(),
        ];

        $this->app['diag']->trackOnboardingEvent(EventCode::KYC_FORM_SUBMIT_SUCCESS, $merchant, null, $eventAttributes);
    }

    protected function activationZapierData(array $customer, Merchant\Entity $merchant)
    {
        $customer['date'] = Carbon::createFromTimeStamp(time(), Timezone::IST)->format('j/m/Y');

        if ($merchant->users->isNotEmpty() === true)
        {
            $customer['contact_name'] = $merchant->users->first()->getAttribute('name');
        }

        return $customer;
    }

    public function postFormSubmissionToZapier($data, $zapierAction, Merchant\Entity $merchant)
    {
        // Don't send data to zapier for hdfc org.
        // TODO:: Move the check to a feature flag after org level feature flags are implemented.
        if ((Config::get('zapier.mock') === true) or ($merchant->getOrgId() === Org\Entity::HDFC_ORG_ID))
        {
            return;
        }

        $url = Config::get('zapier.' . $zapierAction);

        $request = [
            'url'     => $url,
            'method'  => 'post',
            'headers' => [],
            'options' => [],
            'content' => $data
        ];

        // Dispatching the job into the queue
        RequestJob::dispatch($request);
    }

    protected function merchantNotifyActivationSubmission(Entity $merchantDetails, Merchant\Entity $merchant)
    {
        $product = $this->app['basicauth']->getRequestOriginProduct();

        $activationFlow = $merchant->merchantDetail->getActivationFlow();

        $this->trace->info(TraceCode::KYC_SUBMITTED_EMAIL,
                           [
                               'merchant_id'         => $merchant->getPublicId(),
                               'product'             => $product,
                               'activation_flow'     => $activationFlow,
                               'has_banking_account' => $merchant->hasBankingAccounts()
                           ]);

        if ($product === Product::BANKING)
        {
            if (($activationFlow === ActivationFlow::WHITELIST) and
                ($merchant->hasBankingAccounts() === true))
            {
                Mail::queue(new L2SubmissionWhitelist($merchant->getId()));
            }
            else
            {
                if ($activationFlow === ActivationFlow::GREYLIST)
                {
                    Mail::queue(new L2SubmissionGreylist($merchant->getId()));
                }
            }
        }
    }

    protected function adminNotifyActivationSubmission(Entity $merchantDetails)
    {
        $data = $merchantDetails->toArray();

        $notifyAdminMail = new NotifyAdmin($data);

        Mail::queue($notifyAdminMail);
    }

    protected function canSubmit($input, $response, $activationFormMilestone = null)
    {
        if ($response['can_submit'] === false)
        {
            return false;
        }

        $submit = $input[Entity::SUBMIT] ?? false;

        return (($submit === '1') or
                ($activationFormMilestone === DEConstants::L2_SUBMISSION));
    }

    /**
     * This function checks and sets has_key_access to true if merchant has submitted
     * wesbite details
     *
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     */
    public function checkAndMarkHasKeyAccess(Entity $merchantDetails, Merchant\Entity $merchant)
    {

        $this->trace->info(
            TraceCode::MERCHANT_MARK_HAS_KEY_ACCESS,
            [
                'business_website' => $merchantDetails->getWebsite(),
                'has_key_access'   => $merchant->getHasKeyAccess(),
                'merchant_id'      => $merchant->getId()
            ]);

        if (empty($merchantDetails->getWebsite()) === true)
        {
            return;
        }

        $merchant->setHasKeyAccess(true);
    }

    /**
     * Updates the product business banking or primary from where the activation form was submitted.
     *
     * @param Merchant\Entity $merchant
     * @param string          $originProduct
     */
    public function updateActivationSource(Merchant\Entity $merchant, string $originProduct)
    {
        $merchant->setActivationSource($originProduct);

        $this->repo->saveOrFail($merchant);
    }

    /**
     * This submits and locks the form for user.
     *
     * @param Entity $merchantDetails
     */
    public function markSubmittedAndLock(Entity $merchantDetails)
    {
        $submittedAt = Carbon::now()->getTimestamp();

        $input = [
            Entity::SUBMITTED    => 1,
            Entity::SUBMITTED_AT => $submittedAt,
            Entity::LOCKED       => true,
        ];

        $merchantDetails->fill($input);

        $this->repo->saveOrFail($merchantDetails);
    }

    /**
     * Trigger request to BVS to inform about Manual Verification event
     *
     * @param Merchant\Entity $merchant
     * @param string          $activationStatus
     * @param array           $data
     */
    protected function triggerRequestToBvs(Merchant\Entity $merchant, string $activationStatus, $data = []): void
    {
        try
        {
            $variant = $this->app->razorx->getTreatment(
                $merchant->getId(),
                RazorxTreatment::BVS_MANUAL_VERIFICATION_DATA,
                $this->app['basicauth']->getMode() ?? "live"
            );

            if (strcmp($variant, Constant::ON) != 0)
            {
                return;
            }

            if (!isset($data))
            {
                $data = [];
            }

            $requestContext = $this->app['request.ctx'];
            $request        = $this->app['request'];

            if (isset($requestContext) === false or isset($request) === false)
            {
                return;
            }

            if ($this->app['basicauth']->isAdminLoggedInAsMerchantOnDashboard() === true)
            {
                return;
            }

            if (!($requestContext->isAdminDashboard() || $requestContext->getInternalAppName() === 'dashboard'))
            {
                return;
            }

            switch ($activationStatus)
            {
                case Status::ACTIVATED:
                    $workflow_id = $request->route('id');
                    (new ManualVerificationRequestDispatcher\Activated($merchant, $workflow_id))->triggerBVSRequest();
                    break;
                case Status::NEEDS_CLARIFICATION:
                    (new ManualVerificationRequestDispatcher\NeedsClarification($merchant, $data))->triggerBVSRequest();
                    break;
                case Status::REJECTED:
                    $workflow_id = $request->route('id');
                    (new ManualVerificationRequestDispatcher\Rejected($merchant, $workflow_id, $data))->triggerBVSRequest();
                    break;
                case Status::ACTIVATED_MCC_PENDING:
                    (new ManualVerificationRequestDispatcher\ActivatedMccPending($merchant))->triggerBVSRequest();
                    break;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL, TraceCode::BVS_MANUAL_VERIFICATION_REQUEST_ERROR);
        }
    }

    /**
     * This function is used for archiving merchant activation form
     *
     * @param Entity      $merchantDetails
     * @param array       $input
     * @param AdminEntity $admin
     *
     * @return Entity
     */
    public function updateActivationArchive(Entity $merchantDetails, array $input, AdminEntity $admin): Entity
    {
        $merchantDetails->getValidator()->validateInput('archiveForm', $input);

        $archiveAction = (empty($input[Entity::ARCHIVE]) === false) ? Action::ARCHIVE : Action::UNARCHIVE;

        // Check for admin permission
        $admin->hasMerchantActionPermissionOrFail($archiveAction);

        $archivedAt = null;

        if (empty($input[Entity::ARCHIVE]) === false)
        {
            $archivedAt = Carbon::now(Timezone::IST)->getTimestamp();
        }

        $routePermission = Permission\Name::$actionMap[$archiveAction];

        $oldMerchantDetails = clone $merchantDetails;

        $merchantDetails->setArchivedAt($archivedAt);

        $this->app['workflow']->setPermission($routePermission)->handle(
            $oldMerchantDetails, $merchantDetails);

        $this->repo->saveOrFail($merchantDetails);

        $this->logActionToSlack($merchantDetails->merchant, $archiveAction);

        return $merchantDetails;
    }

    /**
     * This function is used for updating merchant activation status
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     * @param PublicEntity    $maker [can be one of Admin\Admin\Entity or Merchant\Entity]
     *
     * @return Entity
     * @throws \Throwable
     */
    public function updateActivationStatus(Merchant\Entity $merchant, array $input, PublicEntity $maker): Entity
    {
        $startTime = microtime(true);

        $merchantDetails = $this->getMerchantDetails($merchant, $input);

        $merchantDetails->getValidator()->validateInput('activationStatus', $input);

        $currentActivationStatus = $merchantDetails->getActivationStatus();

        $merchantDetails->getValidator()
                        ->validateActivationStatusChange(
                            $currentActivationStatus,
                            $input[Entity::ACTIVATION_STATUS]);

        $this->trace->info(TraceCode::MERCHANT_UPDATE_ACTIVATION_STATUS, [
            'input'       => $input,
            'merchant_id' => $merchant->getId()
        ]);

        $rejectionReasons = [];

        $rejectionOption = '';

        $shouldSave = true;

        if (empty($input[Entity::REJECTION_REASONS]) === false)
        {
            $rejectionReasons = $input[Entity::REJECTION_REASONS];

            unset($input[Entity::REJECTION_REASONS]);
        }

        if (empty($input[Entity::REJECTION_OPTION]) === false)
        {
            $rejectionOption = $input[Entity::REJECTION_OPTION];

            unset($input[Entity::REJECTION_OPTION]);
        }

        $oldMerchantDetails = clone $merchantDetails;

        $this->trace->info(TraceCode::UPDATE_ACTIVATION_MERCHANT_DETAILS_EDIT_REQUEST, [
            'input'       => $input,
            'merchant_id' => $merchant->getId(),
            'duration'    => (microtime(true) - $startTime) * 1000,
        ]);

        $merchantDetails->edit($input);

        $this->trace->info(TraceCode::UPDATE_ACTIVATION_MERCHANT_DETAILS_EDITED, [
            'input'       => $input,
            'merchant_id' => $merchant->getId(),
            'duration'    => (microtime(true) - $startTime) * 1000,
        ]);

        $newMerchantDetails = clone $merchantDetails;

        $partnerActivationCore = (new Activation\Core());

        $this->repo->transactionOnLiveAndTest(function() use ($input, $merchant) {
            switch ($input[Entity::ACTIVATION_STATUS])
            {
                case Status::ACTIVATED:

                    if ($merchant->isLinkedAccount() === false)
                    {
                        // If merchant gets Activated, onboarding WF's should get auto-approved
                        (new ActionCore)->handleOnboardingWorkflowActionIfOpen(
                            $merchant->getId(), 'merchant_detail', State\Name::APPROVED);
                    }
                    break;
                case Status::NEEDS_CLARIFICATION:

                    // If merchant goes to NC, onboarding WF's should get auto-rejected
                    (new ActionCore)->handleOnboardingWorkflowActionIfOpen(
                        $merchant->getId(), 'merchant_detail', State\Name::REJECTED);
                    break;
                case Status::REJECTED:

                    // If merchant gets Rejected, onboarding WF's should get auto-closed
                    (new ActionCore)->handleOnboardingWorkflowActionIfOpen(
                        $merchant->getId(), 'merchant_detail', State\Name::CLOSED);
                    break;
            }
        });

        $this->repo->transactionOnLiveAndTest(function() use (
            $rejectionOption,
            $merchantDetails,
            $oldMerchantDetails,
            $newMerchantDetails,
            $input,
            $rejectionReasons,
            $maker, $merchant,
            $shouldSave
        ) {
            if (($input[Entity::ACTIVATION_STATUS] === Status::ACTIVATED) and
                ($merchant->isLinkedAccount() === false))
            {
                /*
                 * Setup workflow for activation_status change in merchantDetail entity,
                 * which will be triggered once all the validations are checked in the activate method.
                 */
                $this->app['workflow']
                    ->setEntity($merchantDetails->getEntity())
                    ->setOriginal($oldMerchantDetails)
                    ->setDirty($newMerchantDetails);

                $isMerchantPreviouslyActivated = $merchant->isActivated();

                $this->trace->info(TraceCode::MERCHANT_UPDATE_ACTIVATE_LOG, [
                    'text'     => 'before activating merchant',
                    'shouldSave'       => $shouldSave,
                ]);

                (new Merchant\Activate)->activate($merchant, true, $shouldSave);

                $this->trace->info(TraceCode::MERCHANT_UPDATE_ACTIVATE_LOG, [
                    'text'     => 'after activating merchant',
                    'shouldSave'       => $shouldSave,
                ]);

                $this->triggerRequestToBvs($merchant, Status::ACTIVATED);
                // request for default instruments when merchant is activated
                $this->app['terminals_service']->requestDefaultMerchantInstruments($merchant->getId());

                if (!$isMerchantPreviouslyActivated)
                {
                    $this->paymentEnabledEvent($merchant, $oldMerchantDetails, $newMerchantDetails);
                }
            }

            if (($input[Entity::ACTIVATION_STATUS] === Status::ACTIVATED_MCC_PENDING) and
                ($merchant->isLinkedAccount() === false))
            {
                $isMerchantPreviouslyActivated = $merchant->isActivated();

                (new Merchant\Activate)->activate($merchant, false, $shouldSave);

                $shouldSave = true;

                $this->triggerRequestToBvs($merchant, Status::ACTIVATED_MCC_PENDING);
                // request for default instruments when merchant is activated
                $this->app['terminals_service']->requestDefaultMerchantInstruments($merchant->getId());

                if (!$isMerchantPreviouslyActivated)
                {
                    $this->paymentEnabledEvent($merchant, $oldMerchantDetails, $newMerchantDetails);
                }
            }

            if ($input[Entity::ACTIVATION_STATUS] === Status::ACTIVATED_KYC_PENDING)
            {
                (new Merchant\Activate)->activate($merchant, false, $shouldSave);
            }

            if ($input[Entity::ACTIVATION_STATUS] === Status::REJECTED)
            {
                $this->triggerWorkflowForRejectionActivationStatusChange(
                    $oldMerchantDetails,
                    $newMerchantDetails,
                    $rejectionReasons);

                if (empty($rejectionOption) === true)
                {
                    $merchant->deactivate();

                    $this->sendRejectionEmail($merchant);
                }
                else
                {
                    $actions = RequestConstants::REJECTION_OPTION_MAP[$rejectionOption];

                    foreach ($actions as $action)
                    {
                        if ($action === RequestConstants::SEND_MAIL)
                        {
                            $this->$action($merchant);
                        }
                        else
                        {
                            $merchant->$action();
                        }
                    }
                }

                $this->triggerRequestToBvs($merchant, Status::REJECTED, $rejectionReasons);

            }

            if ($input[Entity::ACTIVATION_STATUS] === Status::NEEDS_CLARIFICATION)
            {
                //
                // For Older merchant who are still in old flow ,
                // kyc clarification will be empty in this case form should not get unlocked
                //
                if (empty($merchantDetails->getKycClarificationReasons()) === false)
                {
                    $merchantDetails->setLocked(false);

                    $accessMaps = $this->repo->merchant_access_map->fetchAffiliatedPartnersForSubmerchant($merchant->getId());
                    
                    $partnerMerchant = $accessMaps->filter(function ($value, $key) {
                        return ($value->entityOwner->isAggregatorPartner() === true);
                    })->first();
         
                    $this->sendNeedsClarificationEmail($merchant);
                    
                    // $partnerMerchant can be null in case of linked accounts
                    if(!is_null($partnerMerchant)){
                        $properties = [
                            'id' => $partnerMerchant->entityOwner->getId(),
                            'experiment_id' => $this->app['config']->get('app.merchant_kyc_update_to_partner_exp_id')
                        ];

                        $isExpEnabled = (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');
                        if($isExpEnabled === true)
                            $this->sendSubMerchantNCStatusChangedEmail($merchant, $partnerMerchant->entityOwner);
                    }
                }
            }

            if ($shouldSave === false)
            {
                return;
            }

            $this->trace->info(
                TraceCode::MERCHANT_ACTIVATION_LOGS,
                [
                    'text'     => 'before saving merchant',
                    'merchant' => $merchant
                ]
            );

            $this->repo->saveOrFail($merchantDetails);

            $this->repo->saveOrFail($merchant);

            $this->trace->info(
                TraceCode::MERCHANT_ACTIVATION_LOGS,
                [
                    'text'     => 'after saving merchant',
                    'merchant' => $merchant
                ]
            );

            $stateData = [
                State\Entity::NAME => $input[Entity::ACTIVATION_STATUS],
            ];

            $state = (new State\Core)->createForMakerAndEntity($stateData, $maker, $merchantDetails);

            if (empty($rejectionReasons) === false)
            {
                (new Reason\Core)->addRejectionReasons($rejectionReasons, $state);
            }

            $status = $input[Entity::ACTIVATION_STATUS];

            if (empty($status) === false)
            {
                $eventPayload = [
                    ApiEventSubscriber::MAIN => $merchant,
                ];

                $event = 'api.account.' . $status;

                $this->app['events']->dispatch($event, $eventPayload);
            }
        });

        if ($shouldSave === false)
        {
            return $merchantDetails;
        }

        $customProperties['activation_status'] = $currentActivationStatus;

        $properties = $this->getSegmentEventPropertiesforActivationStatusChange($merchant, $merchantDetails, $currentActivationStatus);

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $properties, SegmentEvent::ACTIVATION_STATUS_CHANGE);

        $this->pushHubspotEvent($merchant, $merchantDetails);

        $this->app['diag']->trackOnboardingEvent(EventCode::ACT_CHANGE_ACTIVATION_STATUS_SUCCESS,
                                                 $merchant,
                                                 null,
                                                 $customProperties);

        $this->trace->count(
            Metric::MERCHANT_ACTIVATION_STATE_TRANSITION,
            $this->fetchActivationStatusTransitionMetricDimensions(
                $merchantDetails->getActivationStatus(),
                $currentActivationStatus));

        $args = [
            'activationStatus' => $currentActivationStatus,
            'merchant'         => $merchant
        ];

        $this->trace->info(TraceCode::MERCHANT_ACTIVATION_ONBOARDING_NOTIFICATION, [
            'duration'    => (microtime(true) - $startTime) * 1000,
            'start_time'  => $startTime
        ]);

        Tracer::inSpan(['name' => 'onboarding_notification_handler_send'], function() use ($args) {
            (new OnboardingNotificationHandler($args))->send();
        });

        (new MerchantProduct\Core())->syncMerchantStatusToMerchantProducts($merchantDetails);

        $partnerActivationCore->autoActivatePartnerIfApplicable($merchant, $merchantDetails, $maker);

        $partnerActivationCore->markPartnerFormAsNCIfApplicable($merchant, $merchantDetails, $maker);

        $this->trace->info(TraceCode::MERCHANT_UPDATE_ACTIVATION_STATUS_LATENCY, [
            'merchant_id' => $merchant->getId(),
            'duration'    => (microtime(true) - $startTime) * 1000,
            'start_time'  => $startTime
        ]);

        return $merchantDetails;
    }

    public function paymentEnabledEvent($merchant, $oldMerchantDetails, $newMerchantDetails)
    {

        $properties = [
            'previousActivationStatus' => $oldMerchantDetails->getActivationStatus(),
            'currentActivationStatus'  => $newMerchantDetails->getActivationStatus(),
            'activated_at'             => $merchant->getActivatedAt(),
        ];

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $properties, SegmentEvent::PAYMENTS_ENABLED);

    }

    protected function pushHubspotEvent($merchant, $merchantDetails)
    {
        if (empty($merchant->getEmail()) === true)
        {
            return;
        }

        $properties = [
            'live' => $merchant->isLive()
        ];

        if ($merchantDetails->getActivationStatus() === Status::INSTANTLY_ACTIVATED)
        {
            $properties['instant_activation'] = 1;
        }

        $this->app->hubspot->trackHubspotEvent($merchant->getEmail(), $properties);
    }

    protected function getSegmentEventPropertiesforActivationStatusChange($merchant, $merchantDetails, $previousActivationStatus)
    {
        $activationStatus = $merchantDetails->getActivationStatus();
        $properties       = [
            'activation_status'          => $merchantDetails->getActivationStatus(),
            'previous_activation_status' => $previousActivationStatus,
            'mcc'                        => $merchant->getCategory()
        ];
        if ($activationStatus === Status::INSTANTLY_ACTIVATED)
        {
            $properties['instant_activation'] = true;
        }
        if ($activationStatus === Status::ACTIVATED_MCC_PENDING)
        {
            $properties['activated_mcc_pending'] = true;
        }
        if ($activationStatus === Status::NEEDS_CLARIFICATION)
        {
            $properties['needs_clarification'] = true;
        }

        return $properties;
    }

    /**
     * @param $merchant
     */
    public function sendNeedsClarificationEmail(Merchant\Entity $merchant)
    {
        if ($merchant->getEmail() == null)
        {
            return;
        }

        $notificationBlocked = (new PartnerCore())->isSubMerchantNotificationBlocked($merchant->getId());

        if ($notificationBlocked === true)
        {
            return;
        }

        $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

        $merchantDetail = $merchant->merchantDetail;

        $clarificationCore = new Detail\NeedsClarification\Core();

        $clarificationReasons = $clarificationCore->getFormattedKycClarificationReasons(
            $merchantDetail->getKycClarificationReasons());

        $data = $this->getPayloadForClarificationEmail($merchant, $org, $clarificationReasons);

        $email = new ClarificationEmail($data, $org->toArray());

        Mail::queue($email);
    }

    /**
     * @param $merchant
     * @param $partnerMerchant
     */
    public function sendSubMerchantNCStatusChangedEmail(Merchant\Entity $merchant, Merchant\Entity $partnerMerchant)
    {
        if ($partnerMerchant->getEmail() == null)
        {
            return;
        }

        $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

        $merchantDetail = $merchant->merchantDetail;

        $clarificationCore = new Detail\NeedsClarification\Core();

        $clarificationReasons = $clarificationCore->getFormattedKycClarificationReasons(
            $merchantDetail->getKycClarificationReasons());

        $data = $this->getPayloadForSubMerchantNCStatusChangedEmail($merchant, $partnerMerchant, $clarificationReasons);

        $email = new SubMerchantNCStatusChangedEmail($data, $org->toArray());

        Mail::queue($email);
    }

    public function getPayloadForSubMerchantNCStatusChangedEmail(Merchant\Entity $merchant, Merchant\Entity $partnerMerchant, $clarificationReasons)
    {
        $data = [
            DEConstants::PARTNER_EMAIL        => $partnerMerchant->getEmail(),
            DEConstants::MERCHANT             => [
                Merchant\Entity::ID          => $merchant->getId(),
                Merchant\Entity::NAME          => $merchant->getName(),
            ],
            DEConstants::CLARIFICATION_REASON => $clarificationReasons,
        ];

        return $data;
    }

    public function getPayloadForClarificationEmail(Merchant\Entity $merchant, Org\Entity $org, $clarificationReasons)
    {
        $data = [
            DEConstants::MERCHANT             => [
                Merchant\Entity::NAME          => $merchant->getName(),
                Merchant\Entity::BILLING_LABEL => $merchant->getBillingLabel(),
                Merchant\Entity::EMAIL         => $merchant->getEmail(),
                DEConstants::ORG               => [
                    DEConstants::HOSTNAME => $org->getPrimaryHostName(),
                ]
            ],
            DEConstants::CLARIFICATION_REASON => $clarificationReasons,
        ];

        return $data;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param string|null     $issueFields
     */
    public function deactivateIfFlawedWebsite(Merchant\Entity $merchant, string $issueFields = null)
    {
        $issueFieldsArray = explode(',', $issueFields) ?? [];

        if (in_array(Entity::BUSINESS_WEBSITE, $issueFieldsArray) === true)
        {
            $domain = (new Merchant\TLDExtract())->getEffectiveTLDPlusOne($merchant->getWebsite());

            (new Merchant\Core)->removeDomainFromWhitelistedDomain($merchant, $domain);
        }
    }

    public function setBankAccountForMerchant(Merchant\Entity $merchant)
    {
        $bankCore = (new BankAccount\Core);

        // Build the input array for the merchant's bank account creation
        $bankData = $bankCore->buildBankAccountArrayFromMerchantDetail($merchant->merchantDetail);

        $bankCore->createOrChangeBankAccount($bankData, $merchant);
    }

    /**
     * Triggers workflow when activation status is changed to rejected
     *
     * @param Entity $oldMerchantDetails
     * @param Entity $newMerchantDetails
     * @param array  $rejectionReasons
     *
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    protected function triggerWorkflowForRejectionActivationStatusChange(
        Entity $oldMerchantDetails,
        Entity $newMerchantDetails,
        array $rejectionReasons)
    {
        $oldMerchantDetailsArray = $oldMerchantDetails->toArray();

        $newMerchantDetailsArray = $newMerchantDetails->toArray();

        $rejectionReasonDescriptions = [];

        foreach ($rejectionReasons as $rejectionReason)
        {
            $rejectionReasonCode = $rejectionReason[Reason\Entity::REASON_CODE] ?? '';

            $rejectionReasonDescriptions[] = RejectionReasons::getReasonDescriptionByReasonCode($rejectionReasonCode);
        }

        $newMerchantDetailsArray[Entity::REJECTION_REASONS] = $rejectionReasonDescriptions;

        $this->app['workflow']
            ->setEntity($newMerchantDetails->getEntity())
            ->handle($oldMerchantDetailsArray, $newMerchantDetailsArray);

        $merchant = $newMerchantDetails->merchant;

        // If the merchant is instantly activated and the kyc gets rejected, disable live transactions
        (new Merchant\Core)->disableLiveIfAlreadyActivated($merchant);
    }

    /**
     * This function is used for updating merchant website details
     *
     * @param Entity $merchantDetails
     * @param array  $input
     *
     * @return array
     * @throws \Throwable
     */
    public function updateWebsiteDetails(Entity $merchantDetails, array $input): array
    {
        $merchantDetails->getValidator()->validateInput('websiteDetails', $input);

        $this->trace->info(
            TraceCode::MERCHANT_UPDATE_WEBSITE_DETAILS,
            ['input' => $input]);

        $originalMerchantDetails = clone $merchantDetails;

        $merchantDetails->edit($input);

        $dirtyMerchantDetails = $merchantDetails;

        if ($merchantDetails->getActivationStatus() === Status::ACTIVATED)
        {
            $this->app['workflow']
                ->setEntityAndId($originalMerchantDetails->getEntity(), $originalMerchantDetails->getMerchantId())
                ->handle($originalMerchantDetails, $dirtyMerchantDetails);
        }

        return $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails, $input) {
            $this->repo->saveOrFail($merchantDetails);

            $merchant = $merchantDetails->merchant;

            // Sync few input fields to merchant entity
            $merchant = (new Merchant\Core)->syncMerchantEntityFields($merchant, $input);

            $this->checkAndMarkHasKeyAccess($merchantDetails, $merchant);

            $this->repo->saveOrFail($merchant);

            if ((empty($this->merchant->primaryOwner()) === false) and
                ($merchantDetails->getActivationStatus() === Status::ACTIVATED))
            {
                $args = [
                    Constants::MERCHANT               => $this->merchant,
                    DashboardNotificationEvent::EVENT => DashboardNotificationEvent::MERCHANT_BUSINESS_WEBSITE_ADD,
                    Constants::PARAMS                 => [
                        DashboardNotificationConstants::UPDATED_BUSINESS_WEBSITE => $input[Entity::BUSINESS_WEBSITE]
                    ]
                ];

                (new DashboardNotificationHandler($args))->send();
            }

            $response = $merchantDetails->toArrayPublic();

            $response[Merchant\Entity::HAS_KEY_ACCESS] = $merchant->getHasKeyAccess();

            return $response;
        });
    }

    /**
     * @param Merchant\Entity $merchant
     *
     * @return bool
     * @throws \RZP\Exception\BadRequestException
     */
    protected function autoActivateMerchantIfApplicable(Merchant\Entity $merchant): bool
    {
        $merchantDetails = $this->getMerchantDetails($merchant);

        //
        // Auto-activation is attempted if the following conditions are met
        //
        if (($merchantDetails->isSubmitted() === true) and
            ($merchant->isLinkedAccount() === true))
        {
            $bankCore = (new BankAccount\Core);

            // Build the input array for the merchant's bank account creation
            $bankData = $bankCore->buildBankAccountArrayFromMerchantDetail($merchantDetails, true);

            $bankCore->createOrChangeBankAccount($bankData, $merchant);

            (new Merchant\Activate)->autoActivate($merchant);

            $activationStatusData = [
                Entity::ACTIVATION_STATUS => Status::ACTIVATED,
            ];

            $this->updateActivationStatus($merchant, $activationStatusData, $merchant);

            $this->repo->saveOrFail($merchantDetails);

            return true;
        }

        return false;
    }

    public function getValidationFields(Entity $merchantDetails, bool $addMissingRequirements = false): array
    {
        // @todo: Activation flow will define its own validation fields

        // If no doc onboarding feature is enabled and gmv limit is not exhausted then pick only the specified validation fields.
        if ($merchantDetails->merchant->isNoDocOnboardingEnabled() === true)
        {
            $isGmvLimitExhausted = (new Merchant\AccountV2\Core())->isNoDocOnboardingGmvLimitExhausted($merchantDetails->getMerchantId());

            if ($isGmvLimitExhausted === false)
            {
                [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = ValidationFields::getValidationFieldsForNoDocOnboarding($merchantDetails);

                return [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields];
            }
        }

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = ValidationFields::getValidationFields($merchantDetails);

        if (self::shouldSkipBankAccountRegistration() === true)
        {
            $validationFields = array_diff($validationFields, RequiredFields::BANK_ACCOUNT_FIELDS);
        }

        if (self::shouldSkipKycDocuments($merchantDetails) === true)
        {
            $validationFields = array_diff($validationFields, RequiredFields::KYC_DOCUMENT_FIELDS);
        }

        if ($this->shouldSkipPOADocuments($merchantDetails) === true)
        {
            unset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]);
        }

        $merchant = $merchantDetails->merchant;

        if ($addMissingRequirements === true)
        {
            $businessType = $merchantDetails->getBusinessType();

            array_push($validationFields, Entity::PROMOTER_PAN);

            if (in_array($businessType, BusinessType::$ValidateCompanyPanBusinessType) === true)
            {
                array_push($validationFields, Entity::COMPANY_PAN);
            }

            if (in_array($businessType, BusinessType::$ValidateCINBusinessType) === true)
            {
                array_push($validationFields, Entity::COMPANY_CIN);
            }

            // If the personalPanDocuments group is not fulfilled, \RZP\Models\Merchant\Detail\Core::calculateRequiredDocumentFields would pick the first requirement in group as required field
            // This is just a work around to support V2 onboarding flow to prioritize personal_pan document. So reversing the array.
            if ($businessType === BusinessType::PROPRIETORSHIP)
            {
                $personalPanDocuments = $validationSelectiveRequiredFields[SelectiveRequiredFields::PERSONAL_PAN_DOCUMENTS];

                $validationSelectiveRequiredFields[SelectiveRequiredFields::PERSONAL_PAN_DOCUMENTS] = array_reverse($personalPanDocuments);
            }
        }

        if ($merchant->isLinkedAccount() === true)
        {
            $validationFields                  = RequiredFields::MARKETPLACE_ACCOUNT_FIELDS;
            $validationSelectiveRequiredFields = [];
            $validationOptionalFields          = [];

            $parentMerchant = $merchant->parent;

            //
            // If the linked account's parent was flagged by admins,
            // linked accounts need to add additional KYC details and
            // documents before allowing the merchant to submit the form
            //
            if ($parentMerchant->linkedAccountsRequireKyc() === true)
            {
                $kycValidationFields = RequiredFields::MARKETPLACE_ACCOUNT_KYC_FIELDS;

                $validationFields = array_merge($validationFields, $kycValidationFields);
            }
        }

        return [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields];
    }

    //todo:: use combined activation function in account entity
    protected function getCombinedActivationStatusForLinkedAccounts(Entity $merchantDetails)
    {
        $activationStatus = $merchantDetails->getActivationStatus();

        $bankDetailsVerificationStatus = $merchantDetails->getBankDetailsVerificationStatus();

        if ($activationStatus === null)
        {
            return null;
        }

        $combinedActivationStatus = null;

        switch ([$activationStatus, $bankDetailsVerificationStatus])
        {
            case [Status::ACTIVATED, BvsValidationConstants::VERIFIED]:
            {
                return AccountConstants::ACTIVATED;
            }
            case [Status::ACTIVATED, BvsValidationConstants::INCORRECT_DETAILS]:
            case [Status::ACTIVATED, BvsValidationConstants::NOT_MATCHED]:
            {
                return AccountConstants::VERIFICATION_FAILED;
            }
            default:
                return AccountConstants::VERIFICATION_PENDING;
        }
    }

    public function createResponse(Entity $merchantDetails): array
    {
        $startTime = microtime(true);

        list($response, $merchantDetails) = Tracer::inSpan(['name' => 'create_response.refreshing_entities'], function() use ($merchantDetails) {

            $response = $merchantDetails->toArrayPublic();
            //
            // refreshing the merchant relation here as createResponse is called at many places
            // just after updating the merchant entity
            //
            $merchantDetails->load('merchant');

            $merchantDetails->load('avgOrderValue');

            $merchantDetails->load('tnc');

            $merchantDetails->load('verificationDetail');

            $merchantDetails->load('businessDetail');

            return [$response, $merchantDetails];
        });

        $merchant                = $merchantDetails->merchant;
        $merchantBusinessDetails = $merchantDetails->businessDetail;

        if ($merchant->isLinkedAccount() === true)
        {
            $parentMerchant = $merchant->parent;

            //
            // set key `need_kyc` for the client to determine where full KYC is needed
            // for a linked accounts activation
            //
            $response['need_kyc']                  = (int) $parentMerchant->linkedAccountsRequireKyc();
            $response['linked_account']            = true;
            $response['marketplace_merchant_name'] = $parentMerchant->getName();
            $response['marketplace_merchant_id']   = $parentMerchant->getId();

            // If linked account and penny testing feature enabled on parent merchant, modify the activation_status based
            // on bank_detail_verification_status and
            // activation_status with the new status null, activated, verification_pending, verification_failed
            if ($merchant->isFeatureEnabledOnParentMerchant(FeatureConstants::ROUTE_LA_PENNY_TESTING) === true)
            {
                $response[Entity::ACTIVATION_STATUS] = $this->getCombinedActivationStatusForLinkedAccounts($merchantDetails);

                $response[BvsValidationConstants::BANK_DETAILS_VERIFICATION_ERROR] = $this->getBankDetailsVerificationError($merchantDetails);
            }
        }

        $currentActivationState = $merchant->currentActivationState();

        if ((empty($currentActivationState) === false) and
            ($currentActivationState->name === Status::REJECTED))
        {
            $rejectionReasons = $currentActivationState->rejectionReasons()->get();

            $response[Entity::REJECTION_REASONS] = $rejectionReasons->toArrayPublic();
        }

        $response = Tracer::inSpan(['name' => 'create_response.set_verification_details'], function() use ($merchantDetails, $merchant, $response) {

            $response = $this->setVerificationDetails($merchantDetails, $merchant, $response);

            return $response;
        });

        $response = Tracer::inSpan(['name' => 'create_response.adding_relevant_entity_details'], function() use ($merchant, $merchantDetails, $response, $merchantBusinessDetails) {

            $hardEscalationLevel4 = $this->repo->merchant_auto_kyc_escalations->fetchEscalationsForMerchantAndTypeAndLevel
            ($merchant->getMerchantId(), Merchant\AutoKyc\Escalations\Constants::HARD_LIMIT, 4);

            $isDedupeBlocked = $this->dedupeCore->isDedupeBlocked($merchant);

            if ($merchantDetails->getActivationFormMilestone() === DEConstants::L1_SUBMISSION)
            {
                /*
                 * After L1 submission we do not want to block users even if they are dedupe-blocked case
                 */
                $isDedupeBlocked = false;
            }

            $isDedupeMatch = $this->dedupeCore->isMerchantImpersonated($merchant);
            $dedupe        = [
                'isMatch'       => $isDedupeMatch,
                'isUnderReview' => !$isDedupeBlocked
            ];

            $response[Merchant\Entity::ACTIVATED]                     = (int) $merchant->isActivated();
            $response[Merchant\Entity::LIVE]                          = $merchant->isLive();
            $response[Merchant\Entity::INTERNATIONAL]                 = $merchant->isInternational();
            $response[Constants::MERCHANT]                            = $merchant->toArrayPublic();
            $response[Entity::STAKEHOLDER]                            = $merchantDetails->stakeholder;
            $response[Entity::MERCHANT_AVG_ORDER_VALUE]               = $merchantDetails->avgOrderValue;
            $response[Entity::ACTIVATION_PROGRESS]                    = $response['verification'][Entity::ACTIVATION_PROGRESS];
            $response[Entity::MERCHANT_VERIFICATION_DETAIL]           = $merchantDetails->verificationDetail;
            $response['dedupe']                                       = $dedupe;
            $response['isDedupe']                                     = $isDedupeBlocked;
            $response['isAutoKycDone']                                = $this->isAutoKycDone($merchantDetails);
            $response['isHardLimitReached']                           = empty($hardEscalationLevel4) ? false : true;
            $response['activationStatusChangeLogs']                   = $this->getStatusChangeLogs($merchant);
            $response[Entity::MERCHANT_BUSINESS_DETAIL]               = $merchantBusinessDetails;
            $response[BusinessDetailEntity::BUSINESS_PARENT_CATEGORY] = $merchantBusinessDetails[BusinessDetailEntity::BUSINESS_PARENT_CATEGORY];

            if (empty($merchantDetails->getKycClarificationReasons()) === false)
            {
                $response[Entity::KYC_CLARIFICATION_REASONS] = $this->getUpdatedKycClarificationReasons([], $merchantDetails->getMerchantId());
            }

            return $response;
        });

        $response = Tracer::inSpan(['name' => 'create_response.extra_details'], function() use ($response, $merchant, $merchantBusinessDetails, $merchantDetails) {
            $appUrls = [BusinessDetailConstants::PLAYSTORE_URL, BusinessDetailConstants::APPSTORE_URL];

            foreach ($appUrls as $url)
            {
                $response[$url] = $merchantBusinessDetails[BusinessDetailEntity::APP_URLS][$url] ?? '';
            }

            $isMtuCouponExperimentEnabled = (new Merchant\Core)->isRazorxExperimentEnable(
                $merchant->getId(),
                Merchant\RazorxTreatment::MTU_COUPON_CODE);

            if ($isMtuCouponExperimentEnabled === true)
            {
                $response['showMtuPopup'] = $this->isMerchantEligibleForMtuPopup($merchant);
            }

            if ($this->isMerchantTncApplicable($merchant) === true)
            {
                $response[Entity::MERCHANT_TNC] = (new Merchant\Tnc\Core)->getTncDetails($merchantDetails->tnc);
            }

            $response['isSubMerchant'] = (new AccessMapCore)->isSubMerchant($merchant->getMerchantId());

            $response = $this->appendBankingSpecificDetails($response, $merchant);

            return $response;
        });

        // append error codes to response
        // add this in try-catch so that it doesn't affect the usual response flow.
        try
        {
            $error_status = $this->fetchVerificationErrorCodes($merchant);
            $this->trace->info(TraceCode::MERCHANT_DETAIL_VERIFICATION_ERROR_RESPONSE, [
                '$error_status' => $error_status,
            ]);
            $response[DetailConstants::VERIFICATION_ERROR_CODES] = $error_status;
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::MERCHANT_DETAIL_VERIFICATION_ERROR_RESPONSE, [
                'error' => $e,
            ]);
            $response[DetailConstants::VERIFICATION_ERROR_CODES] = [];
        }
        $this->trace->info(TraceCode::MERCHANT_CREATE_RESPONSE_LATENCY, [
            'merchant_id' => $merchantDetails->getId(),
            'duration'    => (microtime(true) - $startTime) * 1000,
        ]);

        return $response;
    }

    private function getStatusChangeLogs(Merchant\Entity $merchant)
    {
        $statusChangeLogs = (new Merchant\Core)->getActivationStatusChangeLog($merchant);

        return array_column($statusChangeLogs->toArray(), 'name');
    }

    private function isMerchantEligibleForMtuPopup(Merchant\Entity $merchant): bool
    {
        if ($merchant->getOrgId() !== Org\Entity::RAZORPAY_ORG_ID)
        {
            return false;
        }

        if ($merchant->isActivated() === false or
            $merchant->isLive() === false)
        {
            return false;
        }

        if ((new Merchant\Core)->isRegularMerchant($merchant) === false)
        {
            return false;
        }

        if ((new Merchant\M2MReferral\Service())->isReferralMerchant($merchant) === true)
        {
            return false;
        }

        $query = [
            'filters'      => [
                'default' => [
                    [
                        'created_at'    => [
                            'gte' => $merchant->getCreatedAt(),
                            'lte' => Carbon::now()->getTimestamp()
                        ],
                        'authorized_at' => [
                            'gt' => 0
                        ],
                    ],
                ],
            ],
            'aggregations' => [
                'firstTransaction' => [
                    'agg_type' => 'oldest',
                    'details'  => [
                        'index'         => 'payments',
                        'column'        => 'created_at',
                        'mode'          => 'live',
                        'limit'         => 1,
                        'result_fields' => ['base_amount'],
                    ],
                ],
            ],
        ];

        $query = (new Merchant\Core)->processMerchantAnalyticsQuery($merchant->getId(), $query);

        $aggregateData = $this->app['eventManager']->query($query);

        $firstTransaction = $aggregateData['firstTransaction']['result'][0]['base_amount'] ?? 0;

        if ($firstTransaction > 0)
        {
            return false;
        }

        if (Carbon::now()->subDays(2)->getTimestamp() < $merchant->getActivatedAt())
        {
            return false;
        }

        $isCouponCodeAlreadyApplied = (new Coupon\Core)->isAnyCouponApplied(
            $merchant);

        if ($isCouponCodeAlreadyApplied === true)
        {
            return false;
        }

        return true;
    }

    /**
     * Checks that
     * The key that needs to be validated is not present in the merchant details array
     * Or, if the value for the key is null
     * Or, if the value is not a boolean and is empty (empty(false) => true)
     *
     * @param string $key
     * @param array  $merchantDetailsArr
     *
     * @return bool
     */
    private function isKeyNotInMerchantDetail(string $key, array $merchantDetailsArr): bool
    {
        return ((array_key_exists($key, $merchantDetailsArr) === false) or
                (is_null($merchantDetailsArr[$key]) === true) or
                ((is_bool($merchantDetailsArr[$key]) !== true) and
                 (empty($merchantDetailsArr[$key]) === true)));
    }

    private function appendBankingSpecificDetails(array $response, Merchant\Entity $merchant): array
    {
        $balance = $this->repo
            ->balance
            ->getMerchantBalanceByTypeAndAccountType(
                $merchant->getId(),
                Product::BANKING,
                Merchant\Balance\AccountType::SHARED);

        $creditBalance = [];

        if (empty($balance) === false)
        {
            $bankingAccount = $this->repo->banking_account->getActivatedBankingAccountFromBalanceId($balance->getId());

            $response[Merchant\Entity::BANKING_ACCOUNT] = $bankingAccount->toArrayPublic();

            $creditBalance = $this->fetchBankingCreditBalances($merchant->getId(), Product::BANKING, $balance->getAccountType(), $bankingAccount->getPublicId());
        }

        if (empty($creditBalance) == true)
        {

            $creditBalance = $this->fetchBankingCreditBalances($merchant->getId(), Product::BANKING, null, null);
        }

        $response[Merchant\Entity::CREDIT_BALANCE] = $creditBalance;

        return $response;
    }

    protected function fetchBankingCreditBalances($merchantId, $product, $accountType, $bankingAccountId)
    {
        $creditBalances = $this->repo
            ->credits
            ->getTypeAggregatedMerchantCreditsForProductForDashboard(
                $merchantId,
                $product);

        $merchant = $this->repo->merchant->find($merchantId);

        // Calling ledger when merchant has "ledger_journal_reads" feature flag enabled
        // and balance is of type "shared".
        if (($merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true) &&
            ($accountType === Merchant\Balance\AccountType::SHARED))
        {
            $ledgerResponse = (new LedgerCore())->fetchBalanceFromLedger($merchantId, $bankingAccountId);

            if ((empty($ledgerResponse) === false) &&
                (empty($ledgerResponse[LedgerCore::REWARD_BALANCE]) === false) &&
                (empty($ledgerResponse[LedgerCore::REWARD_BALANCE][LedgerCore::BALANCE]) === false))
            {
                (new LedgerCore())->constructCreditBalanceFromLedger($creditBalances, $ledgerResponse);
            }
        }

        return $creditBalances;
    }

    /**
     * @param string $reviewerId
     * @param array  $merchants
     *
     * @return array
     */
    public function bulkAssignReviewer(string $reviewerId, array $merchants): array
    {
        $success = 0;

        $failedItems = [];

        try
        {
            $reviewerIdCopy = $reviewerId;

            AdminEntity::verifyIdAndStripSign($reviewerIdCopy);

            $this->repo->admin->findOrFailPublic($reviewerIdCopy);
        }
        catch (\Exception $e)
        {
            $response = [
                'success' => 0,
                'failed'  => count($merchants),
                'error'   => $e->getMessage(),
            ];

            return $response;
        }

        foreach ($merchants as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $this->editMerchantDetailFields($merchant, [Entity::REVIEWER_ID => $reviewerId]);

                $success++;
            }
            catch (\Exception $e)
            {
                $failedItems[] = [
                    Entity::MERCHANT_ID => $merchantId,
                    'error'             => $e->getMessage()
                ];
            }
        }

        $response = [
            'success'     => $success,
            'failed'      => count($failedItems),
            'failedItems' => $failedItems,
        ];

        return $response;
    }

    public function merchantsMtuUpdate(array $merchants, int $value): array
    {
        $success = 0;

        $failedItems = [];

        foreach ($merchants as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $this->editMerchantDetailFields($merchant, [Entity::LIVE_TRANSACTION_DONE => $value]);

                $success++;

                $this->trace->info(TraceCode::MERCHANT_MTU_UPDATE_SUCCESS, ['id' => $merchantId]);
            }
            catch (\Exception $e)
            {
                $failedItems[] = [
                    Entity::MERCHANT_ID => $merchantId,
                    'error'             => $e->getMessage()
                ];

                $this->trace->info(TraceCode::MERCHANT_MTU_UPDATE_FAILURE, ['id' => $merchantId]);
            }
        }

        $response = [
            'success'     => $success,
            'failed'      => count($failedItems),
            'failedItems' => $failedItems,
        ];

        return $response;
    }

    protected function fetchPoiMetricDimensions(Entity $merchantDetail): array
    {
        return [
            Detail\Constants::POI_STATUS => $merchantDetail->getPoiVerificationStatus()
        ];
    }

    protected function fetchBusinessPanMetricDimensions(Entity $merchantDetail): array
    {
        return [
            Detail\Constants::COMPANY_PAN_VERIFICATION_STATUS => $merchantDetail->getPoiVerificationStatus()
        ];
    }

    /**
     * This function is used for creating activation flow metric dimensions
     *
     * @param string(activation flow)
     * @param array $extra
     *
     * @return array
     *
     */

    protected function fetchActivationMetricDimensions(string $label = null, array $extra = []): array
    {
        return $extra + [
                Metric::ACTIVATION_FLOW => $label
            ];
    }

    /**
     * This function is used for creating metric dimensions for activation status transitions
     *
     * @param string $previous_status
     * @param string $updated_status
     * @param array  $extra
     *
     * @return array
     *
     */

    protected function fetchActivationStatusTransitionMetricDimensions(string $updated_status,
                                                                       string $previous_status = null,
                                                                       array $extra = []): array
    {
        return $extra + [
                Metric::PREVIOUS_ACTIVATION_STATUS => $previous_status,
                Metric::UPDATED_ACTIVATION_STATUS  => $updated_status
            ];
    }

    public static function shouldSkipKycDocuments(Entity $merchantDetails): bool
    {
        $orgId = $merchantDetails->merchant->getOrgId();

        if (empty($orgId) === true)
        {
            return false;
        }

        if ($orgId === ORG_ENTITY::AXIS_ORG_ID)
        {
            return true;
        }

        return false;
    }

    /**
     * SubMerchant batch upload flow allows skipping bank account registration as the partner
     * is there liable for the risk and the submerchants must be activated directly.
     *
     * @return bool
     */
    public static function shouldSkipBankAccountRegistration(): bool
    {
        if (app('basicauth')->isBatchFlow() === false)
        {
            return false;
        }

        $batchContext = app('basicauth')->getBatchContext();

        $batchName                   = $batchContext['type'] ?? null;
        $skipBankAccountRegistration = $batchContext['data'][Merchant\Entity::SKIP_BA_REGISTRATION] ?? false;

        return (($batchName === Type::SUB_MERCHANT) and ($skipBankAccountRegistration === true));
    }

    protected function shouldSkipPOADocuments(Entity $merchantDetails): bool
    {
        if ($this->isAadhaarEsignVerificationRequired($merchantDetails) === false)
        {
            return false;
        }

        $stakeholder = $merchantDetails->stakeholder;

        if (empty($stakeholder) === true)
        {
            return false;
        }

        $experimentEnabled = $this->mcore->isRazorxExperimentEnable($merchantDetails->getMerchantId(),
            RazorxTreatment::SKIP_POA_DOCUMENT_FUNCTIONALITY);

        if ($experimentEnabled === false)
        {
            return false;
        }

        return $stakeholder->getAadhaarEsignStatus() === 'verified';
    }

    public function publicAttemptPennyTesting(Entity $merchantDetails, Merchant\Entity $merchant, $bankDetailsUpdated = false)
    {
        $this->attemptPennyTesting($merchantDetails, $merchant, $bankDetailsUpdated);
    }

    /**
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     *
     * @param array           $input
     * @param bool            $bankDetailsUpdated
     *
     * @throws Exception\InvalidPermissionException
     * @throws LogicException
     */
    protected function attemptPennyTesting(Entity $merchantDetails, Merchant\Entity $merchant, $bankDetailsUpdated = false, array $input = [])
    {
        if ($merchant->isLinkedAccount() === true and
            $merchant->isFeatureEnabledOnParentMerchant(FeatureConstants::ROUTE_LA_PENNY_TESTING) === false)
        {
            return;
        }

        $requiredFields = [
            Entity::BANK_ACCOUNT_NUMBER,
            Entity::BANK_BRANCH_IFSC
        ];

        $isAutoKycAttemptRequired = $this->isAutoKycAttemptRequired($requiredFields,
                                                                    $requiredFields,
                                                                    $input,
                                                                    DetailEntity::BANK_DETAILS_VERIFICATION_STATUS,
                                                                    [BvsValidationConstants::FAILED],
                                                                    $merchant->getId());

        if (($merchantDetails->getBankDetailsVerificationStatus() === DEConstants::VERIFIED or
             $merchantDetails->getBankDetailsVerificationStatus() === BvsValidationConstants::INITIATED) and
            $isAutoKycAttemptRequired === false)
        {
            return;
        }

        if (empty($merchantDetails->getBankAccountNumber()) === true or
            empty($merchantDetails->getBankBranchIfsc()) === true)
        {
            return;
        }

        if ((new Merchant\Core())->isAutoKycEnabled($merchantDetails, $merchant) === false and
            $merchant->isLinkedAccount() === false)
        {
            return;
        }

        if ($this->shouldSkipBankAccountRegistration() == true and $merchant->isLinkedAccount() === false)
        {
            return;
        }

        $keys = [
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT
        ];

        $data = (new StoreCore())->fetchValuesFromStore($merchantDetails->getMerchantId(),
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        StoreConstants::INTERNAL);

        $pennyTestingAttemptsCount = $data[ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT] ?? 0;

        $maxPennyTestCountAllowed = env(DEConstants::BANK_ACCOUNT_VERIFICATION_MAX_ATTEMPT_COUNT);

        //do not perform penny testing
        if ($pennyTestingAttemptsCount >= $maxPennyTestCountAllowed)
        {
            return;
        }

        if ($isAutoKycAttemptRequired === true or
            ($pennyTestingAttemptsCount == 0 and $merchantDetails->isSubmitted() === false) or
            $bankDetailsUpdated === true)
        {

            $this->updateDocumentVerificationStatus($merchant, $merchantDetails, Entity::BANK_ACCOUNT_NUMBER);

            $eventAttributes = [
                'time_stamp'    => Carbon::now()->getTimestamp(),
                'artefact_type' => 'bank_account'
            ];

            $this->app['segment-analytics']->pushTrackEvent($merchant, $eventAttributes, SegmentEvent::RETRY_INPUT_ACTIVATION_FORM);
        }
    }


    public function isAdditionalFieldRequired($field)
    {
        $merchantDetail = $this->getMerchantDetails($this->merchant);

        $KYClarificationReasons = $merchantDetail->getKycClarificationReasons();

        if (empty($KYClarificationReasons) === true)
        {
            return false;
        }

        $additionalDetail = $KYClarificationReasons[Entity::ADDITIONAL_DETAILS] ?? [];

        return array_key_exists($field, $additionalDetail) === true;
    }

    /**
     * Set activation status to activated if
     * a) If merchant belongs to unregistered business type then activate merchant if
     *  a.1) poaVerificationStatus is Verified and
     *  a.2) bankDetailsVerificationStatus is Verified and
     *  a.3) poiVerificationStatus is Verified and
     *
     * Else change set activation status to under review
     *
     * @param Entity $merchantDetails
     *
     * @return string
     */
    public function getApplicableActivationStatus(Entity $merchantDetails): string
    {
        if ($merchantDetails->merchant->isNoDocOnboardingEnabled() === true)
        {
            $status = $this->getApplicableActivationStatusForNoDoc($merchantDetails);

            $this->trace->info(TraceCode::APPLICABLE_ACTIVATION_STATUS_FOR_NO_DOC, [
                'merchant_id'       => $merchantDetails->getId(),
                'activation_status' => $status,
            ]);

            return $status;
        }

        $autoKyc = $this->isAutoKycDone($merchantDetails);

        if ($autoKyc)
        {
            switch ($merchantDetails->getBusinessType())
            {
                case BusinessType::NOT_YET_REGISTERED:
                case BusinessType::INDIVIDUAL:
                    return $this->getApplicableActivationStatusForUnregisteredMerchant($merchantDetails);

                case BusinessType::PROPRIETORSHIP:
                case BusinessType::PARTNERSHIP:
                case BusinessType::PRIVATE_LIMITED:
                case BusinessType::PUBLIC_LIMITED:
                case BusinessType::LLP:
                case BusinessType::HUF:
                case BusinessType::TRUST:
                case BusinessType::SOCIETY:
                    return $this->getApplicableActivationStatusForRegisteredMerchant($merchantDetails);
            }
        }

        return Status::UNDER_REVIEW;
    }

    private function getApplicableActivationStatusForRegisteredMerchant($merchantDetails)
    {
        if ($merchantDetails->merchant->getOrgId() !== Org\Entity::RAZORPAY_ORG_ID)
        {
            return Status::UNDER_REVIEW;
        }

        $excludeActivationStatusList = [
            Status::NEEDS_CLARIFICATION,
            Status::ACTIVATED,
            Status::REJECTED
        ];

        $currentActivationStatus = $merchantDetails->getActivationStatus();

        $currentActivationFlow = $merchantDetails->getActivationFlow();

        if (empty($currentActivationFlow) === true and
            $merchantDetails->canDetermineActivationFlow())
        {
            $currentActivationFlow = $this->getActivationFlow(
                $merchantDetails->merchant, $merchantDetails, null, false);
        }

        $isWhitelisted = ($currentActivationFlow === ActivationFlow::WHITELIST);

        $isImpersonated = $this->dedupeCore->isMerchantImpersonated($merchantDetails->merchant);

        if (($isWhitelisted === true) and
            ($isImpersonated === false) and
            (in_array($currentActivationStatus, $excludeActivationStatusList) === false))
        {
            return Status::ACTIVATED_MCC_PENDING;
        }

        return Status::UNDER_REVIEW;
    }

    private function getApplicableActivationStatusForUnregisteredMerchant($merchantDetails)
    {
        if ($merchantDetails->merchant->getOrgId() !== Org\Entity::RAZORPAY_ORG_ID)
        {
            return Status::UNDER_REVIEW;
        }

        $excludeActivationStatusList = [
            Status::NEEDS_CLARIFICATION,
            Status::ACTIVATED,
            Status::REJECTED
        ];

        $currentActivationStatus = $merchantDetails->getActivationStatus();

        $isImpersonated = $this->dedupeCore->isMerchantImpersonated($merchantDetails->merchant);

        if (($isImpersonated === false) and
            (in_array($currentActivationStatus, $excludeActivationStatusList) === false))
        {
            return Status::ACTIVATED_MCC_PENDING;
        }

        return Status::UNDER_REVIEW;
    }

    private function getApplicableActivationStatusForNoDoc(Entity $merchantDetails): string
    {
        $autoKycDone = $this->isAutoKycDone($merchantDetails);

        if ($autoKycDone === true)
        {
            if ($merchantDetails->getGstinVerificationStatus() === DetailConstants::VERIFIED)
            {
                return Status::ACTIVATED;
            }
            else
            {
                return Status::ACTIVATED_KYC_PENDING;
            }
        }

        return Status::UNDER_REVIEW;
    }

    public function isAutoKycDone(Entity $merchantDetails)
    {
        $businessType = $merchantDetails->getBusinessType();

        if (empty($businessType) === true)
        {
            return false;
        }

        if ($merchantDetails->merchant->isNoDocOnboardingEnabled() === true)
        {
            $gstValidationCompleted = (new UpdateContextRequirements())->isNoDocGstValidationCompleted($merchantDetails);

            if ($gstValidationCompleted === false)
            {
                return false;
            }

            $conditions = $this->fetchAutoKycConditionsForNoDoc($merchantDetails);

            $this->trace->info(TraceCode::AUTO_KYC_CONDITIONS_FOR_NO_DOC, [
                'merchant_id'   => $merchantDetails->getId(),
                'business_type' => $businessType,
            ]);
        }
        else
        {
            if (isset(AutoKyc\Constants::AUTO_KYC_VERIFICATION_CONDITIONS[$businessType]) === false)
            {
                return false;
            }
            else
            {
                $conditions = AutoKyc\Constants::AUTO_KYC_VERIFICATION_CONDITIONS[$businessType];
            }
        }

        $autoKycDone = (new Parser)->parse($conditions, function($key, $condition) use ($merchantDetails) {

            $entity = $condition[AutoKyc\Constants::ENTITY];
            $in     = $condition[AutoKyc\Constants::IN];

            switch ($entity)
            {
                case E::MERCHANT_DETAIL:
                    return $this->verifyMerchantDetailCondition($merchantDetails, $key, $in);
                case E::STAKEHOLDER:
                    return $this->verifyStakeHolderCondition($merchantDetails, $key, $in);
                case E::MERCHANT_VERIFICATION_DETAIL:
                    return $this->verifyBusinessVerificationCondition($merchantDetails, $key, $in);
            }
        });

        if ($this->isAutoKycEnabled($merchantDetails) === false)
        {
            return false;
        }

        return $autoKycDone;
    }

    private function isAutoKycEnabled($merchantDetails)
    {
        if ($merchantDetails->getBusinessType() === BusinessType::PARTNERSHIP
            and $merchantDetails->merchant->isNoDocOnboardingEnabled() === false)
        {
            $isExperimentEnabledForPartnershipBiz = (new Merchant\Core)->isRazorxExperimentEnable($merchantDetails->getMerchantId(),
                                                                                                  RazorxTreatment::AUTO_KYC_PARTNERSHIP);

            if ($isExperimentEnabledForPartnershipBiz === false)
            {
                return false;
            }
        }

        if ($merchantDetails->getBusinessType() === BusinessType::TRUST
            or $merchantDetails->getBusinessType() === BusinessType::SOCIETY)
        {
            $isExperimentEnabledForTrustSocietyAutoKyc = (new Merchant\Core)->isRazorxExperimentEnable($merchantDetails->getMerchantId(),
                                                                                                       RazorxTreatment::AUTO_KYC_TRUST_SOCIETY);

            if ($isExperimentEnabledForTrustSocietyAutoKyc === false)
            {
                return false;
            }
        }

        return true;
    }


    private function fetchAutoKycConditionsForNoDoc(Entity $merchantDetails)
    {
        $businessType = $merchantDetails->getBusinessType();

        $conditions = AutoKyc\Constants::AUTO_KYC_VERIFICATION_CONDITIONS_NO_DOC[Constants::DEFAULT];

        if (isset(AutoKyc\Constants::AUTO_KYC_VERIFICATION_CONDITIONS_NO_DOC[$businessType]) === true)
        {
            $conditions = AutoKyc\Constants::AUTO_KYC_VERIFICATION_CONDITIONS_NO_DOC[$businessType];
        }

        $updateContextRequirement = (new UpdateContextRequirements());

        $isGstValidationCompleted = $updateContextRequirement->isNoDocGstValidationCompleted($merchantDetails);

        $isGstStatusInTerminalState = $updateContextRequirement->isArtifactStatusInTerminalState($merchantDetails->getGstinVerificationStatus());

        if ($isGstValidationCompleted === true and $isGstStatusInTerminalState === true)
        {
            $conditions = array_merge($conditions[Operator:: AND], [Entity::GSTIN_VERIFICATION_STATUS => AutoKyc\Constants::GSTIN_CONDITION]);
        }

        return $conditions;
    }

    protected function verifyBusinessVerificationCondition(Entity $merchantDetails, string $key, array $in)
    {
        $businessType = $merchantDetails->getBusinessType();

        [$type, $identifier] = explode('|', $key);

        if ((in_array($businessType, BusinessType::getCOIApplicableBusinessTypes(), true) === true) && ($type == Constant::CERTIFICATE_OF_INCORPORATION))
        {
            $isExperimentEnabledForCOI = (new Merchant\Core)->isRazorxExperimentEnable($merchantDetails->getMerchantId(),
                                                                                       RazorxTreatment::AUTO_KYC_COI);

            $this->trace->info(TraceCode::COI_EXPERIMENT, [
                "merchantId"                 => $merchantDetails->getMerchantId(),
                "isExperimentEnabledForCOI"  => $isExperimentEnabledForCOI,
                "type"                       => $type,
                "merchant_activation_status" => $merchantDetails->getActivationStatus(),
                "bizzType"                   => $businessType,
            ]);

            if ($isExperimentEnabledForCOI === false)
            {
                return false;
            }
        }

        $verificationDetail = $this->repo->merchant_verification_detail->getDetailsForTypeAndIdentifier(
            $merchantDetails->getMerchantId(),
            $type,
            $identifier
        );

        if (empty($verificationDetail) === true)
        {
            return false;
        }

        return in_array(
            $verificationDetail->getAttribute(Merchant\VerificationDetail\Entity::STATUS),
            $in,
            true
        );
    }

    protected function verifyMerchantDetailCondition(Entity $merchantDetails, string $key, array $in)
    {
        $isAadhaarEsignRequired = $this->isAadhaarEsignVerificationRequired($merchantDetails);

        if ($isAadhaarEsignRequired === true and $key === DetailEntity::POA_VERIFICATION_STATUS)
        {
            $isExperimentEnabled = $this->mcore->isRazorxExperimentEnable(
                $merchantDetails->getMerchantId(), RazorxTreatment::POA_VERIFICATION_AUTO_KYC);

            if ($isExperimentEnabled === false)
            {
                return false;
            }
        }

        return in_array($merchantDetails->getAttribute($key), $in, true);
    }

    protected function verifyStakeHolderCondition(Entity $merchantDetails, string $key, array $in)
    {
        $isAadhaarEsignRequired = $this->isAadhaarEsignVerificationRequired($merchantDetails);

        if (($isAadhaarEsignRequired === true) and
            (empty($merchantDetails->stakeholder) === false))
        {
            return in_array($merchantDetails->stakeholder->getAttribute($key), $in, true);
        }

        return true;
    }

    /**
     * * Format of defining required documents
     *
     * {
     * "document1": [
     *   [ "document_type1" , "document_type2"],
     *   [ "document_type3" , "document_type2"]
     * ],
     * "document2": [
     *   [ "document_type4" , "document_type5"],
     *   [ "document_type6" , "document_type7"]
     * ]
     * }
     *
     * explanation : For submitting L2 form  document1 and document2 fields are required
     * for document1 field user can submit (document_type1, document_type2) or (document_type3, document_type2)
     * for document2 field user can submit (document_type4, document_type5) or (document_type6, document_type7)
     *
     * $requiredDocumentField : document1,document2
     * $documentGroup         :   ["document_type6","document_type7"]
     *
     * @param Merchant\Entity $merchant
     * @param                 $validationDocumentFields
     * @param                 $documentsResponse
     * @param                 $requiredFields
     */
    protected function calculateRequiredDocumentFields(Merchant\Entity $merchant, $validationDocumentFields, $documentsResponse, &$requiredFields): void
    {
        foreach ($validationDocumentFields as $requiredDocumentField => $documentGroups)
        {
            //
            // if merchant uploads all documents of a document group then
            // we consider  required document field to be filled
            //
            $isFieldPresent = array_reduce($documentGroups, function($isFieldPresent, $documentGroup) use ($documentsResponse) {
                $isDocumentGroupFilled = count(array_diff($documentGroup, array_keys($documentsResponse))) === 0;

                $isFieldPresent = ($isFieldPresent or $isDocumentGroupFilled);

                return $isFieldPresent;

            }, false);

            if ($isFieldPresent === false)
            {
                $isNoDocEnabledAndGmvLimitExhausted = (new Merchant\AccountV2\Core())->isNoDocEnabledAndGmvLimitExhausted($merchant);

                if ($isNoDocEnabledAndGmvLimitExhausted === true)
                {
                    foreach ($documentGroups as $documentGroup)
                    {
                        $requiredFields = array_merge($requiredFields, $documentGroup);
                    }
                }
                else
                {
                    $requiredFields = array_merge($requiredFields, $documentGroups[0]);
                }
            }
        }
    }

    /**
     * @param Entity $merchantDetails
     * @param array  $input
     *
     * @return mixed
     * @throws \Throwable
     */
    public function addAdditionalWebsiteDetails(Entity $merchantDetails, array $input)
    {
        $merchantDetails->getValidator()->validateInput('additionalWebsites', $input);

        $this->trace->info(
            TraceCode::MERCHANT_ADD_ADDITIONAL_WEBSITE_DETAILS,
            ['input' => $input]);

        $merchant = $merchantDetails->merchant;

        if ((empty($merchantDetails->getWebsite()) === true) and
            (empty($merchantDetails->getAdditionalWebsites()) === true))
        {
            $this->trace->info(
                TraceCode::MERCHANT_MARK_HAS_KEY_ACCESS,
                [
                    'Additional_website' => $input[Entity::ADDITIONAL_WEBSITE],
                    'has_key_access'     => $merchant->getHasKeyAccess(),
                    'merchant_id'        => $merchant->getId()
                ]);

            $merchant->setHasKeyAccess(true);
        }

        $merchantCore = new Merchant\Core();

        $merchantDetailsInput = $this->addAdditionalWebsites($input[Entity::ADDITIONAL_WEBSITE], $merchantDetails);

        $merchantDetails->edit($merchantDetailsInput);

        $domain = (new Merchant\TLDExtract)->getEffectiveTLDPlusOne($input[Entity::ADDITIONAL_WEBSITE]);

        $merchantCore->addDomainInWhitelistedDomain($merchant, $domain);

        return $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails, $input, $merchant) {

            $this->repo->saveOrFail($merchantDetails);

            $this->repo->saveOrFail($merchant);

            $response = [];

            $response[Entity::ADDITIONAL_WEBSITES] = $merchantDetails->getAdditionalWebsites();

            return $response;
        });
    }

    public function deleteAdditionalWebsites(Entity $merchantDetails, array $input)
    {
        $merchantDetails->getValidator()->validateInput('deleteAdditionalWebsites', $input);

        $this->trace->info(
            TraceCode::MERCHANT_DELETE_ADDITIONAL_WEBSITES,
            [
                'input' => $input,
            ]);

        $newAdditionalWebsites = array_values(array_diff($merchantDetails->getAdditionalWebsites(), $input[Entity::ADDITIONAL_WEBSITES]));

        $merchantDetails->setAdditionalWebsites($newAdditionalWebsites);

        $this->repo->merchant_detail->saveOrFail($merchantDetails);

        $response[Entity::ADDITIONAL_WEBSITES] = $merchantDetails->getAdditionalWebsites();

        return $response;
    }

    /**
     * @param string $website
     * @param Entity $merchantDetails
     *
     * @return array
     */
    protected function addAdditionalWebsites(string $website, Entity $merchantDetails)
    {
        $businessWebsite = $merchantDetails->getWebsite();

        $additionalWebsites = $merchantDetails->getAdditionalWebsites() ?? [];

        $merchantDetailsInput = [];

        if (($website !== $businessWebsite) and
            (in_array($website, $additionalWebsites) === false))
        {
            array_push($additionalWebsites, $website);

            $merchantDetailsInput[Entity::ADDITIONAL_WEBSITES] = $additionalWebsites;
        }

        return $merchantDetailsInput;
    }

    /**
     * Returns required document for L2 submission
     *
     * @param Entity $merchantDetails
     *
     * @return array
     */
    private function getRequireActivationDocuments(Entity $merchantDetails): array
    {
        $response = $this->createResponse($merchantDetails);

        $requiredFields = $response[DEConstants::VERIFICATION][DEConstants::REQUIRED_FIELDS] ?? [];

        $requiredDocuments = [];

        foreach ($requiredFields as $requiredField)
        {
            $documentFields = ValidationFields::getDocumentsRequired($requiredField);

            if (empty($documentFields) === false)
            {
                $requiredDocuments = array_merge_recursive($requiredDocuments, $documentFields);
            }
        }

        return $requiredDocuments;
    }

    public function sendRejectionEmail($merchant)
    {
        if ($merchant->getEmail() == null)
        {
            return;
        }

        $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

        $data = [
            'name'   => $merchant->getName(),
            'email'  => $merchant->getEmail(),
            'id'     => $merchant->getId(),
            'org_id' => $org->getId(),
        ];

        $data['email_logo'] = $org->getEmailLogo();
        // For marketplace accounts, send this email to the parent merchant
        if ($merchant->isLinkedAccount() === true)
        {
            $data['email'] = $merchant->parent->getEmail();
        }

        $rejectionMail = new RejectionEmail($data, $org->toArray());

        Mail::queue($rejectionMail);
    }

    public function sendRejectionEmailProof($merchant)
    {
        $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

        $data = [
            'name'  => $merchant->getName(),
            'email' => $merchant->getEmail(),
            'id'    => $merchant->getId(),
        ];

        // For marketplace accounts, send this email to the parent merchant
        if ($merchant->isLinkedAccount() === true)
        {
            $data['email'] = $merchant->parent->getEmail();
        }

        $rejectionMail = new RejectionSettlement($data, $org->toArray());

        Mail::queue($rejectionMail);
    }


    /**
     * @param Merchant\Entity $merchant
     * @param                 $international
     *
     * @throws \RZP\Exception\BadRequestException
     *
     * Sets international_activation_flow to blacklist if its being disabled by admin. .
     * SHOULD BE CALLED ONLY IN CASE OF ADMIN FLOW
     */
    public function adminUpdateInternationalActivationFlow(Merchant\Entity $merchant, $international)
    {
        $merchantDetail = $merchant->merchantDetail;

        $internationalActivationFlow = null;

        if ($international === Constants::$internationalActionMapping[Action::ENABLE_INTERNATIONAL] and
            empty($merchantDetail->getInternationalActivationFlow() === false))
        {
            return;
        }
        elseif ($international === Constants::$internationalActionMapping[Action::ENABLE_INTERNATIONAL]
                and empty($merchantDetail->getInternationalActivationFlow()) === true)
        {
            $internationalActivationFlow = (new Detail\InternationalCore)->getInternationalActivationFlow($merchant);
        }
        elseif ($international !== Constants::$internationalActionMapping[Action::ENABLE_INTERNATIONAL])
        {
            $internationalActivationFlow = Detail\InternationalActivationFlow\InternationalActivationFlow::BLACKLIST;
        }

        $merchantDetail->setInternationalActivationFlow($internationalActivationFlow);

        $this->repo->saveOrFail($merchantDetail);
    }


    /**
     * @param Merchant\Entity      $merchant
     * @param Entity               $merchantDetails
     * @param Merchant\Entity|null $partner
     * @param array|string[]       $activationFlowTypes
     * @param bool                 $batchFlow
     */
    protected function updateActivationFlows(Merchant\Entity $merchant, Merchant\Detail\Entity $merchantDetails,
                                             Merchant\Entity $partner = null,
                                             array $activationFlowTypes = Detail\Constants::ACTIVATION_FLOWS,
                                             bool $batchFlow = false): void
    {
        foreach ($activationFlowTypes as $activationFlowType)
        {
            switch ($activationFlowType)
            {
                case Detail\Constants::ACTIVATION:

                    $this->autoUpdateActivationFlow($merchant, $merchantDetails, $partner, $batchFlow);

                    break;
                case Detail\Constants::INTERNATIONAL_ACTIVATION:

                    $this->autoUpdateInternationalActivationFlow($merchant, $partner);

                    break;
            }
        }
    }

    /**
     * this function is called dynamically to update merchant details through batch action.
     * This unction name is derived from action name.
     *
     * @param string $merchantId
     * @param array  $input
     */
    public function updateEntity(string $merchantId, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        (new Validator())->validateInput('update_entity_batch_action', $input);

        $this->editMerchantDetailFields($merchant, $input);
    }

    /**
     * @throws \Throwable
     */
    public function retryPennyTestingCron()
    {
        $merchantDetails = $this->getMerchantDetailsWithBankDetailsVerificationStatus(BankDetailsVerificationStatus::INITIATED);

        $pennyTesting = new PennyTesting();

        foreach ($merchantDetails as $merchantDetail)
        {
            $isPennyTestingAttemptLessThenMaxAttempt = $pennyTesting->isPennyTestingAttemptLessThenMaxAttempt($merchantDetail);

            $this->trace->info(TraceCode::MERCHANT_PENNY_TESTING_CRON_RETRY, [
                Entity::MERCHANT_ID            => $merchantDetail->getId(),
                Constants::PENNY_TESTING_COUNT => $pennyTesting->getPennyTestingAttempts($merchantDetail),
            ]);

            $this->repo->transactionOnLiveAndTest(function() use ($merchantDetail, $pennyTesting, $isPennyTestingAttemptLessThenMaxAttempt) {

                $merchant = $merchantDetail->merchant;

                if ($isPennyTestingAttemptLessThenMaxAttempt === true)
                {
                    $shouldPerformPennyTesting = $pennyTesting->verifyPennyTestingResults($merchantDetail);

                    if ($shouldPerformPennyTesting === true)
                    {
                        $pennyTesting->triggerPennyTesting($merchantDetail);
                    }
                }
                else
                {
                    $this->markBankDetailsVerificationStatusFailed($merchant, $merchantDetail);
                }

                $this->repo->saveOrFail($merchantDetail);
                $this->repo->saveOrFail($merchant);
            });
        }
    }

    protected function markBankDetailsVerificationStatusFailed(Merchant\Entity $merchant, Entity $merchantDetail)
    {
        $merchantDetail->setBankDetailsVerificationStatus(BankDetailsVerificationStatus::FAILED);

        (new PennyTesting())->updateMerchantContext($merchantDetail, $merchant);
    }

    /**
     * eg. if cron job run for each 2 hour then
     * only those merchant_details will be returned for which penny testing updated before 2 or more hours
     *
     * @param string $status Bank Details Verification Status
     *
     * @return mixed
     */
    protected function getMerchantDetailsWithBankDetailsVerificationStatus(string $status)
    {
        $currentTime = time();

        $lastCronJobTime = $currentTime - DetailConstants::PENNY_TESTING_RETRY_PERIOD_IN_SEC;

        $merchantDetails = $this->repo->useSlave(function() use ($lastCronJobTime, $status) {

            return (new Repository())->fetchMerchantDetailsForPennyTestingRetry($status, $lastCronJobTime);
        });

        return $merchantDetails;
    }

    /**
     * this function takes key of the fields and return true if that fields was updated else false
     *
     * @param array  $dependentFields
     * @param array  $requiredFields
     * @param array  $input
     * @param string $documentVerificationStatusFieldKey
     * @param array  $retriableVerificationStatus
     * @param string $merchantId
     *
     * @return bool
     * @throws Exception\InvalidPermissionException
     */
    protected function isAutoKycAttemptRequired(
        array $dependentFields,
        array $requiredFields,
        array $input,
        string $documentVerificationStatusFieldKey,
        array $retriableVerificationStatus,
        string $merchantId)
    {
        $merchantDetails = $this->repo->merchant_detail->findOrFailPublic($merchantId);

        if ($this->hasAllRequiredFields($merchantDetails, $input, $requiredFields) === false)
        {
            return false;
        }

        $verificationStatus = $merchantDetails->getAttribute($documentVerificationStatusFieldKey);

        if (array_search($verificationStatus, $retriableVerificationStatus, true) !== false)
        {
            return true;
        }

        $submit = $input[Entity::SUBMIT] ?? false;

        if (empty($verificationStatus) === true and ($submit === '1'))
        {
            return true;
        }

        //
        // check if there is any change in any field
        //
        foreach ($dependentFields as $dependentField)
        {
            if ((isset($input[$dependentField]) === true) and ($merchantDetails->getAttribute($dependentField) !== $input[$dependentField]))
            {
                return true;
            }
        }

        return false;
    }

    public function hasAllRequiredFields(DetailEntity $merchantDetails, array $input, array $requiredFields)
    {
        // check that all require fields are present for calling external api
        // changed to empty on $merchantDetails->getAttribute($field) because fields value could be empty string eg. do_not_have_gstin
        foreach ($requiredFields as $field)
        {
            if ((isset($input[$field]) === false) and empty($merchantDetails->getAttribute($field)) === true)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     * @param array           $response
     *
     * @return array
     */

    public function documentCore()
    {
        return new Document\Core();
    }

    private function isAadhaarEsignVerificationDone(Entity $merchantDetails)
    {
        $merchantDetails->load('stakeholder');

        $stakeholder = $merchantDetails->stakeholder;

        if (empty($stakeholder) === true)
        {
            return false;
        }

        // if aadhaar is not linked we say verification is done
        if ((bool) $stakeholder->getAadhaarLinked() === false)
        {
            return true;
        }

        return $stakeholder->getAadhaarEsignStatus() === 'verified';
    }

    private function isAadhaarEsignVerificationRequired(Entity $merchantDetails)
    {
        if ($merchantDetails->merchant->getOrgId() !== Org\Entity::RAZORPAY_ORG_ID)
        {
            return false;
        }

        if (BusinessType::isAadhaarEsignVerificationRequired($merchantDetails->getBusinessType()) === false)
        {
            return false;
        }

        if ($merchantDetails->merchant->isLinkedAccount() === true)
        {
            return false;
        }

        if (array_key_exists($merchantDetails->getBusinessType(), DetailConstants::AADHAAR_ESIGN_BUSINESS_TYPES_EXPERIMENT_MAPPING) === true)
        {
            $experimentName = DetailConstants::AADHAAR_ESIGN_BUSINESS_TYPES_EXPERIMENT_MAPPING[$merchantDetails->getBusinessType()];

            $isAadhaarEsignEnabled = $this->mcore->isRazorxExperimentEnable($merchantDetails->getMerchantId(), $experimentName);

            if ($isAadhaarEsignEnabled === false)
            {
                return false;
            }
        }
        return true;
    }

    public function canSubmitActivationForm(
        Entity $merchantDetails, Merchant\Entity $merchant, $requiredFields)
    {

        if (count($requiredFields) > 0)
        {
            return false;
        }

        $isValidDocumentsStatus = (new FormSubmissionValidStatusesMap())->isDocumentsStatusValidForFormSubmission(
            $merchantDetails,
            FormSubmissionValidStatusesMap::DOCUMENT_LIST_L2);

        if ($merchantDetails->isUnregisteredBusiness() === false and $isValidDocumentsStatus === false)
        {
            return false;
        }

        $activationFlow = null;
        if ($merchantDetails->canDetermineActivationFlow())
        {
            if ($merchantDetails->isUnregisteredBusiness())
            {
                $activationFlow = $this->getActivationFlowForUnregistered($merchant, $merchantDetails);
            }
            else
            {
                $activationFlow = $this->getActivationFlow($merchant, $merchantDetails, null, false);
            }
        }

        if ($activationFlow === ActivationFlow::BLACKLIST)
        {
            return false;
        }

        if ($merchant->isLinkedAccount() === false)
        {
            if ($this->isAadhaarEsignVerificationRequired($merchantDetails) === true)
            {
                return $this->isAadhaarEsignVerificationDone($merchantDetails);
            }
        }

        return true;
    }

    public function setVerificationDetails(Entity $merchantDetails, Merchant\Entity $merchant, array $response, bool $addMissingValidationFields = false)
    {
        $requiredFields = [];

        $merchantDetailsArr = $merchantDetails->toArray();

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $this->getValidationFields($merchantDetails, $addMissingValidationFields);

        $totalFields = count($validationFields) + count($validationSelectiveRequiredFields);

        $documentsResponse = Tracer::inSpan(['name' => 'fetch_document_response'], function() use ($merchant) {
            return $this->documentCore()->documentResponse($merchant);
        });

        $response['documents'] = $documentsResponse;

        $this->setShopEstablishmentVerifiableZone($merchantDetails, $response);

        foreach ($validationFields as $key)
        {
            //
            // Add the key to the list of the required fields if:
            //- if key is document ;- check it only in merchant-documents
            //- else check in merchant_details

            if ($this->isKeyPresent($key, $merchantDetailsArr, $documentsResponse) === false)
            {
                $requiredFields[] = $key;
            }
        }

        if ($merchant->getOrgId() !== ORG_ENTITY::AXIS_ORG_ID)
        {
            $this->calculateRequiredDocumentFields(
                $merchant,
                $validationSelectiveRequiredFields,
                $documentsResponse,
                $requiredFields);
        }

        if ($this->canSubmitActivationForm($merchantDetails, $merchant, $requiredFields) === false)
        {
            $remainingFields = count($requiredFields);

            $response['verification'] = [
                'status'              => 'disabled',
                'disabled_reason'     => 'required_fields',
                'required_fields'     => $requiredFields,
                'optional_fields'     => $validationOptionalFields,
                'activation_progress' => 100 - intval($remainingFields * 100 / $totalFields),
            ];

            $response['can_submit'] = false;
        }
        else
        {
            $response['verification'] = [
                'status'              => 'pending',
                'activation_progress' => 100,
            ];

            $response['can_submit'] = true;
        }

        $isExperimentEnabled = (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(),
                                                                               RazorxTreatment::INSTANT_ACTIVATION_FUNCTIONALITY);

        if ($isExperimentEnabled === true)
        {
            $response['verification']['activation_progress'] = $this->getActivationProgress($merchantDetails);
        }

        return $response;
    }

    private function getActivationProgress(Entity $merchantDetails)
    {
        $activationProgress = 10;

        if (empty($merchantDetails->getBusinessModel()) === false)
        {
            $activationProgress = 40;
        }

        $milestone = $merchantDetails->getActivationFormMilestone();

        switch ($milestone)
        {
            case DetailConstants::L1_SUBMISSION:
                $activationProgress = 60;
                break;

            case DetailConstants::L2_SUBMISSION:
                $activationProgress = 80;
                break;
        }

        if (in_array(
                Status::ACTIVATED_MCC_PENDING,
                $this->getStatusChangeLogs($merchantDetails->merchant)) === true)
        {
            $activationProgress = 90;
        }

        if ($merchantDetails->tnc !== null)
        {
            $activationProgress += 5;
        }

        if ($merchantDetails->getActivationStatus() === Status::ACTIVATED)
        {
            $activationProgress = 100;
        }

        return $activationProgress;
    }

    private function setShopEstablishmentVerifiableZone(Entity $merchantDetails, array &$response)
    {
        $isShopEstablishmentVerifiableZone = false;

        $shopEstablishmentAreaCode = (new ShopEstablishmentAreaCodeMapping())->getAreaCode(
            $merchantDetails->getBusinessRegisteredCity() ?? '',
            $merchantDetails->getBusinessRegisteredState() ?? ''
        );

        if ((empty($shopEstablishmentAreaCode) === false) and
            (BusinessType::isShopEstbVerificationEnableBusinessTypes($merchantDetails->getBusinessTypeValue()) === true))
        {
            $isShopEstablishmentVerifiableZone = true;
        }

        $response['shop_establishment_verifiable_zone'] = $isShopEstablishmentVerifiableZone;
    }

    protected function verifyGSTINIfApplicable(Entity $merchantDetails, Merchant\Entity $merchant, array $input)
    {
        if (((new Merchant\Core())->isAutoKycEnabled($merchantDetails, $merchant) === false) or
            ((array_key_exists(Entity::GSTIN, $input) === true) and
             (empty($input[Entity::GSTIN]) === true)))
        {
            $merchantDetails->setGstinVerificationStatus(null);

            return;
        }

        // For handling business type switch
        if (BusinessType::isGstinVerificationEnableBusinessTypes($merchantDetails->getBusinessTypeValue()) === false)
        {
            $merchantDetails->setGstinVerificationStatus(null);

            return;
        }

        $dependentFields = [
            Entity::GSTIN,
            Entity::BUSINESS_NAME,
            Entity::PROMOTER_PAN_NAME,
            Entity::BUSINESS_OPERATION_ADDRESS,
            Entity::BUSINESS_TYPE
        ];

        $requiredFields = [
            Entity::GSTIN,
            Entity::BUSINESS_NAME,
            Entity::PROMOTER_PAN_NAME,
        ];

        $isAutoKycAttemptRequired = $this->isAutoKycAttemptRequired(
            $dependentFields,
            $requiredFields,
            $input,
            Entity::GSTIN_VERIFICATION_STATUS,
            [GSTINVerificationStatus::FAILED],
            $merchant->getId());

        if ($isAutoKycAttemptRequired === false)
        {
            return;
        }

        $this->updateDocumentVerificationStatus($merchant, $merchantDetails, Constant::GSTIN);

        $eventAttributes = [
            'time_stamp'    => Carbon::now()->getTimestamp(),
            'artefact_type' => 'gstin'
        ];
        $this->app['segment-analytics']->pushTrackEvent($merchant, $eventAttributes, SegmentEvent::RETRY_INPUT_ACTIVATION_FORM);


    }


    /**
     * @param Entity $merchantDetail
     *
     * @return array
     */
    protected function fetchGSTINMetricDimensions(Entity $merchantDetail): array
    {
        return [
            Detail\Constants::GSTIN_STATUS => $merchantDetail->getGstinVerificationStatus()
        ];
    }

    /**
     * @param string $inputKey
     * @param array  $merchantDetailsArr
     * @param array  $documentsResponse
     *
     * @return bool
     */
    protected function isKeyPresent(string $inputKey, array $merchantDetailsArr, array $documentsResponse): bool
    {
        if (Document\Type::isValid($inputKey) === true)
        {
            return (array_key_exists($inputKey, $documentsResponse) === true);
        }

        return ($this->isKeyNotInMerchantDetail($inputKey, $merchantDetailsArr) === false);
    }

    /**
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @throws \Throwable
     */
    public function verifyCINDetailsIfApplicable(Entity $merchantDetails, Merchant\Entity $merchant, array $input = [])
    {
        if (BusinessType::isCinVerificationEnableBusinessTypes($merchantDetails->getBusinessTypeValue()) === false)
        {
            $merchantDetails->setCinVerificationStatus(null);

            return;
        }

        if ((new Merchant\Core())->isAutoKycEnabled($merchantDetails, $merchant) === false)
        {
            $merchantDetails->setCinVerificationStatus(null);

            return;
        }

        $dependentFields = [Detail\Entity::COMPANY_CIN, Detail\Entity::PROMOTER_PAN_NAME, Detail\Entity::BUSINESS_NAME];

        $isAutoKycAttemptRequired = $this->isAutoKycAttemptRequired(
            $dependentFields,
            $dependentFields,
            $input,
            Entity::CIN_VERIFICATION_STATUS,
            [CinVerificationStatus::FAILED],
            $merchant->getId());

        if (($isAutoKycAttemptRequired === false))
        {
            return;
        }
        $this->updateCINorLLPINStatusForBvsVerification($merchant, $merchantDetails);

        $eventAttributes = [
            'time_stamp'    => Carbon::now()->getTimestamp(),
            'artefact_type' => 'comapny_cin',
            'business_type' => $merchantDetails->getBusinessType()
        ];

        $this->app['segment-analytics']->pushTrackEvent($merchant, $eventAttributes, SegmentEvent::RETRY_INPUT_ACTIVATION_FORM);
    }

    /**
     * Update CIN/LLPIN Verification status for BVS
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetails
     *
     * @throws LogicException
     */
    protected function updateCINorLLPINStatusForBvsVerification(Merchant\Entity $merchant, Entity $merchantDetails): void
    {
        $fieldType = ($this->isLLPBusinessType($merchantDetails->getBusinessType()) === true) ?
            Constant::LLPIN : Constant::CIN;

        $this->updateDocumentVerificationStatus($merchant, $merchantDetails, $fieldType);
    }

    /**
     * Returns true if llp business type
     *
     * @param string $businessType `
     *
     * @return bool
     */
    public function isLLPBusinessType(string $businessType): bool
    {
        return $businessType === BusinessType::LLP;
    }

    /**
     *
     * @param Merchant\Entity $merchant
     * @param string          $verificationType
     *
     * @param array           $input
     *
     * @return array
     * @throws LogicException
     * @throws \Throwable
     * @todo will remove this route once frontend stops calling this , for now just returning empty array
     */
    public function verifyMerchantAttributes(Merchant\Entity $merchant, string $verificationType, array $input): array
    {
        return [];
    }

    /**
     * this function is called dynamically to activate merchant in spite of merchant belongs to greylist or blacklist,
     * through batch action.
     * This function name is derived from action name.
     *
     * @param string $merchantId
     * @param array  $input
     */
    public function batchInstantActivation(string $merchantId, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->batchActivationInputUpdate($merchant, $input);

        (new Validator())->validateInput('batch_instant_activation', $input);

        $this->trace->info(TraceCode::MERCHANT_PROCESS_BATCH_ACTIVATION, ['MerchantId' => $merchantId]);

        $this->saveInstantActivationDetailsBatch($input, $merchant);
    }

    /**
     * @param array  $input
     * @param Entity $merchantDetails
     * @param string $key
     */
    public function batchBusinessDetails(array &$input, array $merchantDetails, string $key)
    {
        if (empty($merchantDetails[$key]) === false)
        {
            $input[$key] = $merchantDetails[$key];
        }
    }

    /**
     * Modifying the external input of Merchant Details based on Previous Merchant Details Data
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     */
    public function batchActivationInputUpdate(Merchant\Entity $merchant, array &$input)
    {
        $businessDba = $input[Merchant\Entity::BILLING_LABEL];

        unset($input[Merchant\Entity::BILLING_LABEL]);

        $input[Entity::BUSINESS_DBA] = $businessDba;

        $merchantDetails = $merchant->merchantDetail->toArrayPublic();

        $merchantAttributes = [
            Entity::BUSINESS_DBA,
            Entity::BUSINESS_CATEGORY,
            Entity::BUSINESS_SUBCATEGORY,
            Entity::BUSINESS_TYPE,
            Entity::BUSINESS_NAME,
            Entity::BUSINESS_REGISTERED_ADDRESS,
            Entity::BUSINESS_REGISTERED_STATE,
            Entity::BUSINESS_REGISTERED_CITY,
            Entity::BUSINESS_REGISTERED_PIN,
        ];

        foreach ($merchantAttributes as $key)
        {
            $this->batchBusinessDetails($input, $merchantDetails, $key);
        }

        $addressMerchant = [
            Entity::BUSINESS_OPERATION_ADDRESS => Entity::BUSINESS_REGISTERED_ADDRESS,
            Entity::BUSINESS_OPERATION_STATE   => Entity::BUSINESS_REGISTERED_STATE,
            Entity::BUSINESS_OPERATION_CITY    => Entity::BUSINESS_REGISTERED_CITY,
            Entity::BUSINESS_OPERATION_PIN     => Entity::BUSINESS_REGISTERED_PIN,
        ];

        foreach ($addressMerchant as $key => $value)
        {
            $input[$key] = $input[$value];
        }
    }

    /**
     * @param string $merchantId
     *
     * @return array
     */
    public function getMerchantAndSetBasicAuth(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->app['basicauth']->setMerchant($merchant);

        $merchantDetails = $merchant->merchantDetail;

        return [$merchant, $merchantDetails];
    }

    /**
     * Fetches merchant and merchant details from merchant_id
     *
     * @param string $merchantId
     *
     * @return array
     */
    public function getMerchantAndDetailEntities(string $merchantId): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantDetails = $merchant->merchantDetail;

        return [$merchant, $merchantDetails];
    }

    /**
     * @param Entity $merchantDetail
     * @param string $template
     */
    public function sendOnboardingJourneySms(Entity $merchantDetail, string $template)
    {
        $partnerCore = (new PartnerCore());

        $notificationBlocked = $partnerCore->isSubMerchantNotificationBlocked($merchantDetail->getMerchantId());

        $smsBlocked = $partnerCore->isSmsBlockedSubmerchant($merchantDetail->merchant);

        if (($merchantDetail->merchant->isRazorpayOrgId() === false) or ($smsBlocked === true) or ($notificationBlocked == true))
        {
            return;
        }

        $payload = [
            'receiver' => $merchantDetail->getContactMobile(),
            'template' => $template,
            'source'   => SmsTemplates::ONBOARDING_SOURCE,
            'params'   => [
                'merchantName' => $merchantDetail->merchant->getName(),
                'dashboardUrl' => $this->app['config']->get('applications.dashboard.url')
            ]
        ];

        $orgId = $merchantDetail->getMerchantOrgId();

        // appending orgId in stork context to be used on stork to select org specific sms gateway.
        if (empty($orgId) === false)
        {
            $payload['stork']['context']['org_id'] = $orgId;
        }

        $this->trace->info(TraceCode::MERCHANT_ONBOARDING_SMS_SENT,
                           ['mid'      => $merchantDetail->getMerchantId(),
                            'template' => $payload['template']]);
        try
        {
            $this->app->raven->sendSms($payload);
        }

        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::CRITICAL,
                                         TraceCode::MERCHANT_ONBOARDING_SMS_FAILED,
                                         ['mid'      => $merchantDetail->getMerchantId(),
                                          'template' => $payload['template']]
            );
        }
    }

    /**
     * @param string|null $oldActivationStatus
     * @param Entity      $merchantDetail
     *
     * @throws Exception\ServerErrorException
     */
    private function sendSmsBasedOnMilestones(?string $oldActivationStatus, Entity $merchantDetail)
    {
        $newActivationStatus = $merchantDetail->getActivationStatus();

        if ($newActivationStatus === $oldActivationStatus ||
            $merchantDetail->merchant->isRazorpayOrgId() === false)
        {
            return;
        }

        $promoCodeActive = $this->isPromoCodeActive($merchantDetail->getMerchantId());

        $this->trace->info(
            TraceCode::MERCHANT_ONBOARDING_PROMO_CODE_ACTIVE,
            [
                'promo_active' => $promoCodeActive,
                'merchant_id'  => $merchantDetail->getMerchantId(),
            ]);

        $smsTemplateName = '';

        switch ($newActivationStatus)
        {
            case Status::INSTANTLY_ACTIVATED:
                {
                    if ((Detail\BusinessType::isUnregisteredBusiness($merchantDetail->getBusinessType()) === true))
                    {
                        if ($promoCodeActive === false)
                        {
                            $smsTemplateName = SmsTemplates::UNREGISTERED_PAYMENTS_ENABLED;
                        }
                        else
                        {
                            $smsTemplateName = SmsTemplates::PROMO_UNREGISTERED_PAYMENTS_ENABLED;
                        }
                    }
                    else
                    {
                        if ($promoCodeActive === false)
                        {
                            $smsTemplateName = SmsTemplates::REGISTERED_PAYMENTS_ENABLED;
                        }
                        else
                        {
                            $smsTemplateName = SmsTemplates::PROMO_REGISTERED_PAYMENTS_ENABLED;
                        }
                    }
                }
                break;
            case Status::NEEDS_CLARIFICATION:
                {
                    if (($promoCodeActive === true) &&
                        (Detail\BusinessType::isUnregisteredBusiness($merchantDetail->getBusinessType()) === false))
                    {
                        $smsTemplateName = SmsTemplates::PROMO_NEEDS_CLARIFICATION;
                    }
                    else
                    {
                        $smsTemplateName = SmsTemplates::NEEDS_CLARIFICATION;
                    }
                }
                break;
            case Status::ACTIVATED:
                {
                    if (Detail\BusinessType::isUnregisteredBusiness($merchantDetail->getBusinessType()) === true)
                    {
                        $smsTemplateName = SmsTemplates::UNREGISTERED_SETTLEMENTS_ENABLED;
                    }
                    else
                    {
                        if ($oldActivationStatus !== Status::INSTANTLY_ACTIVATED)
                        {
                            if ($promoCodeActive === false)
                            {
                                $smsTemplateName = SmsTemplates::REGISTERED_SETTLEMENTS_ENABLED;
                            }
                            else
                            {
                                $smsTemplateName = SmsTemplates::PROMO_REGISTERED_SETTLEMENTS_ENABLED;
                            }
                        }
                        else
                        {
                            if ($promoCodeActive === false)
                            {
                                $smsTemplateName = SmsTemplates::UNREGISTERED_SETTLEMENTS_ENABLED;
                            }
                            else
                            {
                                $smsTemplateName = SmsTemplates::PROMO_UNREGISTERED_SETTLEMENTS_ENABLED;
                            }
                        }
                    }
                }
                break;
        }

        if (empty($smsTemplateName) === false)
        {
            $this->sendOnboardingJourneySms($merchantDetail, $smsTemplateName);

            $this->trace->info(
                TraceCode::MERCHANT_ONBOARDING_SMS_TEMPLATE_NAME,
                [
                    'sms_template_name' => $smsTemplateName,
                    'merchant_id'       => $merchantDetail->getMerchantId(),
                ]);
        }
    }

    /**
     * @param $input
     *
     * get sorted subcategory list based on user-entered string
     *
     * @return array
     */
    public function getBusinessDetails(array $input): array
    {
        (new Validator())->validateInput('search_business_details', $input);

        if (isset($input[DEConstants::SEARCH_STRING]) === false)
        {
            $input[DEConstants::SEARCH_STRING] = "";
        }

        $inMemorySearch = new InMemoryBusinessSearch($input[DEConstants::SEARCH_STRING]);

        $response = $inMemorySearch->searchString();

        return $response;
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\BaseException
     */
    public function getCompanySearchList(array $input): array
    {
        $companySearchList = [Constant::RESULTS => []];

        $businessType = $this->merchant->merchantDetail->getBusinessType();

        if (BusinessType::isValidCompanySearchBusinessType($businessType) === false)
        {
            return $companySearchList;
        }

        $bvsCore = new AutoKyc\Bvs\Core();

        $companySearchAttempts = $bvsCore->getCompanySearchAttempts($this->merchant->getId());

        if ($companySearchAttempts > DetailConstants::COMPANY_SEARCH_MAX_ATTEMPT)
        {
            $this->trace->count(DetailMetric::COMPANY_SEARCH_EXHAUSTED);

            $this->trace->info(TraceCode::COMPANY_SEARCH_EXHAUSTED);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_COMPANY_SEARCH_RETRIES_EXHAUSTED);
        }

        (new Validator())->validateInput('company_search', $input);

        try
        {
            $companySearchList =
                $bvsCore->probeCompanySearch($input[DEConstants::SEARCH_STRING]);
        }
        catch (\Exception $e)
        {
            $dimension = AutoKyc\Bvs\Core::getProbeDimension(Constant::COMPANY_SEARCH);

            $this->trace->count(DetailMetric::BVS_PROBE_API_FAILURE, $dimension);

            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::MERCHANT_COMPANY_SEARCH_FAILED,
                                         [
                                             DEConstants::SEARCH_STRING => $input[DEConstants::SEARCH_STRING]
                                         ]);
        }

        $bvsCore->increaseCompanySearchAttempt($this->merchant->getId());

        return $companySearchList;
    }

    /**
     * @return array
     */
    public function getGSTDetailsList(): array
    {
        $gstDetails = [];

        try
        {
            $merchantDetail = $this->merchant->merchantDetail;

            $keys = [
                ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT
            ];

            $data = (new StoreCore())->fetchValuesFromStore($this->merchant->getId(),
                                                            ConfigKey::ONBOARDING_NAMESPACE,
                                                            $keys,
                                                            Merchant\Store\Constants::INTERNAL);

            $this->trace->info(TraceCode::MERCHANT_STORE_GET_DETAILS, ['data' => $data]);

            $bvsCore = new AutoKyc\Bvs\Core();

            //rate limiting per merchant
            $getGstDetailsAttempts = $data[ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT] ?? 0;

            if ($getGstDetailsAttempts > DetailConstants::GET_GST_DETAILS_MAX_ATTEMPT)
            {
                $this->trace->count(DetailMetric::GET_GST_DETAILS_EXHAUSTED);

                $this->trace->info(TraceCode::GET_GST_DETAILS_EXHAUSTED);

                return [Constant::RESULTS => $gstDetails];
            }

            $gstDetailsForCompanyPan  = [];
            $gstDetailsForPersonalPan = [];

            //get company pan associated gstin
            $pan = $merchantDetail->getPan();

            //get business pan associated gstin
            if (empty($pan) == false && $merchantDetail->getCompanyPanVerificationStatus() == DetailConstants::VERIFIED)
            {
                $gstDetailsForCompanyPan =
                    $bvsCore->artefactCuratorProbeGetGstDetails($pan,"Active");
            }

            //get personal pan associated gstin
            $pan = $merchantDetail->getPromoterPan();

            if (empty($pan) == false && $merchantDetail->getPoiVerificationStatus() == DetailConstants::VERIFIED)
            {
                $gstDetailsForPersonalPan =
                    $bvsCore->artefactCuratorProbeGetGstDetails($pan,"Active");
            }

            //merge both with company pan associated gstin given more priority
            $gstDetails = array_unique(array_merge($gstDetailsForCompanyPan, $gstDetailsForPersonalPan));

            $data = [
                Merchant\Store\Constants::NAMESPACE               => ConfigKey::ONBOARDING_NAMESPACE,
                ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT => $getGstDetailsAttempts + 1
            ];

            $data = (new StoreCore())->updateMerchantStore($this->merchant->getId(), $data, Merchant\Store\Constants::INTERNAL);

            $this->trace->info(TraceCode::MERCHANT_STORE_GET_DETAILS, ['data' => $data]);

        }
        catch (\Exception $e)
        {
            $dimension = AutoKyc\Bvs\Core::getProbeDimension(Constant::GET_GST_DETAILS);

            $this->trace->count(DetailMetric::BVS_PROBE_API_FAILURE, $dimension);

            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::GET_GST_DETAILS_FAILED,
                                         [
                                             DEConstants::MERCHANT_ID => $this->merchant->getId()]);
        }

        return [Constant::RESULTS => $gstDetails];
    }


    public function getActivationFlowForUnregistered(Merchant\Entity $merchant, Entity $merchantDetails)
    {
        $subcategory = $merchantDetails->getBusinessSubcategory();

        $category = $merchantDetails->getBusinessCategory();

        $subcategoryMetaData = BusinessSubCategoryMetaData::getSubCategoryMetaData($category, $subcategory);

        $activationFlow = $subcategoryMetaData[BusinessSubCategoryMetaData::NON_REGISTERED_ACTIVATION_FLOW];

        return $activationFlow;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetails
     * @param                 $partner
     * @param bool            $batchFlow
     *
     * @return string
     * @throws Exception\BadRequestException
     */
    public function getActivationFlow(Merchant\Entity $merchant, Entity $merchantDetails, $partner, bool $batchFlow)
    {
        $subcategory = $merchantDetails->getBusinessSubcategory();

        $category = $merchantDetails->getBusinessCategory();

        $subcategoryMetaData = BusinessSubCategoryMetaData::getSubCategoryMetaData($category, $subcategory);

        $activationFlow = $subcategoryMetaData[Entity::ACTIVATION_FLOW];

        //
        // If activation flow is blacklisted we need not to update that
        //
        if (($merchant->isBlockedOrgForInstantActivation() === true) and
            ($activationFlow === ActivationFlow::WHITELIST))
        {
            return ActivationFlow::GREYLIST;
        }

        //
        // If request comes via on-boarding api, it will always be grey-list as on-boarding api
        // does not support instant activation flow
        //
        if ($batchFlow === true)
        {
            //
            // Making ActivationFlow Whitelist for the batchFlow Merchants though there KYC has not been verified
            //
            return ActivationFlow::WHITELIST;
        }

        if (empty($partner) === false)
        {
            return ActivationFlow::GREYLIST;
        }

        if ($this->dedupeCore->isMerchantImpersonated($merchant) === true)
        {
            return ActivationFlow::GREYLIST;
        }

        return $activationFlow;
    }

    /**
     * Check if promotional coupon campaign is enabled
     *
     * @param string $merchantId
     *
     * @return bool
     */
    public function isPromoCodeActive(string $merchantId): bool
    {
        $isCouponActive = (new Coupon\Repository())
            ->isPromoCodeActiveForMerchant($merchantId, Entity::PROMO_COUPON_CODE);

        if ($isCouponActive === true)
        {
            return (new Promotion\Repository())
                ->isMerchantAssociatedWithPromoCode($merchantId, Entity::PROMO_COUPON_CODE);
        }

        return false;
    }

    public function publicTriggerValidationRequests(Merchant\Entity $merchant, Entity $merchantDetails, string $activationFormMilestone = '')
    {
        $this->triggerValidationRequests($merchant, $merchantDetails, $activationFormMilestone);
    }

    /**
     * Triggers validation requests
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetails
     * @param string          $activationFormMilestone
     */
    protected function triggerValidationRequests(Merchant\Entity $merchant, Entity $merchantDetails, string $activationFormMilestone = ''): void
    {
        $factory = new requestDispatcher\Factory();

        $requestCreators = $factory->getBvsRequestDispatchers($merchant, $merchantDetails, $activationFormMilestone);

        foreach ($requestCreators as $requestCreator)
        {
            try
            {
                if ($requestCreator instanceof requestDispatcher\RequestDispatcher)
                {
                    $requestCreator->triggerBVSRequest();
                }
            }
            catch (\Exception $e)
            {
                $this->trace->error(TraceCode::BVS_VERIFICATION_ERROR, ['message'        => $e->getMessage(),
                                                                        'merchantId'     => $merchant->getId(),
                                                                        'requestCreator' => get_class($requestCreator)]);

            }
        }
    }

    /**
     * Triggers validation requests
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetails
     */
    protected function triggerSyncValidationRequests(Merchant\Entity $merchant, Entity $merchantDetails): void
    {
        $factory = new requestDispatcher\Factory();

        $requestCreators = $factory->getSyncBvsRequestDispatchers($merchant, $merchantDetails);

        foreach ($requestCreators as $requestCreator)
        {
            try
            {
                if ($requestCreator instanceof requestDispatcher\RequestDispatcher)
                {
                    $requestCreator->triggerBVSRequest();
                }
            }
            catch (\Exception $e)
            {
                $this->trace->error(TraceCode::BVS_VERIFICATION_ERROR, ['message'        => $e->getMessage(),
                                                                        'merchantId'     => $merchant->getId(),
                                                                        'requestCreator' => get_class($requestCreator)]);

            }
        }
    }

    /**
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @throws LogicException
     */
    public function verifyShopEstbNumberIfApplicable(Entity $merchantDetails, Merchant\Entity $merchant, array $input)
    {
        //
        // This is a temp metric to observe the length of shop establishment number.
        // It Will be removed once Column size is increased from 30.
        //
        if ((empty($input[Entity::SHOP_ESTABLISHMENT_NUMBER]) === false) and
            (strlen($input[Entity::SHOP_ESTABLISHMENT_NUMBER]) > 30))
        {
            $this->trace->info(
                TraceCode::SHOP_ESTABLISHMENT_NUMBER_LENGTH_MORE_THAN_30,
                [
                    Entity::SHOP_ESTABLISHMENT_NUMBER => $input[Entity::SHOP_ESTABLISHMENT_NUMBER]
                ]);

            //
            // Unsetting value so that flow does not break at DB level as column has limit 30 char
            //
            unset($input[Entity::SHOP_ESTABLISHMENT_NUMBER]);

            $this->trace->count(DetailMetric::SHOP_ESTABLISHMENT_NUMBER_LENGTH_MORE_THAN_30);

            return;
        }

        if (((new Merchant\Core())->isAutoKycEnabled($merchantDetails, $merchant) === false) or
            ((array_key_exists(Entity::SHOP_ESTABLISHMENT_NUMBER, $input) === true) and
             (empty($input[Entity::SHOP_ESTABLISHMENT_NUMBER]) === true)))
        {
            $merchantDetails->setShopEstbVerificationStatus(null);

            return;
        }

        // For handling business type switch
        if (BusinessType::isShopEstbVerificationEnableBusinessTypes($merchantDetails->getBusinessTypeValue()) === false)
        {
            $merchantDetails->setShopEstbVerificationStatus(null);

            return;
        }

        if (isset($input[Entity::SHOP_ESTABLISHMENT_NUMBER]) === false)
        {
            return;
        }

        $this->updateDocumentVerificationStatus($merchant, $merchantDetails, Entity::SHOP_ESTABLISHMENT_NUMBER);
    }

    /**
     * This function is to update Document verification status as pending
     * so that verification can be triggered at Form Submission for all such document types.
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetail
     * @param string          $field          // this key can refer to both (proof as well as identifier)
     *
     * @param string          $validationUnit // if not passed will fetch from config
     *
     * @return bool
     * @throws LogicException
     */
    public function updateDocumentVerificationStatus(
        Merchant\Entity $merchant, Merchant\Detail\Entity $merchantDetail, string $field, string $validationUnit = ''): bool
    {
        $enabledVerificationDocuments = array_keys(Constant::ENABLE_VERIFICATION_AFTER_FORM_SUBMISSION);

        if ((in_array($field, $enabledVerificationDocuments, true) === true))
        {
            $documentTypeRazorxMap = Constant::ENABLE_VERIFICATION_AFTER_FORM_SUBMISSION[$field];

            $razorxExperiment = $documentTypeRazorxMap[Constant::RAZORX_EXPERIMENT] ?? '';

            if ((empty($razorxExperiment) === false) and
                (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(), $razorxExperiment) === false)
            {
                return false;
            }

            $this->trace->info(TraceCode::ONBOARDING_FIELD_VERIFICATION_REQUEST_RECEIVED, [
                'field'       => $field,
                "merchant_id" => $merchant->getId()
            ]);

            $artefactDetails = Constant::FIELD_ARTEFACT_DETAILS_MAP[$field];

            $validation = new Merchant\BvsValidation\Entity();

            if (empty($validationUnit) === true)
            {
                $validationUnit = $artefactDetails[Constant::VALIDATION_UNIT];
            }

            $validation->setValidationUnit($validationUnit);

            $validation->setArtefactType($artefactDetails[Constant::ARTEFACT_TYPE]);

            $statusUpdateFactory = new DocumentStatusUpdater\Factory();

            $statusUpdater = $statusUpdateFactory->getInstance($merchant, $merchantDetail, $validation);

            $statusUpdater->updateStatusToPending();
        }

        return true;
    }

    /**
     * @param array $input
     */
    protected function convertStatesToStatesCode(array &$input)
    {
        $this->getStateCodeFromMapping($input, 'business_operation_state');
        $this->getStateCodeFromMapping($input, 'business_registered_state');
    }

    public function setEsignAadhaarSession(string $merchantId, string $sessionId)
    {
        $redis = $this->app['redis']->Connection();

        $key = 'aadhar_esign_session_' . $merchantId;

        $redis->set($key, $sessionId, 'ex', 60 * 10);
    }

    public function getEsignAadhaarSession(string $merchantId)
    {
        $redis = $this->app['redis']->Connection();

        $key = 'aadhar_esign_session_' . $merchantId;

        return $redis->get($key);
    }

    public function processEsignAadhaarVerification(string $merchantId, string $pin, string $fileUrl, string $probeId)
    {
        $stakeholderInput = [
            Stakeholder\Entity::AADHAAR_ESIGN_STATUS => 'verified',
            Stakeholder\Entity::AADHAAR_PIN          => $pin,
            Stakeholder\Entity::BVS_PROBE_ID         => $probeId
        ];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $zip = $this->getFileFromUrl($merchantId, $fileUrl);
        $xml = $this->extractXmlFromZip($merchantId, $pin, $zip);
        $this->encryptFile($xml);

        $this->uploadAadharEsignDocument($merchant, Document\Type::AADHAR_ZIP, $zip);
        $this->uploadAadharEsignDocument($merchant, Document\Type::AADHAR_XML, $xml);

        (new Stakeholder\Core)->saveStakeholder(null, $merchantId, $stakeholderInput);

        if ($merchant->merchantDetail->getPoiVerificationStatus() !== BvsValidationConstants::VERIFIED)
        {
            return;
        }
        if ($merchant->getOrgId() !== Org\Entity::RAZORPAY_ORG_ID)
        {
            return;
        }

        $businessType = $merchant->merchantDetail->getBusinessType();

        // If aadhaar esign is not required then, aadhar with pan is also not required
        if (BusinessType::isAadhaarEsignVerificationRequired($businessType) === false)
        {
            return;
        }

        $this->validateAadhaarWithPan($merchant, $probeId);
    }

    public function processDigilockerAadhaarVerification(string $merchantId, string $aadhaarXml, string $artefactCuratorId)
    {
        $stakeholderInput = [
            Stakeholder\Entity::AADHAAR_ESIGN_STATUS => 'verified',
            Stakeholder\Entity::BVS_PROBE_ID         => $artefactCuratorId
        ];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);


        $xml = $this->getXmlFile($merchantId,$aadhaarXml);
        $this->encryptFile($xml);

        $this->uploadAadharEsignDocument($merchant, Document\Type::AADHAR_XML, $xml);

        (new Stakeholder\Core)->saveStakeholder(null, $merchantId, $stakeholderInput);

        if ($merchant->merchantDetail->getPoiVerificationStatus() !== BvsValidationConstants::VERIFIED)
        {
            return;
        }
        if ($merchant->getOrgId() !== Org\Entity::RAZORPAY_ORG_ID)
        {
            return;
        }

        $businessType = $merchant->merchantDetail->getBusinessType();

        // If aadhaar esign is not required then, aadhar with pan is also not required
        if (BusinessType::isAadhaarEsignVerificationRequired($businessType) === false)
        {
            return;
        }

        $this->validateAadhaarWithPan($merchant, $artefactCuratorId);
    }

    private function uploadAadharEsignDocument($merchant, string $document_type, UploadedFile $file)
    {
        $input = [
            'document_type' => $document_type,
            'file'          => $file
        ];

        $this->documentCore()->uploadActivationFile($merchant, $input, true, 'aadharUpload');
    }

    private function getFileFromUrl(string $merchantId, string $fileUrl)
    {
        $tmpZipFilePath = '/tmp/' . $merchantId . 'zip';
        if (file_put_contents($tmpZipFilePath, file_get_contents($fileUrl)))
        {
            return new UploadedFile($tmpZipFilePath, 'file.zip', null, null, null, true);
        }

        throw new Exception\BadRequestValidationFailureException("unable to fetch aadhar zip file");
    }

    private function getXmlFile(string $merchantId, string $xmldata)
    {
        $tmpZipFilePath = '/tmp/' . $merchantId . 'xml';
        if (file_put_contents($tmpZipFilePath, $xmldata))
        {
            return new UploadedFile($tmpZipFilePath, 'file.xml', null, null, null, true);
        }

        throw new Exception\BadRequestValidationFailureException("unable to creare aadhar xml file");
    }

    private function extractXmlFromZip(string $merchantId, string $pin, $zip)
    {
        $tmpFolder = '/tmp/' . $merchantId;

        $zipArchive = new \ZipArchive();

        if ($zipArchive->open($zip->getPath() . '/' . $zip->getFilename()) === true)
        {
            $zipArchive->setPassword($pin);
            // Unzip Path
            $zipArchive->extractTo($tmpFolder);
            $zipArchive->close();

            $allFiles = scandir($tmpFolder);
            foreach ($allFiles as $xmlFile)
            {
                if (ends_with($xmlFile, 'xml'))
                {
                    return new UploadedFile($tmpFolder . '/' . $xmlFile,
                                            'file.xml', null, null, null, true);
                }
            }
        }

        throw new Exception\BadRequestValidationFailureException("unable to extract xml file");
    }

    private function encryptFile(UploadedFile $file)
    {
        $config = $this->app['config']->get('applications.stakeholders');

        $params = [
            AESEncryption::MODE   => AES::MODE_CBC,
            AESEncryption::IV     => $config['aes_key'],
            AESEncryption::SECRET => $config['aes_key']
        ];

        $handler = new Encryption\Handler(Encryption\Type::AES_ENCRYPTION, $params);

        $handler->encryptFile($file->getPath() . '/' . $file->getFilename());
    }

    /**
     * @param array  $input
     * @param string $field
     */
    protected function getStateCodeFromMapping(array &$input, string $field)
    {
        if (isset($input[$field]) === true)
        {
            $stateCode = IndianStates::getStateCode($input[$field]);

            if ($stateCode !== null)
            {
                $input[$field] = $stateCode;
            }
        }
    }

    public function setMerchantRiskClient($mrclient)
    {
        $this->mrclient = $mrclient;
    }

    public function setMerchantCoreForRazorx($mcore)
    {
        $this->mcore = $mcore;
    }

    public function isMerchantTncApplicable(Merchant\Entity $merchant)
    {
        $org = $merchant->getOrgId() ?: $this->app['basicauth']->getOrgId();

        if (array_key_exists($org, DEConstants::TNC_ORG_ID_EXP_MAP) === false)
        {
            return false;
        }

        if (empty($merchant->merchantDetail->getAttribute(
                Entity::BUSINESS_WEBSITE)) === false)
        {
            return false;
        }

        if ($merchant->isBusinessBankingEnabled() === true)
        {
            return false;
        }

        $experimentName = DEConstants::TNC_ORG_ID_EXP_MAP[$org];

        if ((new Merchant\Core())->isRazorxExperimentEnable(
                $merchant->getId(),
                $experimentName) === false)
        {
            return false;
        }

        return true;
    }

    /**
     * Fetch common fields to be locked in the partner/merchant activation form (based on the entity passed)
     *
     * @param Entity
     *
     * @return array
     */
    public function fetchCommonFieldsToBeLocked(Base\PublicEntity $entity): array
    {
        $merchantDetail = ($entity->getEntityName() === E::PARTNER_ACTIVATION) ? $entity->merchantDetail : $entity;

        $businessType = $merchantDetail->getBusinessType();

        $commonFields = [];

        if ($entity->isLocked() === true)
        {
            $commonFields = DEConstants::COMMON_FIELDS_WITH_PARTNER_ACTIVATION[Constants::DEFAULT];

            if (isset(DEConstants::COMMON_FIELDS_WITH_PARTNER_ACTIVATION[$businessType]) === true)
            {
                $commonFields = DEConstants::COMMON_FIELDS_WITH_PARTNER_ACTIVATION[$businessType];
            }
        }
        else
        {
            if ($entity->getEntityName() === E::MERCHANT_DETAIL)
            {
                // if L1 form is submitted then do not allow verified PAN fields to be editable in the partner KYC form
                if ($merchantDetail->getActivationFormMilestone() === DetailConstants::L1_SUBMISSION)
                {
                    if ($merchantDetail->getPoiVerificationStatus() === DetailConstants::VERIFIED)
                    {
                        array_push($commonFields, DetailEntity::PROMOTER_PAN, DetailEntity::PROMOTER_PAN_NAME);
                    }

                    if ($merchantDetail->getCompanyPanVerificationStatus() === DetailConstants::VERIFIED)
                    {
                        array_push($commonFields, DetailEntity::COMPANY_PAN, DetailEntity::BUSINESS_NAME);
                    }
                }
            }
        }

        return $commonFields;
    }

    private function addCommentForBusinessWebsiteSave(string $urlType, string $permissionName, Entity $merchantDetails, string $dedupeFlaggedMIDs, array $input)
    {
        $businessDetailsComment = '';

        $testCredentialComment = '';

        if ($urlType === DetailConstants::URL_TYPE_WEBSITE)
        {
            $businessDetailsComment = sprintf(DetailConstants::MERCHANT_BUSINESS_WEBSITE_COMMENT,
                                              $input[DetailConstants::BUSINESS_WEBSITE_MAIN_PAGE],
                                              $input[DetailConstants::BUSINESS_WEBSITE_ABOUT_US],
                                              $input[DetailConstants::BUSINESS_WEBSITE_CONTACT_US],
                                              $input[DetailConstants::BUSINESS_WEBSITE_PRICING_DETAILS],
                                              $input[DetailConstants::BUSINESS_WEBSITE_PRIVACY_POLICY],
                                              $input[DetailConstants::BUSINESS_WEBSITE_TNC],
                                              $input[DetailConstants::BUSINESS_WEBSITE_REFUND_POLICY],
                                              $dedupeFlaggedMIDs
            );

            if ((empty($input[DetailConstants::BUSINESS_WEBSITE_USERNAME]) === false) and
                (empty($input[DetailConstants::BUSINESS_WEBSITE_PASSWORD]) === false))
            {
                $testCredentialComment = sprintf(DetailConstants::MERCHANT_WEBSITE_TEST_CREDENTIAL_COMMENT,
                                                 $input[DetailConstants::BUSINESS_WEBSITE_USERNAME],
                                                 $input[DetailConstants::BUSINESS_WEBSITE_PASSWORD]
                );
            }
        }
        else
        {
            $businessDetailsComment = sprintf(DetailConstants::MERCHANT_APP_URL_COMMENT,
                                              $input[DetailConstants::BUSINESS_APP_URL],
                                              $dedupeFlaggedMIDs
            );

            if (
                (empty($input[DetailConstants::BUSINESS_APP_USERNAME]) === false) and
                (empty($input[DetailConstants::BUSINESS_APP_PASSWORD]) === false))
            {
                $testCredentialComment = sprintf(DetailConstants::MERCHANT_APP_TEST_CREDENTIAL_COMMENT,
                                                 $input[DetailConstants::BUSINESS_APP_USERNAME],
                                                 $input[DetailConstants::BUSINESS_APP_PASSWORD]
                );
            }
        }

        $this->app['trace']->info(TraceCode::MERCHANT_SAVE_BUSINESS_WEBSITE_COMMENT, [
            'business_details_comment' => $businessDetailsComment,
        ]);

        $workFlowAction = (new ActionCore())->fetchOpenActionOnEntityOperation($merchantDetails->getId(),
                                                                               $merchantDetails->getEntity(),
                                                                               $permissionName
        )->first();

        $businessDetailsCommentEntity = (new CommentCore())->create([
                                                                        CommentEntity::COMMENT => $businessDetailsComment,
                                                                    ]);

        $businessDetailsCommentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($businessDetailsCommentEntity);

        if (empty($testCredentialComment) === false)
        {
            $encryptedComment = DetailConstants::ENCRYPTED_WEBSITE_DETAILS_IDENTIFIER . encrypt($testCredentialComment);

            $testCredentialCommentEntity = (new CommentCore())->create([
                                                                           CommentEntity::COMMENT => $encryptedComment,
                                                                       ]);

            $testCredentialCommentEntity->entity()->associate($workFlowAction);

            $this->repo->saveOrFail($testCredentialCommentEntity);
        }
    }

    /**
     * This function is used for edit merchant contact details
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return array
     */
    public function updateMerchantContact(Merchant\Entity $merchant, array $input)
    {
        $this->trace->info(
            TraceCode::CONTACT_UPDATE_REQUEST, [
            Entity::MERCHANT_ID    => $merchant->getMerchantId(),
            Entity::CONTACT_MOBILE => mask_phone($input[DetailConstants::NEW_CONTACT_NUMBER])
        ]);

        $input[Entity::MERCHANT_ID] = $merchant->getMerchantId();

        $oldMerchantDetails = [
            Entity::CONTACT_MOBILE         => $merchant->merchantDetail->getContactMobile(),
            Constants::USER_CONTACT_MOBILE => $input[DetailConstants::OLD_CONTACT_NUMBER],
        ];

        $newMerchantDetails = [
            Entity::CONTACT_MOBILE         => $input[DetailConstants::NEW_CONTACT_NUMBER],
            Constants::USER_CONTACT_MOBILE => $input[DetailConstants::NEW_CONTACT_NUMBER],
        ];

        $this->app['workflow']
            ->setPermission(Permission\Name::UPDATE_MOBILE_NUMBER)
            ->setEntityAndId($merchant->getEntity(), $merchant->getId())
            ->setController(DetailConstants::UPDATE_CONTACT_CONTROLLER)
            ->setInput($input)
            ->handle($oldMerchantDetails, $newMerchantDetails);
    }

    public function changeMerchantUserMobile(Merchant\Entity $merchant, $input)
    {
        $validOldMobileNumberFormats = (new PhoneBook($input[DetailConstants::OLD_CONTACT_NUMBER]))->getMobileNumberFormats();

        $user = $merchant->users()
                         ->where(Merchant\Detail\Entity::ROLE, '=', DetailConstants::OWNER)
                         ->whereIn(Entity::CONTACT_MOBILE, $validOldMobileNumberFormats)
                         ->first();

        $this->trace->info(TraceCode::MERCHANT_USER_NUMBER_CHANGE, [
            Entity::MERCHANT_ID                 => $merchant->getMerchantId(),
            Entity::CONTACT_MOBILE              => mask_phone($input[DetailConstants::NEW_CONTACT_NUMBER]),
            DetailConstants::OLD_CONTACT_NUMBER => mask_phone($merchant->merchantDetail->getContactMobile()),
            Constants::MERCHANT_USER            => $user->getId(),
            Constants::USER_CONTACT_MOBILE      => mask_phone($user->getContactMobile())
        ]);

        $user->setContactMobile($input[DetailConstants::NEW_CONTACT_NUMBER]);

        $this->repo->saveOrFail($user);
    }

    /**
     * update contact mobile of a merchant after workflow approval
     *
     * @param array  $input
     *
     * @param Entity $user
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function merchantContactUpdatePostWorkflow(Merchant\Entity $merchant, array $input)
    {
        $oldMerchantContact = $input[DetailConstants::OLD_CONTACT_NUMBER];

        $newMerchantContact = $input[DetailConstants::NEW_CONTACT_NUMBER];

        $merchant->merchantDetail->setAttribute(Entity::CONTACT_MOBILE, $newMerchantContact);

        $this->repo->merchant_detail->saveOrFail($merchant->merchantDetail);

        $args = [
            Constants::MERCHANT               => $merchant,
            DashboardNotificationEvent::EVENT => DashboardNotificationEvent::UPDATE_MERCHANT_CONTACT_FROM_ADMIN,
            Constants::PARAMS                 => [
                DetailConstants::OLD_CONTACT_NUMBER => $oldMerchantContact,
                DetailConstants::NEW_CONTACT_NUMBER => $newMerchantContact,
            ]
        ];

        (new DashboardNotificationHandler($args))->send();
    }

    /**
     * This function is used for add/edit merchant website details
     *
     * @param Entity $merchantDetails
     * @param array  $input
     *
     * @return array
     * @throws \Throwable
     */
    public function postSaveBusinessWebsite(string $urlType, array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_SAVE_BUSINESS_WEBSITE,
            ['input' => $input]);

        $isRequestToUpdateWebsite = !empty($this->merchant->merchantDetail->getWebsite());

        $input = array_merge($input, [DetailConstants::URL_TYPE => $urlType]);

        if ($urlType === DetailConstants::URL_TYPE_WEBSITE)
        {
            $this->merchant->merchantDetail->getValidator()->validateInput('business_websites_check', $input);
        }
        else
        {
            $this->merchant->merchantDetail->getValidator()->validateInput('business_app_url_check', $input);
        }

        $permissionName = Permission\Name::EDIT_MERCHANT_WEBSITE_DETAIL;

        $newUrl = ($urlType === DetailConstants::URL_TYPE_WEBSITE) ? $input[DetailConstants::BUSINESS_WEBSITE_MAIN_PAGE] : $input[DetailConstants::BUSINESS_APP_URL];

        if ($isRequestToUpdateWebsite === true)
        {
            if ($this->merchant->getHasKeyAccess() === false)
            {
                (new KeyValidator)->checkHasKeyAccess($this->merchant, $this->mode);
            }

            $permissionName = Permission\Name::UPDATE_MERCHANT_WEBSITE;
        }

        $originalMerchantDetails = [DetailConstants::BUSINESS_WEBSITE_MAIN_PAGE => $this->merchant->merchantDetail->getWebsite()];

        $this->merchant->merchantDetail->setWebsite($newUrl);

        $dirtyMerchantDetails = [DetailConstants::BUSINESS_WEBSITE_MAIN_PAGE => $this->merchant->merchantDetail->getWebsite()];

        [$status, $matchedMerchantIds] = $this->dedupeCore->matchAndGetMatchedMIDs($this->merchant);

        $this->merchant->merchantDetail->setWebsite($originalMerchantDetails[DetailConstants::BUSINESS_WEBSITE_MAIN_PAGE]);

        $dedupeFlaggedMIDs = implode(',', $matchedMerchantIds);

        $this->app['workflow']
            ->setPermission($permissionName)
            ->setEntityAndId($this->merchant->merchantDetail->getEntity(), $this->merchant->merchantDetail->getMerchantId())
            ->setController(DetailConstants::UPDATE_BUSINESS_WEBSITE_CONTROLLER)
            ->setInput($input)
            ->handle($originalMerchantDetails, $dirtyMerchantDetails, true);

        $this->addCommentForBusinessWebsiteSave($urlType, $permissionName, $this->merchant->merchantDetail, $dedupeFlaggedMIDs, $input);
    }

    public function updateBusinessWebsite(Merchant\Entity $merchant, string $newUrl)
    {
        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $newUrl) {
            $this->merchant->merchantDetail->setWebsite($newUrl);

            $this->repo->merchant_detail->saveOrFail($this->merchant->merchantDetail);

            // Sync key business_website  to merchant entity
            $merchant = (new Merchant\Core)->syncMerchantEntityFields($merchant, [Entity::BUSINESS_WEBSITE => $newUrl]);

            $this->checkAndMarkHasKeyAccess($this->merchant->merchantDetail, $merchant);

            $this->repo->saveOrFail($merchant);
        });
    }

    public function getDecryptedWebsiteCommentForWebsiteSelfServe($comments)
    {
        foreach ($comments as $comment)
        {
            if (empty($comment->comment) == false and strpos($comment->comment, DetailConstants::ENCRYPTED_WEBSITE_DETAILS_IDENTIFIER) === 0)
            {
                $decryptedWebsiteInfo = decrypt(substr($comment->comment, strlen(DetailConstants::ENCRYPTED_WEBSITE_DETAILS_IDENTIFIER)));

                return $decryptedWebsiteInfo;
            }
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_ENCRYPTED_COMMENT_NOT_FOUND);
    }

    /**
     * Submit partner activation form while submitting merchant activation form if applicable
     * Case 1: Partner activation status is -> [under review, activated, rejected] or merchant is not partner
     *       - Do not submit partner activation form
     * Case 2: Partner activation is under needs clarification
     *       - Get KYC clarification reasons for common fields and update partner KYC clarification reasons and then
     *         submit the partner activation form
     * Case 3: Partner activation form is not submitted (null)
     *       - Only submit the partner activation form
     *
     * @param Merchant\Entity $merchant
     * @param array|null      $input
     *
     * @throws \Throwable
     */
    private function submitPartnerActivationFormIfApplicable(Merchant\Entity $merchant, ?array $input)
    {
        $partnerCore = (new PartnerCore());

        $partnerActivation = $partnerCore->getPartnerActivation($merchant);

        $partnerActivationStatus = empty($partnerActivation) ? null : $partnerActivation->getActivationStatus();

        $excludedActivationStatus = [Status::ACTIVATED, Status::UNDER_REVIEW, Status::REJECTED];

        if (empty($partnerActivation) or (in_array($partnerActivationStatus, $excludedActivationStatus, true) === true))
        {
            return;
        }

        if ($partnerActivationStatus === Status::NEEDS_CLARIFICATION)
        {
            $merchantDetails = $merchant->merchantDetail;

            $merchantDetails->getValidator()->validatePartnerActivationStatus($merchant);

            $input[DetailEntity::KYC_CLARIFICATION_REASONS] = $partnerCore->fetchCommonFieldsFromMerchantKycClarificationReasons(
                $input, $merchant);
        }
        else
        {
            unset($input[DetailEntity::KYC_CLARIFICATION_REASONS]);
        }

        $kycClarificationReasons = $partnerCore->getUpdatedPartnerKycClarificationReasons($input, $merchant->getId());

        if (empty($kycClarificationReasons) === false)
        {
            $partnerActivation->setKycClarificationReasons($kycClarificationReasons);
        }

        $partnerCore->submitPartnerActivationForm($merchant, $merchant->merchantDetail, $partnerActivation, $input, Constants::MERCHANT);
    }

    public function postAddAdditionalWebsiteSelfServe(Entity $merchantDetails, string $urlType, array $input)
    {
        $merchant = $merchantDetails->merchant;

        $merchantDetails->getValidator()->validateAddAdditionalWebsiteConditions($merchantDetails);

        $newUrl = ($urlType === DetailConstants::URL_TYPE_WEBSITE) ? $input[DetailConstants::ADDITIONAL_WEBSITE_MAIN_PAGE] : $input[DetailConstants::ADDITIONAL_APP_URL];

        $input = array_merge($input, [DetailConstants::URL_TYPE => $urlType]);

        if ($urlType === DetailConstants::URL_TYPE_WEBSITE)
        {
            $merchantDetails->getValidator()->validateInput('additional_website_check', $input);

            if ((isset($input[DetailConstants::ADDITIONAL_WEBSITE_PROOF_URL]) === true) and
                (is_object($input[DetailConstants::ADDITIONAL_WEBSITE_PROOF_URL]) === true))
            {
                $input[DetailConstants::ADDITIONAL_WEBSITE_PROOF_URL] = $this->uploadAdditionalWebsiteProof($merchantDetails, $input[DetailConstants::ADDITIONAL_WEBSITE_PROOF_URL]);
            }
        }
        else
        {
            $merchantDetails->getValidator()->validateInput('additional_app_check', $input);
        }

        $input[DetailConstants::MERCHANT_ID] = $merchant->getMerchantId();

        $oldMerchantDetails = $merchantDetails;

        $newMerchantDetails = clone $oldMerchantDetails;

        $newMerchantDetails->setAdditionalWebsites([$merchantDetails->getAdditionalWebsites(), $newUrl]);

        $this->app['workflow']
            ->setEntityAndId($merchantDetails->getEntity(), $merchantDetails->getMerchantId())
            ->setPermission(Permission\Name::ADD_ADDITIONAL_WEBSITE)
            ->setInput($input)
            ->setController(DetailConstants::ADD_ADDITIONAL_WEBSITE_CONTROLLER)
            ->handle($oldMerchantDetails, $newMerchantDetails, true);

        [$status, $matchedMerchantIds] = $this->dedupeCheckForAdditionalWebsite($merchant, $newUrl);

        $input[DetailConstants::DEDUPE_STATUS] = $status;

        $input[DetailConstants::DEDUPE_FLAGGED_MIDS] = implode(',', $matchedMerchantIds);

        $this->addCommentForAddAdditionalWebsitePostWorkflowCreation($merchantDetails, $input, $urlType);

        return [Entity::ADDITIONAL_WEBSITES => $merchantDetails->getAdditionalWebsites()];
    }

    protected function uploadAdditionalWebsiteProof(Entity $merchantDetails, $additionalWebsiteProof)
    {
        $fileInputs = [
            DetailConstants::ADDITIONAL_WEBSITE_PROOF_URL => $additionalWebsiteProof
        ];

        $fileAttributes = (new Detail\Service())->storeActivationFile($merchantDetails, $fileInputs);

        if ((is_array($fileAttributes) === false) or
            (isset($fileAttributes[DetailConstants::ADDITIONAL_WEBSITE_PROOF_URL]) === false))
        {
            throw new Exception\ServerErrorException(
                'Additional Website Domain Registration/Ownership Proof URL upload failed',
                ErrorCode::SERVER_ERROR);
        }

        return $fileAttributes[DetailConstants::ADDITIONAL_WEBSITE_PROOF_URL][Document\Constants::FILE_ID];
    }

    public function dedupeCheckForAdditionalWebsite(Merchant\Entity $merchant, string $newUrl)
    {
        $merchant->merchantDetail->setWebsite($newUrl);

        [$status, $matchedMerchantIds] = $this->dedupeCore->matchAndGetMatchedMIDs($merchant);

        return [$status, $matchedMerchantIds];
    }

    protected function addCommentForAddAdditionalWebsitePostWorkflowCreation(Entity $merchantDetails, array $input, string $urlType)
    {
        $workFlowAction = (new WorkFlowActionCore())->fetchOpenActionOnEntityOperation($merchantDetails->getMerchantId(),
                                                                                       $merchantDetails->getEntity(),
                                                                                       Permission\Name::ADD_ADDITIONAL_WEBSITE,
                                                                                       $merchantDetails->merchant->getOrgId()
        )->first();

        if (is_null($workFlowAction) === true)
        {
            throw new Exception\ServerErrorException('Workflow Action Not Found',
                                                     ErrorCode::SERVER_ERROR_WORKFLOW_ACTION_CREATE_FAILED);
        }

        $this->createCommentForAddAdditionalWebsitePages($workFlowAction, $input, $urlType);

        $this->createCommentForAddAdditionalWebsiteDedupe($workFlowAction, $input);

        if (((empty($input[DetailConstants::ADDITIONAL_WEBSITE_TEST_USERNAME]) === false) and
             (empty($input[DetailConstants::ADDITIONAL_WEBSITE_TEST_PASSWORD]) === false)) or
            ((empty($input[DetailConstants::ADDITIONAL_APP_TEST_USERNAME]) === false) and
             (empty($input[DetailConstants::ADDITIONAL_APP_TEST_PASSWORD]) === false)))
        {
            $this->createCommentForAddAdditionalWebsiteTestCredentials($workFlowAction, $input, $urlType);
        }

        $this->createCommentForAddAdditionalWebsiteReason($workFlowAction, $input, $urlType);

        if (($urlType === DetailConstants::URL_TYPE_WEBSITE) and
            (empty($input[DetailConstants::ADDITIONAL_WEBSITE_PROOF_URL]) === false))
        {
            $this->createCommentForAddAdditionalWebsiteUrlProof($workFlowAction, $input);
        }
    }

    protected function createCommentForAddAdditionalWebsitePages(WorkFlowActionEntity $workFlowAction, array $input, string $urlType)
    {
        if ($urlType === DetailConstants::URL_TYPE_WEBSITE)
        {
            $comment = sprintf(DetailConstants::ADD_ADDITIONAL_WEBSITE_WORKFLOW_PAGES_COMMENT_STRUCTURE,
                               $input[DetailConstants::ADDITIONAL_WEBSITE_MAIN_PAGE],
                               $input[DetailConstants::ADDITIONAL_WEBSITE_ABOUT_US],
                               $input[DetailConstants::ADDITIONAL_WEBSITE_CONTACT_US],
                               $input[DetailConstants::ADDITIONAL_WEBSITE_PRICING_DETAILS],
                               $input[DetailConstants::ADDITIONAL_WEBSITE_PRIVACY_POLICY],
                               $input[DetailConstants::ADDITIONAL_WEBSITE_TNC],
                               $input[DetailConstants::ADDITIONAL_WEBSITE_REFUND_POLICY]
            );
        }
        else
        {
            $comment = sprintf(DetailConstants::ADD_ADDITIONAL_APP_WORKFLOW_PAGE_COMMENT_STRUCTURE,
                               $input[DetailConstants::ADDITIONAL_APP_URL]
            );
        }

        $commentEntity = (new CommentCore())->create([
                                                         DetailConstants::COMMENT => $comment,
                                                     ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);
    }

    protected function createCommentForAddAdditionalWebsiteDedupe(WorkFlowActionEntity $workFlowAction, array $input)
    {
        $comment = DetailConstants::DEDUPE_STATUS_FALSE;

        if ($input[DetailConstants::DEDUPE_STATUS] === true)
        {
            $comment = sprintf(DetailConstants::ADD_ADDITIONAL_WEBSITE_WORKFLOW_DEDUPE_COMMENT_STRUCTURE,
                               $input[DetailConstants::DEDUPE_FLAGGED_MIDS]
            );
        }

        $commentEntity = (new CommentCore())->create([
                                                         DetailConstants::COMMENT => $comment,
                                                     ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);
    }

    protected function createCommentForAddAdditionalWebsiteTestCredentials(WorkFlowActionEntity $workFlowAction, array $input, string $urlType)
    {
        if ($urlType === DetailConstants::URL_TYPE_WEBSITE)
        {
            $comment = sprintf(DetailConstants::ADD_ADDITIONAL_WEBSITE_WORKFLOW_TEST_CREDENTIALS_COMMENT_STRUCTURE,
                               $input[DetailConstants::ADDITIONAL_WEBSITE_TEST_USERNAME],
                               $input[DetailConstants::ADDITIONAL_WEBSITE_TEST_PASSWORD]
            );
        }
        else
        {
            $comment = sprintf(DetailConstants::ADD_ADDITIONAL_WEBSITE_WORKFLOW_TEST_CREDENTIALS_COMMENT_STRUCTURE,
                               $input[DetailConstants::ADDITIONAL_APP_TEST_USERNAME],
                               $input[DetailConstants::ADDITIONAL_APP_TEST_PASSWORD]
            );
        }

        $encryptedComment = DetailConstants::ENCRYPTED_WEBSITE_DETAILS_IDENTIFIER . encrypt($comment);

        $commentEntity = (new CommentCore())->create([
                                                         DetailConstants::COMMENT => $encryptedComment,
                                                     ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);
    }

    protected function createCommentForAddAdditionalWebsiteReason(WorkFlowActionEntity $workFlowAction, array $input, string $urlType)
    {
        if ($urlType === DetailConstants::URL_TYPE_WEBSITE)
        {
            $comment = sprintf(DetailConstants::ADD_ADDITIONAL_WEBSITE_WORKFLOW_REASON_COMMENT_STRUCTURE,
                               $input[DetailConstants::ADDITIONAL_WEBSITE_REASON]
            );
        }
        else
        {
            $comment = sprintf(DetailConstants::ADD_ADDITIONAL_WEBSITE_WORKFLOW_REASON_COMMENT_STRUCTURE,
                               $input[DetailConstants::ADDITIONAL_APP_REASON]
            );
        }

        $commentEntity = (new CommentCore())->create([
                                                         DetailConstants::COMMENT => $comment,
                                                     ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);
    }

    protected function createCommentForAddAdditionalWebsiteUrlProof(WorkFlowActionEntity $workFlowAction, array $input)
    {
        $comment = sprintf(DetailConstants::ADD_ADDITIONAL_WEBSITE_WORKFLOW_URL_COMMENT_STRUCTURE,
                           $this->app->config->get('applications.dashboard.url'),
                           $input[DetailConstants::ADDITIONAL_WEBSITE_PROOF_URL]
        );

        $commentEntity = (new CommentCore())->create([
                                                         DetailConstants::COMMENT => $comment,
                                                     ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);
    }


    public function getBankDetailsVerificationError(Entity $merchantDetails)
    {
        $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerIdAndOwnerType(
            $merchantDetails->getId(),
            Constant::MERCHANT,
            Constant::BANK_ACCOUNT
        );
        if ($validation === null)
        {
            return null;
        }
        $error_description = null;

        if ($validation->getErrorCode() === BvsValidationConstants::INPUT_DATA_ISSUE)
        {
            $error_description = $validation->getErrorDescription();
        }
        else
        {
            $error_description = $validation->getErrorCode();
        }

        return $error_description;
    }

    public function getBusinessTypes($merchantId): array
    {
        $result = [];

        foreach (BusinessType::$businessTypeBuckets as $bucketName => $businessTypes)
        {
            $result[$bucketName] = [];

            foreach ($businessTypes as $businessType)
            {
                if (empty($merchantId) === false and
                    array_key_exists($businessType, BusinessType::$businessTypeExperiments))
                {
                    $experimentName            = BusinessType::$businessTypeExperiments[$businessType];
                    $isRazorxExperimentEnabled = (new Merchant\Core)->isRazorxExperimentEnable(
                        $merchantId,
                        $experimentName);

                    $this->trace->info(
                        TraceCode::RAZORX_EXPERIMENT_RESULT,
                        [$experimentName => $isRazorxExperimentEnabled]);

                    $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                    if ($isRazorxExperimentEnabled === true && (new Merchant\Core())->isBlockedMerchantType($merchant,[Merchant\Core::SUB_MERCHANT,
                                                                                                                       Merchant\Core::PARTNER_MERCHANT,
                                                                                                                       Merchant\Core::LINKED_ACCOUNT])===false)
                    {
                        array_push($result[$bucketName],
                                   [
                                       "id"      =>  strval(BusinessType::getIndexFromKey($businessType)),
                                       "label" => BusinessType::getDisplayNameFromKey($businessType),
                                       "status"  => 'active'
                                   ]
                        );
                    }
                    else
                    {
                        array_push($result[$bucketName],
                                   [
                                       "id"      =>  strval(BusinessType::getIndexFromKey($businessType)),
                                       "label" => BusinessType::getDisplayNameFromKey($businessType),
                                       "status"  => 'inactive'
                                   ]
                        );
                    }
                }
                else
                {
                    array_push($result[$bucketName],
                               [
                                   "id"      => strval(BusinessType::getIndexFromKey($businessType)),
                                   "label" => BusinessType::getDisplayNameFromKey($businessType),
                                   "status"  => 'active'
                               ]
                    );
                }
            }
        }

        return $result;
    }

    /*
     * This function builds the response error code in the event there exists an error in the validation
     * It queries the latest validation for the supported artefact types and checks if it has error,
     * in case it has error, it ensures the document associated with the validation is not deleted and
     * post which it maps the error code to the relevant custom error code.
     */
    public function fetchVerificationErrorCodes($merchant): array
    {
        $errorCodes = [];

        $merchantId = $merchant->getMerchantId();
        // for each artefact type make a separate query to database, given we need to fetch only the
        // latest record and see if it has error
        foreach (DetailConstants::SUPPORTED_VERIFICATION_RESPONSE_TYPES as $artefactType)
        {
            $validation = $this->repo->bvs_validation->getLatestArtefactValidationForOwnerIdAndOwnerType(
                $merchantId,
                Constant::MERCHANT,
                $artefactType
            );
            $this->trace->info(TraceCode::MERCHANT_DETAIL_VERIFICATION_ERROR_RESPONSE, [
                '$validation' => $validation,
            ]);
            if (empty($validation) === true or empty($validation->getErrorCode()) === true)
            {
                // since there is no error associated with the latest validation skip the processing
                continue;
            }

            $validationUnit = $validation->getValidationUnit();

            $merchantDetails = $this->repo->merchant_detail->findByPublicId($merchantId);

            $document = null;

            if ($validationUnit === BvsValidationConstants::PROOF)
            {
                // verify that the latest validation does not belong to a deleted document
                $document = $this->repo->merchant_document->findNonDeletedDocumentForMerchantIdAndValidationId($merchantId,
                                                                                                               $validation->getValidationId());
                if (empty($document) === true)
                {
                    // since document associated with the validation is deleted, skip the processing
                    continue;
                }
                $this->trace->info(TraceCode::MERCHANT_DETAIL_VERIFICATION_ERROR_RESPONSE, [
                    '$document' => $document,
                ]);
            }

            try {
                $requestDispatcher = $this->getRequestDispatcher($artefactType, $merchant, $merchantDetails, $validationUnit, $document);
            }
            catch (\Exception $e){
                continue;
            }

            $validationErrorCodeKey = Constant::ARTEFACT_STATUS_ATTRIBUTE_MAPPING[$artefactType . '-' . $validationUnit][1];

            $verificationResponseKey = $requestDispatcher->getVerificationResponseKey($validation);

            if (isset(DetailConstants::VERIFICATION_RESPONSE_ERROR_CODES[$verificationResponseKey]))
            {
                $validationErrorCodeValue = DetailConstants::VERIFICATION_RESPONSE_ERROR_CODES[$verificationResponseKey];

                $errorCodes[$validationErrorCodeKey] = $validationErrorCodeValue;
            }

        }

        return $errorCodes;
    }

    public function getRequestDispatcher($artefactType, $merchant, $merchantDetails, $validationUnit, $document = null )
    {

        if(empty($document) === false )
        {
            switch ($document->getDocumentType()){
                case DocumentType::AADHAR_BACK:
                    return new requestDispatcher\AadharBackOcr($merchant,$merchantDetails,$document);

                case DocumentType::AADHAR_FRONT:
                    return new requestDispatcher\AadhaarFrontAndBackValidationOcr($merchant,$merchantDetails,$document);
            }
        }
        else{
            switch ($artefactType . $validationUnit) {
                case Constant::BANK_ACCOUNT. BvsValidationConstants::IDENTIFIER:
                    return new BankAccountRequestDispatcher($merchant, $merchantDetails);

            }
        }

        throw new \InvalidArgumentException('Artefact not supported');
    }

    public function getMerchantInfo(string $merchantId): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantDetails = $merchant->merchantDetail;

        $response['merchant_details'] = $this->createResponse($merchantDetails);

        $users = (new Merchant\Core())->getUsers($merchant);

        $finalUsers = [];

        foreach ($users as $user)
        {
            try
            {
                $contact = $user['contact_mobile'];

                $user['whatsapp_optin_status'] = false;

                if (empty($contact) === false)
                {
                    $user['whatsapp_optin_status'] = app('stork_service')->optInStatusForWhatsapp($this->mode, $contact, "pg.onboarding.presignup")['consent_status'];
                }
            }
            catch (\Exception $e)
            {

            }
            array_push($finalUsers, $user);
        }

        $response['users'] = $finalUsers;

        //escalations
        $onboardingescalations = (new Merchant\Escalations\Core)->fetchAllEscalationsForMerchant($merchant);
        $autoKycescalations    = $this->repo->merchant_auto_kyc_escalations->fetchEscalationsForMerchant($merchantId)->callOnEveryItem('toArrayPublic');

        $response['escalations'] = array_merge($onboardingescalations, $autoKycescalations);

        $response['features'] = (new Feature\Service())->getFeaturesForMerchantPublic($merchant)["features"];

        $response['invitations'] = $this->repo->invitation->fetchInvitations(Product::PRIMARY, $merchant->getMerchantId());

        $input = [
            "count" => 10,
            "skip"  => 0
        ];

        $response['transactions'] = $this->repo->payment->fetch($input, $merchantId)->toArrayPublic()['items'];

        $response['refunds'] = $this->repo->refund->fetch($input, $merchantId)->toArrayPublic()['items'];

        $response['disputes'] = $this->repo->dispute->fetch($input, $merchantId)->toArrayPublic()['items'];

        $response['credits'] = $creditsLogs = $this->repo->credits->fetch($input, $merchantId)->toArrayPublic()['items'];

        $response['settlements'] = $this->repo->settlement->fetch($input, $merchantId)->toArrayPublic()['items'];

        $input['phase']          = 'chargeback';
        $response['chargebacks'] = $this->repo->dispute->fetch($input, $merchantId)->toArrayPublic()['items'];

        $data = [
            StoreConstants::NAMESPACE => Merchant\Store\ConfigKey::ONBOARDING_NAMESPACE
        ];

        $response['config'] = (new StoreCore())->fetchMerchantStore($merchantId, $data, StoreConstants::INTERNAL);

        $response['referees'] = $this->repo->m2m_referral->getReferralsFromReferrerId($merchantId);

        $referral = $this->repo->m2m_referral->getReferralDetailsFromMerchantId($merchantId);

        $response['referral'] = $referral != null ? $referral->toArrayPublic() : [];

        return $response;

    }


}
