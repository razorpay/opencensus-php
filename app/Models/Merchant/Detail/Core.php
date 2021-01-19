<?php

namespace RZP\Models\Merchant\Detail;

use Mail;
use Queue;
use Config;

use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\State;
use RZP\Models\Coupon;
use RZP\Diag\EventCode;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Services\MerchantRiskClient;
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
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Metric;
use RZP\Constants\IndianStates;
use RZP\Models\Merchant\AutoKyc;
use RZP\Models\Admin\Permission;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Document;
use RZP\Models\Merchant\Constants;
use RZP\lib\ConditionParser\Parser;
use RZP\Models\Merchant\Promotion;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\LegalEntity;
use RZP\Models\Merchant\Stakeholder;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Action as Action;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\Base\PublicEntity as PublicEntity;
use RZP\Mail\Merchant\Rejection as RejectionEmail;
use RZP\Models\Workflow\Action\Core as ActionCore;
use RZP\Mail\Merchant\RazorpayX\L2SubmissionGreylist;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;
use RZP\Mail\Merchant\RazorpayX\L2SubmissionWhitelist;
use RZP\Models\Merchant\Detail\Metric as DetailMetric;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater;
use RZP\Mail\Admin\NotifyActivationSubmission as NotifyAdmin;
use RZP\Mail\Merchant\NeedsClarificationEmail as ClarificationEmail;
use RZP\Notifications\Onboarding\Handler as OnboardingNotificationHandler;
use RZP\Models\Merchant\Detail\BusinessDetailSearch\InMemoryBusinessSearch;

class Core extends Base\Core
{
    use NotifyTrait;
    use DispatchesJobs;

    protected $kycServiceRetryDelayInSecond;

    protected $mutex;

    private $mrclient;

    private $mcore;

    public function __construct()
    {
        parent::__construct();

        $this->kycServiceRetryDelayInSecond = (int) $this->app['config']['applications.kyc']['retry_delay'];

        $this->mutex = $this->app['api.mutex'];

        $this->mrclient = new MerchantRiskClient();

        $this->mcore = new Merchant\Core();
    }

    public function saveMerchantDetails(array $input,
                                        Merchant\Entity $merchant,
                                        string $originProduct = Product::PRIMARY)
    {
        $this->trace->info(
            TraceCode::MERCHANT_SAVE_ACTIVATION_DETAILS,
            [
                'input'       => $input,
                'merchant_id' => $merchant->getId(),
            ]);

        $merchantDetails = $this->getMerchantDetails($merchant, $input);

        $this->convertStatesToStatesCode($input);

        $merchantDetails->getValidator()->validateIsNotLocked($merchant);

        $merchantDetails->getValidator()->blockInstantActivationCriticalFields($input);

        $merchantDetails->edit($input);

        // do pan validation
        $this->verifyPOIDetailsIfApplicable($merchantDetails, $merchant, $input);

        $this->verifyCompanyPanDetailsIfApplicable($merchantDetails, $merchant, $input);

        $this->verifyGSTINIfApplicable($merchantDetails, $merchant, $input);

        $this->verifyShopEstbNumberIfApplicable($merchantDetails, $merchant, $input);

        $this->verifyCINDetailsIfApplicable($merchantDetails, $merchant, $input);

        return $this->mutex->acquireAndRelease(
            $merchant->getId(),
            function() use ($input, $merchantDetails, $merchant, $originProduct) {

                return $this->repo->transactionOnLiveAndTest(function() use (
                    $input,
                    $merchantDetails,
                    $merchant,
                    $originProduct
                ) {

                    $this->repo->merchant->lockForUpdate($merchant->getId());

                    $this->repo->merchant_detail->lockForUpdate($merchantDetails->getId());

                    $merchantDetails = $this->editMerchantDetailFields($merchant, $input);
                    $oldActivationStatus = $merchantDetails->getActivationStatus();

                    $response = $this->createResponse($merchantDetails);

                    if ($this->canSubmit($input, $response) === true)
                    {
                        // blacklisted merchant should not be allowed to submit l2 form
                        $merchantDetails->getValidator()->validateFullActivationForm($merchant);

                        $response = $this->submitActivationForm($merchant, $originProduct);

                        // If activation status changes to under_review and previous activation status is
                        // Needs Clarification, then it means merchant has responded to Needs Clarification.
                        // If merchant is NC responded then we want to trigger activation workflow
                        if($this->isNcResponded($oldActivationStatus, $merchantDetails->getActivationStatus()))
                        {
                            $this->triggerActivationWorkflow($merchant);
                        }
                    }
                    else
                    {
                        $response = $this->updateActivationProgress($merchant);
                    }
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

    private function triggerActivationWorkflow($merchant)
    {
        $statusChangeLogs = (new Merchant\Core)->getActivationStatusChangeLog($merchant);

        // agent who marked NC will be the maker of activation workflow
        $maker = $this->getNcMarkedAgent($statusChangeLogs);

        if(empty($maker))
        {
            return;
        }

        $tags = $this->getNcRespondedTags($merchant->merchantDetail, $statusChangeLogs, $maker);

        $input = [Entity::ACTIVATION_STATUS => Status::ACTIVATED];

        // The reason routeName and Controller is set here because
        // the workflow being triggered is associated with the different route.
        $this->app['workflow']
            ->setPermission(Permission\Name::EDIT_ACTIVATE_MERCHANT)
            ->setRouteName(DetailConstants::ACTIVATION_ROUTE_NAME)
            ->setController(DetailConstants::ACTIVATION_CONTROLLER)
            ->setWorkflowMaker($maker)
            ->setMakerFromAuth(false)
            ->setTags($tags)
            ->setRouteParams([Entity::ID => $merchant->getId()])
            ->setInput($input);

        try
        {
            $this->updateActivationStatus($merchant, $input, $maker);
        }
        catch(Exception\EarlyWorkflowResponse $e)
        {
            // Catching exception because we do not want to abort the code flow
            $workflowActionData = json_decode($e->getMessage(), true);
            $this->app['workflow']->saveActionIfTransactionFailed($workflowActionData);
        }
    }

    /**
     * Method that returns whether merchant has responded on penny testing failure
     * or Manual NC
     * @param string $ncrCountTag
     * @param $merchant
     * @return bool
     */
    protected function isNcrOnPennyTesting(string $ncrCountTag, $merchantDetails)
    {
        if($ncrCountTag === 'NCR1')
        {
            $bankDetailsVerificationStatus = $merchantDetails->getBankDetailsVerificationStatus();

            return ($bankDetailsVerificationStatus !== BankDetailsVerificationStatus::VERIFIED);
        }
        return false;
    }

    protected function getNcRespondedTags($merchantDetails, $statusChangeLogs, $maker)
    {
        $ncrCountTag = $this->getNcRespondedCountTag($statusChangeLogs);
        $tags = [
            $ncrCountTag,
            $this->getNcMarkedAgentTag($maker)
        ];

        if($this->isNcrOnPennyTesting($ncrCountTag, $merchantDetails))
        {
            $tags[] = "Auto NC";
        }

        if ($this->canAddAutoKycTag($merchantDetails)) {
            $tags[] = "auto-kyc";
        }
        return $tags;
    }

    protected function canAddAutoKycTag($merchantDetails)
    {
        $autoKyc = $this->isAutoKycDone($merchantDetails);
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
        $count = 0;
        $ncFound = false;
        foreach ($statusChangeLogs as $statusData)
        {
            if($statusData[State\Entity::NAME] === Status::NEEDS_CLARIFICATION)
            {
                $ncFound = true;
                continue;
            }
            if($statusData[State\Entity::NAME] === Status::UNDER_REVIEW and $ncFound)
            {
                $count++;
                $ncFound = false;
            }
        }

        if($count >= 3)
        {
            return "NCR3_greater";
        }

        return "NCR".$count;
    }

    protected function getNcMarkedAgent($statusChangeLogs)
    {
        $adminId = null;
        foreach ($statusChangeLogs as $statusData)
        {
            if($statusData[State\Entity::NAME] === Status::NEEDS_CLARIFICATION){
                $adminId = $statusData[State\Entity::ADMIN_ID];    // get the latest admin who marked NC
            }
        }

        if(!empty($adminId))
        {
            return $this->repo->admin->findOrFailPublic($adminId);
        }
        return null;
    }

    protected function getNcMarkedAgentTag($maker)
    {
        return "NCR_".$maker->getName();
    }

    public function submitActivationForm(Merchant\Entity $merchant, string $originProduct = Product::PRIMARY)
    {
        $this->repo->assertTransactionActive();

        $merchantDetails = $this->getMerchantDetails($merchant);

        $isImpersonated = $this->isMerchantImpersonated($merchant);

        $this->autoUpdateMerchantActivationFlows(
            $merchant, $merchantDetails, null, [Detail\Constants::INTERNATIONAL_ACTIVATION], false, $isImpersonated);

        if($isImpersonated === true)
        {
            $merchant->deactivate();
            $this->handleFlowForImpersonatedMerchant($merchant, $merchantDetails);
        }

        // If a merchant does not have website or app, we would need to activate them
        // only with PLs, Invoices and should not get API keys in live mode. Merchant's has_key_access
        // should be set to true only if one submits website details, there by will be able to
        // generate/access keys.
        $this->checkAndMarkHasKeyAccess($merchantDetails, $merchant);

        $this->markSubmittedAndLock($merchantDetails);

        $this->updateActivationSource($merchant, $originProduct);

        $statusToBeUpdated = $this->getApplicableActivationStatus($merchantDetails);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => $statusToBeUpdated,
        ];

        $this->updateActivationStatus($merchant, $activationStatusData, $merchant);

        $autoActivated = $this->autoActivateMerchantIfApplicable($merchant);

        $response = $this->updateActivationProgress($merchant);

        $response['auto_activated'] = $autoActivated;

        $eventAttributes = $merchant->toArrayEvent();

        $this->app['eventManager']->trackEvents($merchant, Merchant\Action::SUBMITTED, $eventAttributes);

        $this->attemptPennyTesting($merchantDetails, $merchant); // async

        $this->triggerValidationRequests($merchant, $merchantDetails);

        $this->fireActivationTrigger($merchantDetails, $merchant);

        $this->repo->saveOrFail($merchantDetails);

        return $response;
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
            $legalEntityInput[LegalEntity\Entity::MCC]                  = $merchant->getCategory();
            $legalEntityInput[LegalEntity\Entity::BUSINESS_CATEGORY]    = $input[Entity::BUSINESS_CATEGORY];

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
     * @param Merchant\Entity $merchant
     * @param Entity|null $merchantDetails
     * @param Merchant\Entity|null $partner
     * @param array|string[] $activationFlowTypes
     * @param bool $batchFlow
     * @param bool $isImpersonated
     */
    public function autoUpdateMerchantActivationFlows(Merchant\Entity $merchant,
                                                      Merchant\Detail\Entity $merchantDetails = null,
                                                      Merchant\Entity $partner = null,
                                                      array $activationFlowTypes = Detail\Constants::ACTIVATION_FLOWS,
                                                      bool $batchFlow = false, bool $isImpersonated = false
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

        if($isImpersonated === true)
        {
            $merchantDetails->setActivationFlow(ActivationFlow::GREYLIST);
        }

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
    public function updateToDefaultDepartmentVolumeIfApplicable(Entity & $merchantDetails)
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

        $merchantDetails = $this->getMerchantDetails($merchant, $input);

        $this->convertStatesToStatesCode($input);

        $merchantDetails->getValidator()->performInstantActivationValidations($input);

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

        return $this->transactionInstantActivationDetails($input,$merchantDetails, $merchant);
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
                        if ($merchantCore->isAutoKycEnabled($merchantDetails, $merchant) === true)
                        {
                            if ($this->canProcessInstantActivation($merchantDetails) === true)
                            {
                                $this->processInstantActivation($merchant, $merchantDetails);
                            }
                        }
                        else
                        {
                            $this->processInstantActivation($merchant, $merchantDetails);
                        }
                    }

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

    /**
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetails
     *
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\LogicException
     */
    protected function processInstantActivation(Merchant\Entity $merchant, Entity $merchantDetails)
    {
        $isImpersonated = $this->isMerchantImpersonated($merchant);

        if($isImpersonated)
        {
            $this->handleFlowForImpersonatedMerchant($merchant, $merchantDetails);

            // This is only for un-reg merchants; for reg we already show FE greylist popup;
            if($merchantDetails->isUnregisteredBusiness())
            {
                $merchantDetails->setLocked(true);
            }
        }

        $this->autoUpdateMerchantActivationFlows($merchant, $merchantDetails);

        if (BusinessType::isUnregisteredBusiness($merchantDetails->getBusinessType()) === true)
        {
            if ($isImpersonated === false and $this->canProcessInstantActivation($merchantDetails) === true)
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

    protected function handleFlowForImpersonatedMerchant(Merchant\Entity $merchant, Entity $merchantDetails)
    {
        $actions = (new ActionCore)->fetchOpenActionOnEntityOperationWithPermissionList(
            $merchant->getId(), 'merchant_detail', [Permission\Name::IMPERSONATING_MERCHANT_DEDUPE]);
        $actions = $actions->toArray();

        if(empty($actions) === false)
        {
            // If a workflow is already created, then do not create the same workflow;
            return;
        }

        $oldMerchantDetails = clone $merchantDetails;
        $newMerchantDetails = clone $merchantDetails;
        $newMerchantDetails->setActivationFlow(ActivationFlow::WHITELIST);

        $this->app['workflow']
            ->setPermission(Permission\Name::IMPERSONATING_MERCHANT_DEDUPE)
            ->setRouteName(DEConstants::ACTIVATION_ROUTE_NAME)
            ->setController(DEConstants::ACTIVATION_CONTROLLER)
            ->setWorkflowMaker($merchant)
            ->setWorkflowMakerType(MakerType::MERCHANT)
            ->setRouteParams([DetailEntity::ID => $merchant->getId()])
            ->setInput([])
            ->setEntity($merchant->merchantDetail->getEntity())
            ->setOriginal($oldMerchantDetails)
            ->setDirty($newMerchantDetails);

        try {
            $this->app['workflow']->handle();
        }
        catch(Exception\EarlyWorkflowResponse $e)
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
            $merchant, $merchantDetails, null,Detail\Constants::ACTIVATION_FLOWS, $batchFlow);

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
            $merchantDetails->stakeholder->setPoiStatus(null);

            return;
        }

        $dependentFields = [Detail\Entity::PROMOTER_PAN, Detail\Entity::PROMOTER_PAN_NAME, Detail\Entity::BUSINESS_TYPE];

        $requiredFields = [Detail\Entity::PROMOTER_PAN, Detail\Entity::PROMOTER_PAN_NAME];

        $isAutoKycAttemptRequired = $this->isAutoKycAttemptRequired(
            $dependentFields,
            $input,
            Entity::POI_VERIFICATION_STATUS,
            [POIStatus::FAILED],
            $merchant->getId());

        if (($isAutoKycAttemptRequired === false) or
            ($this->hasAllRequiredFields($merchantDetails, $input, $requiredFields) === false))
        {
            return;
        }

        $response = null;

        $verificationStatus = POIStatus::FAILED;
        try
        {
            $input = [
                DEConstants::PAN_NUMBER        => $merchantDetails->getPromoterPan(),
                DEConstants::PROMOTER_PAN_NAME => $merchantDetails->getPromoterPanName()
            ];

            $verificationStatus = (new AutoKyc\Core())->verifyPOI($merchantDetails, $input);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         null,
                                         TraceCode::MERCHANT_POI_VERIFICATION_FAILED);

        }

        $merchantDetails->setPoiVerificationStatus($verificationStatus);
        $merchantDetails->stakeholder->setPoiStatus($verificationStatus);

        $dimension = $this->fetchPoiMetricDimensions($merchantDetails);

        $this->trace->count(DetailMetric::POI_VERIFICATION_STATUS_TOTAL, $dimension);
    }

    /**
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     * @param array           $input
     */
    protected function verifyCompanyPanDetailsIfApplicable(Entity $merchantDetails, Merchant\Entity $merchant, array $input)
    {
        // For handling business type switch
        if (BusinessType::isCompanyPanEnableBusinessTypes($merchantDetails->getBusinessTypeValue()) === false)
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
            $input,
            Entity::COMPANY_PAN_VERIFICATION_STATUS,
            [CompanyPanStatus::FAILED],
            $merchant->getId());

        if (($isAutoKycAttemptRequired === false) or
            ($this->hasAllRequiredFields($merchantDetails, $input, $dependentFields) === false))
        {
            return;
        }

        $response = null;

        $verificationStatus = CompanyPanStatus::FAILED;

        try
        {
            $input = [
                DEConstants::COMPANY_PAN      => $merchantDetails->getPan(),
                DEConstants::COMPANY_PAN_NAME => $merchantDetails->getBusinessName(),
            ];

            $verificationStatus = (new AutoKyc\Core())->verifyCompanyPan($merchantDetails, $input);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         null,
                                         TraceCode::MERCHANT_COMPANY_PAN_VERIFICATION_FAILED);

        }

        $merchantDetails->setCompanyPanVerificationStatus($verificationStatus);

        $dimension = $this->fetchBusinessPanMetricDimensions($merchantDetails);

        $this->trace->count(DetailMetric::COMPANY_PAN_VERIFICATION_STATUS_TOTAL, $dimension);
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
                [ 'merchant_id'    => $merchant->getId() ]);

            $merchantDetails = $this->createMerchantDetails($merchant, $input);

            // if merchant details are created, load relation in $merchant
            $merchant->load('merchantDetail');
        }

        // to create if not exists or fetch and set in $details->stakeholder relation
        (new Stakeholder\Core)->createOrFetchStakeholder($merchantDetails);

        return $merchantDetails;
    }

    /**
     * This function is used to patch merchant details fields
     *
     * @param Merchant\Entity $merchant
     * @param array  $input
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
     * @param array $input
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

        $existingKycClarifications = $merchantDetails->getKycClarificationReasons() ?? [];
        $existingReasons           = $existingKycClarifications[Entity::CLARIFICATION_REASONS] ?? null;

        $newKycClarifications      = $input[Entity::KYC_CLARIFICATION_REASONS] ?? [];
        $newAdditionalDetails      = $newKycClarifications[Entity::ADDITIONAL_DETAILS] ?? null;
        $newReasons                = $newKycClarifications[Entity::CLARIFICATION_REASONS] ?? null;

        if ((empty($newReasons) === true) and
            (empty($newAdditionalDetails) === true))
        {
            return $existingKycClarifications;
        }

        $statusChangeLogs = (new Merchant\Core)->getActivationStatusChangeLog($merchantDetails->merchant);

        $ncCount = $this->getStatusChangeCount($statusChangeLogs, Status::UNDER_REVIEW);

        if (empty($newReasons) === false)
        {
            //
            // new reason is not null then reassign existing reason as we are appending new reasons in existing
            //
            $existingReasons = $existingReasons ?? [];

            foreach ($existingReasons as $key => $values)
            {
                foreach ($values as &$val)
                {
                    if ($val[Merchant\Constants::NC_COUNT] !== $ncCount)
                    {
                        $val[Merchant\Constants::IS_CURRENT] = false;
                    }
                }

                $existingReasons[$key] = $values;
            }

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

        return [
            Entity::CLARIFICATION_REASONS   =>  $existingReasons,
            Entity::ADDITIONAL_DETAILS      =>  $newAdditionalDetails,
            Merchant\Constants::NC_COUNT    =>  $ncCount
        ];
    }

    public function editMerchantDetailFields(Merchant\Entity $merchant, array $input): Entity
    {
        $merchantDetails = $this->getMerchantDetails($merchant, $input);

        if (isset($input[Entity::REVIEWER_ID]) === true)
        {
            $reviewerId = $input[Entity::REVIEWER_ID];

            unset($input[Entity::REVIEWER_ID]);

            AdminEntity::verifyIdAndStripSign($reviewerId);

            $reviewer = $this->repo->admin->findOrFailPublic($reviewerId);

            $merchantDetails->reviewer()->associate($reviewer);
        }

        $merchantDetails->edit($input);

        $kycClarificationReasons = $this->getUpdatedKycClarificationReasons($input, $merchantDetails->getMerchantId());

        if(empty($kycClarificationReasons) === false)
        {
            $merchantDetails->setKycClarificationReasons($kycClarificationReasons);
        }

        $this->autoUpdateMerchantCategoryDetailsIfApplicable($merchantDetails, $merchant);

        $this->repo->saveOrFail($merchantDetails);

        $this->updateLegalEntity($input, $merchant);

        // Sync few input fields to merchant entity
        (new Merchant\Core)->syncMerchantEntityFields($merchant, $input);

        // dual write promoter related fields to stakeholder entity
        (new Stakeholder\Core)->syncMerchantDetailFieldsToStakeholder($merchantDetails, $input);

        $this->repo->saveOrFail($merchant);

        return $merchantDetails;
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

        (new Document\Core)->storeInMerchantDocument($merchant, $merchantDocumentParams);
    }

    public function createMerchantDetails(Merchant\Entity $merchant, array $input = [])
    {
        $merchantDetail = (new Entity)->build($input);

        $merchantDetail->setContactEmail($merchant->getEmail());

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
            else if ($activationFlow === ActivationFlow::GREYLIST)
            {
                Mail::queue(new L2SubmissionGreylist($merchant->getId()));
            }
        }
    }

    protected function adminNotifyActivationSubmission(Entity $merchantDetails)
    {
        $data = $merchantDetails->toArray();

        $notifyAdminMail = new NotifyAdmin($data);

        Mail::queue($notifyAdminMail);
    }

    protected function canSubmit($input, $response)
    {
        return (($response['can_submit'] === true) and
                (isset($input[Entity::SUBMIT]) === true) and
                ($input[Entity::SUBMIT] === '1'));
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
                'has_key_access'   => $merchant->getHasKeyAccess()
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
     * This function is used for archiving merchant activation form
     * @param Entity $merchantDetails
     * @param array $input
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
        $merchantDetails = $this->getMerchantDetails($merchant, $input);

        $merchantDetails->getValidator()->validateInput('activationStatus', $input);

        $currentActivationStatus = $merchantDetails->getActivationStatus();

        $merchantDetails->getValidator()
                        ->validateActivationStatusChange(
                            $currentActivationStatus,
                            $input[Entity::ACTIVATION_STATUS]);

        $this->trace->info(
            TraceCode::MERCHANT_UPDATE_ACTIVATION_STATUS,
            ['input' => $input]);

        $rejectionReasons = [];

        if (empty($input[Entity::REJECTION_REASONS]) === false)
        {
            $rejectionReasons = $input[Entity::REJECTION_REASONS];

            unset($input[Entity::REJECTION_REASONS]);
        }

        $oldMerchantDetails = clone $merchantDetails;

        $merchantDetails->edit($input);

        $newMerchantDetails = clone $merchantDetails;

        $this->repo->transactionOnLiveAndTest(function() use (
                                                            $merchantDetails,
                                                            $oldMerchantDetails,
                                                            $newMerchantDetails,
                                                            $input,
                                                            $rejectionReasons,
                                                            $maker, $merchant)
        {
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

                (new Merchant\Activate)->activate($merchant);

                // request for default instruments when merchant is activated
                $this->app['terminals_service']->requestDefaultMerchantInstruments($merchant->getId());
            }

            if (($input[Entity::ACTIVATION_STATUS] === Status::ACTIVATED_MCC_PENDING) and
                ($merchant->isLinkedAccount() === false))
            {

                (new Merchant\Activate)->activate($merchant, false);

                // request for default instruments when merchant is activated

                $this->app['terminals_service']->requestDefaultMerchantInstruments($merchant->getId());
            }

            if ($input[Entity::ACTIVATION_STATUS] === Status::REJECTED)
            {
                $this->triggerWorkflowForRejectionActivationStatusChange(
                    $oldMerchantDetails,
                    $newMerchantDetails,
                    $rejectionReasons);

                $merchant->deactivate();

                $this->sendRejectionEmail($merchant);
            }

            if ($input[Entity::ACTIVATION_STATUS] === Status::NEEDS_CLARIFICATION)
            {
                // If merchant responds to NC, activation workflow is created
                // This workflow should get auto closed if agent marks NC again
                (new ActionCore)->autoCloseActivationWorkflowActionIfOpen(
                    $merchant->getId(), 'merchant_detail');

                //
                // For Older merchant who are still in old flow ,
                // kyc clarification will be empty in this case form should not get unlocked
                //
                if (empty($merchantDetails->getKycClarificationReasons()) === false)
                {
                    $merchantDetails->setLocked(false);

                    $this->sendNeedsClarificationEmail($merchant);
                }
            }

            $this->trace->info(
                TraceCode::MERCHANT_ACTIVATION_LOGS,
                [
                    'text' => 'before saving merchant',
                    'merchant' => $merchant
                ]
            );

            $this->repo->saveOrFail($merchantDetails);

            $this->repo->saveOrFail($merchant);

            $this->trace->info(
                TraceCode::MERCHANT_ACTIVATION_LOGS,
                [
                    'text' => 'after saving merchant',
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

                $this->app['events']->fire($event, $eventPayload);
            }
        });

        $customProperties['activation_status'] = $currentActivationStatus;

        $this->app['diag']->trackOnboardingEvent(EventCode::ACT_CHANGE_ACTIVATION_STATUS_SUCCESS,
                                                 $merchant,
                                                 null,
                                                 $customProperties);

        $this->trace->count(
            Metric::MERCHANT_ACTIVATION_STATE_TRANSITION,
            $this->fetchActivationStatusTransitionMetricDimensions(
                $merchantDetails->getActivationStatus(),
                $currentActivationStatus));

        $isWhatsappEnabled = (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(),
            RazorxTreatment::WHATSAPP_NOTIFICATIONS);

        if($isWhatsappEnabled === true)
        {
            $args = [
                'activationStatus'  => $currentActivationStatus,
                'merchant'          => $merchant
            ];
            (new OnboardingNotificationHandler($args))->send();
        }
        else
        {
            $this->sendSmsBasedOnMilestones($currentActivationStatus, $merchantDetails);
        }

        return $merchantDetails;
    }

    /**
     * @param $merchant
     */
    public function sendNeedsClarificationEmail(Merchant\Entity $merchant)
    {
        $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

        $merchantDetail = $merchant->merchantDetail;

        $clarificationCore = New Detail\NeedsClarification\Core();

        $clarificationReasons = $clarificationCore->getFormattedKycClarificationReasons(
            $merchantDetail->getKycClarificationReasons());

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

        $email = new ClarificationEmail($data, $org->toArray());

        Mail::queue($email);
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

        return $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails, $input)
        {
            $this->repo->saveOrFail($merchantDetails);

            $merchant = $merchantDetails->merchant;

            // Sync few input fields to merchant entity
            $merchant = (new Merchant\Core)->syncMerchantEntityFields($merchant, $input);

            $this->checkAndMarkHasKeyAccess($merchantDetails, $merchant);

            $this->repo->saveOrFail($merchant);

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

    public function getValidationFields(Entity $merchantDetails): array
    {
        // @todo: Activation flow will define its own validation fields

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = ValidationFields::getValidationFields($merchantDetails);

        if (self::shouldSkipBankAccountRegistration() === true)
        {
            $validationFields = array_diff($validationFields, RequiredFields::BANK_ACCOUNT_FIELDS);
        }

        $merchant = $merchantDetails->merchant;

        if ($merchant->isLinkedAccount() === true)
        {
            $validationFields         = RequiredFields::MARKETPLACE_ACCOUNT_FIELDS;
            $validationSelectiveRequiredFields = [];
            $validationOptionalFields = [];

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

    public function createResponse(Entity $merchantDetails): array
    {
        $response = $merchantDetails->toArrayPublic();


        //
        // refreshing the merchant relation here as createResponse is called at many places
        // just after updating the merchant entity
        //
        $merchantDetails->load('merchant');

        $merchant = $merchantDetails->merchant;

        if ($merchant->isLinkedAccount() === true)
        {
            $parentMerchant = $merchant->parent;

            //
            // set key `need_kyc` for the client to determine where full KYC is needed
            // for a linked accounts activation
            //
            $response['need_kyc']                   = (int) $parentMerchant->linkedAccountsRequireKyc();
            $response['linked_account']             = true;
            $response['marketplace_merchant_name']  = $parentMerchant->getName();
            $response['marketplace_merchant_id']    = $parentMerchant->getId();
        }

        $currentActivationState = $merchant->currentActivationState();

        if ((empty($currentActivationState) === false) and
            ($currentActivationState->name === Status::REJECTED))
        {
            $rejectionReasons = $currentActivationState->rejectionReasons()->get();

            $response[Entity::REJECTION_REASONS] = $rejectionReasons->toArrayPublic();
        }

        $response = $this->setVerificationDetails($merchantDetails, $merchant, $response);

        $response[Merchant\Entity::ACTIVATED]                   = (int) $merchant->isActivated();
        $response[Merchant\Entity::LIVE]                        = $merchant->isLive();
        $response[Merchant\Entity::INTERNATIONAL]               = $merchant->isInternational();
        $response[Constants::MERCHANT]                          = $merchant->toArrayPublic();

        $response = $this->appendBankingSpecificDetails($response, $merchant);

        return $response;
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

        if (empty($balance) === false)
        {
            $bankingAccount = $this->repo
                                   ->banking_account
                                   ->getFromBalanceId($balance->getId());

            $response[Merchant\Entity::BANKING_ACCOUNT] = $bankingAccount->toArrayPublic();
        }

        $response[Merchant\Entity::CREDIT_BALANCE]  = $this->fetchBankingCreditBalances(
                                                                            $merchant->getId(),
                                                                            Product::BANKING);

        return $response;
    }

    protected function fetchBankingCreditBalances($merchantId, $product)
    {
        $creditBalances = $this->repo
                                ->credits
                                ->getTypeAggregatedMerchantCreditsForProductForDashboard(
                                    $merchantId,
                                    $product);

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
        $success     = 0;

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

                $this->trace->info(TraceCode::MERCHANT_MTU_UPDATE_SUCCESS,['id' => $merchantId]);
            }
            catch(\Exception $e)
            {
                $failedItems[] = [
                    Entity::MERCHANT_ID => $merchantId,
                    'error'             => $e->getMessage()
                ];

                $this->trace->info(TraceCode::MERCHANT_MTU_UPDATE_FAILURE,['id' => $merchantId]);
            }
        }

        $response = [
            'success'       => $success,
            'failed'        => count($failedItems),
            'failedItems'   => $failedItems,
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
       * @param array  $extra
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
     * @param array $extra
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

    /**
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     *
     * @throws \Throwable
     */
    protected function attemptPennyTesting(Entity $merchantDetails, Merchant\Entity $merchant)
    {
        if ((new Merchant\Core())->isAutoKycEnabled($merchantDetails, $merchant) === false)
        {
            return;
        }

        //
        // if bank detail is already attempted then skip penny testing and send to manual queue .
        //
        if ($merchantDetails->getBankDetailsVerificationStatus() !== null)
        {
            return;
        }

        // no penny testing for linked accounts
        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        if ($this->shouldSkipBankAccountRegistration() == true)
        {
            return;
        }

        $verifyBankDetailsThoughBvs = $this->updateDocumentVerificationStatus($merchant, Entity::BANK_ACCOUNT_NUMBER);

        if ($verifyBankDetailsThoughBvs === true)
        {
            return;
        }

        (new PennyTesting())->triggerPennyTesting($merchantDetails);
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
        $autoKyc = $this->isAutoKycDone($merchantDetails);

        if ($autoKyc)
        {
            switch ($merchantDetails->getBusinessType())
            {
                case BusinessType::NOT_YET_REGISTERED:
                case BusinessType::INDIVIDUAL:
                    return Status::ACTIVATED;

                case BusinessType::PROPRIETORSHIP:
                case BusinessType::PRIVATE_LIMITED:
                case BusinessType::PUBLIC_LIMITED:
                case BusinessType::LLP:
                    return $this->getApplicableActivationStatusForRegisteredMerchant($merchantDetails);
            }
        }
        return Status::UNDER_REVIEW;
    }

    private function getApplicableActivationStatusForRegisteredMerchant($merchantDetails)
    {
        $excludeActivationStatusList = [
            Status::NEEDS_CLARIFICATION,
            Status::ACTIVATED,
            Status::REJECTED
        ];
        $currentActivationStatus = $merchantDetails->getActivationStatus();
        $isWhitelisted = ($merchantDetails->getActivationFlow() === ActivationFlow::WHITELIST);

        if ($isWhitelisted === true and
            (in_array($currentActivationStatus, $excludeActivationStatusList) === false))
        {
            $isSelfServeEnabled = (new Merchant\Core())->isRazorxExperimentEnable(
                $merchantDetails->getMerchantId(),
                RazorxTreatment::SELF_SERVE_AUTO_KYC
            );

            if ($isSelfServeEnabled) {
                return Status::ACTIVATED_MCC_PENDING;
            }
        }

        return Status::UNDER_REVIEW;
    }

    public function isAutoKycDone($merchantDetails)
    {
        $businessType = $merchantDetails->getBusinessType();

        if (isset($businessType) === false or $businessType === '')
        {
            return false;
        }

        if(isset(AutoKyc\Constants::AUTO_KYC_VERIFICATION_CONDITIONS[$businessType]) === false)
        {
            return false;
        }

        $conditions = AutoKyc\Constants::AUTO_KYC_VERIFICATION_CONDITIONS[$businessType];

        return (new Parser)->parse($conditions, function ($key, $value) use ($merchantDetails){
            return in_array($merchantDetails->getAttribute($key), $value, true);
        });
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
     * @param $validationDocumentFields
     * @param $documentsResponse
     * @param $requiredFields
     */
    protected function calculateRequiredDocumentFields($validationDocumentFields,
                                                       $documentsResponse,
                                                       &$requiredFields): void
    {
        foreach ($validationDocumentFields as $requiredDocumentField => $documentGroups)
        {
            //
            // if merchant uploads all documents of a document group then
            // we consider  required document field to be filled
            //
            $isFieldPresent = array_reduce($documentGroups, function($isFieldPresent, $documentGroup) use ($documentsResponse)
            {
                $isDocumentGroupFilled = count(array_diff($documentGroup, array_keys($documentsResponse))) === 0;

                $isFieldPresent = ($isFieldPresent or $isDocumentGroupFilled);

                return $isFieldPresent;

            }, false);

            if ($isFieldPresent === false)
            {
                $requiredFields = array_merge($requiredFields, $documentGroups[0]);
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
                    'has_key_access'     => $merchant->getHasKeyAccess()
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

        $rejectionMail = new RejectionEmail($data, $org->toArray());

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
     * @param Merchant\Entity $merchant
     * @param Entity $merchantDetails
     * @param Merchant\Entity|null $partner
     * @param array|string[] $activationFlowTypes
     * @param bool $batchFlow
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
     * @param array  $input
     * @param string $documentVerificationStatusFieldKey
     * @param array  $retriableVerificationStatus
     * @param string $merchantId
     *
     * @return bool
     */
    protected function isAutoKycAttemptRequired(
        array $dependentFields,
        array $input,
        string $documentVerificationStatusFieldKey,
        array $retriableVerificationStatus,
        string $merchantId)
    {

        $merchantDetails = $this->repo->merchant_detail->findByPublicId($merchantId);

        $verificationStatus = $merchantDetails->getAttribute($documentVerificationStatusFieldKey);

        //
        // Check if document verification status is not already set,
        // This is for handling backward compatibility test, for few merchant who have filled form before autokyc
        // Fields won't change but we still need to call
        //

        if (empty($verificationStatus) === true)
        {
            return true;
        }

        if(array_search($verificationStatus, $retriableVerificationStatus, true) !== false)
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

    public function hasAllRequiredFields(Entity $merchantDetails, array $input, array $requiredFields)
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

    private function setVerificationDetails(Entity $merchantDetails, Merchant\Entity $merchant, array $response)
    {
        $requiredFields = [];

        $merchantDetailsArr = $merchantDetails->toArray();

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $this->getValidationFields($merchantDetails);

        $totalFields = count($validationFields) + count($validationSelectiveRequiredFields);

        $documentsResponse = $this->documentCore()->documentResponse($merchant);

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

        $this->calculateRequiredDocumentFields(
            $validationSelectiveRequiredFields,
            $documentsResponse,
            $requiredFields);

        $isAutoKycDocumentsVerificationStatusAllowed = (new FormSubmissionValidStatusesMap())->isDocumentsStatusValidForFormSubmission(
            $merchantDetails,
            FormSubmissionValidStatusesMap::DOCUMENT_LIST_L2);

        if ((count($requiredFields) > 0) or
            ($isAutoKycDocumentsVerificationStatusAllowed === false))
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

        return $response;
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
            $input,
            Entity::GSTIN_VERIFICATION_STATUS,
            [GSTINVerificationStatus::FAILED],
            $merchant->getId());

        if (($isAutoKycAttemptRequired === false) or
            ($this->hasAllRequiredFields($merchantDetails, $input, $requiredFields) === false))
        {
            return;
        }

        $this->updateDocumentVerificationStatus($merchant, Constant::GSTIN);
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
            $input,
            Entity::CIN_VERIFICATION_STATUS,
            [CinVerificationStatus::FAILED],
            $merchant->getId());

        if (($isAutoKycAttemptRequired === false) or
            ($this->hasAllRequiredFields($merchantDetails, $input, $dependentFields) === false))
        {
            return;
        }
        $this->updateCINorLLPINStatusForBvsVerification($merchant, $merchantDetails);
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

        $this->updateDocumentVerificationStatus($merchant, $fieldType);
    }

    /**
     * Returns true if llp business type
     *
     * @param string $businessType`
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
     * @param Entity $merchantDetail
     * @param string $template
     */
    public function sendOnboardingJourneySms(Entity $merchantDetail, string $template)
    {
        if ($merchantDetail->merchant->isRazorpayOrgId() === false or
            (new \RZP\Models\Partner\Core())->isSmsBlockedSubmerchant($merchantDetail->merchant) === true)
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

        if(empty($smsTemplateName) === false)
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
    public function getBusinessDetails(array $input) : array
    {
        (new Validator())->validateInput('search_business_details', $input);

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

        $isCompanySearchRazorxExperimentEnabled = (new Merchant\Core())->isRazorxExperimentEnable(
            $this->merchant->getId(),
            RazorxTreatment::BVS_COMPANY_SEARCH);

        if ($isCompanySearchRazorxExperimentEnabled === false)
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

        if ($this->isMerchantImpersonated($merchant))
        {
            return ActivationFlow::GREYLIST;
        }

        return $activationFlow;
    }

    private function isMerchantImpersonated(Merchant\Entity $merchant) : bool
    {
        $isDedupeEnabled = $this->mcore->isRazorxExperimentEnable($merchant->getId(),
            RazorxTreatment::DEDUPE_FUNCTIONALITY);

        if($isDedupeEnabled === true)
        {
            $riskFactor = $this->mrclient->getMerchantRiskFactor($merchant);

            if (isset($riskFactor['impersonated']) === true and
                $riskFactor['impersonated'] === true)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if promotional coupon campaign is enabled
     *
     * @param string $merchantId
     *
     * @return bool
     */
    public function isPromoCodeActive(string $merchantId) : bool
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

    /**
     * Triggers validation requests
     *
     * @param Merchant\Entity $merchant
     * @param Entity          $merchantDetails
     */
    protected function triggerValidationRequests(Merchant\Entity $merchant, Entity $merchantDetails): void
    {
        $factory = new requestDispatcher\Factory();

        $requestCreators = $factory->getBvsRequestDispatchers($merchant, $merchantDetails);

        foreach ($requestCreators as $requestCreator)
        {
            if ($requestCreator instanceof requestDispatcher\RequestDispatcher)
            {
                $requestCreator->triggerBVSRequest();
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

        $this->updateDocumentVerificationStatus($merchant, Entity::SHOP_ESTABLISHMENT_NUMBER);
    }

    /**
     * This function is to update Document verification status as pending
     * so that verification can be triggered at Form Submission for all such document types.
     *
     * @param Merchant\Entity $merchant
     * @param string          $field // this key can refer to both (proof as well as identifier)
     *
     * @return bool
     * @throws \RZP\Exception\LogicException
     */
    public function updateDocumentVerificationStatus(Merchant\Entity $merchant, string $field): bool
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

            $this->trace->info(TraceCode::ONBOARDING_FIELD_VERIFICATION_REQUEST_RECEIVED, ['field' => $field]);

            $artefactDetails = Constant::FIELD_ARTEFACT_DETAILS_MAP[$field];

            $validation = new Merchant\BvsValidation\Entity();

            $validation->setValidationUnit($artefactDetails[Constant::VALIDATION_UNIT]);

            $validation->setArtefactType($artefactDetails[Constant::ARTEFACT_TYPE]);

            $statusUpdateFactory = new DocumentStatusUpdater\Factory();

            $statusUpdater = $statusUpdateFactory->getInstance($merchant, $validation);

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
}
