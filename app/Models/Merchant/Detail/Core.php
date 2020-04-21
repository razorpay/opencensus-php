<?php

namespace RZP\Models\Merchant\Detail;

use Mail;
use Queue;
use Config;

use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;

use RZP\Models\Base;
use RZP\Models\State;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Jobs\RequestJob;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Batch\Type;
use RZP\Constants\Product;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\State\Reason;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\AutoKyc;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\Document;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\LegalEntity;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Merchant\Action as Action;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\Base\PublicEntity as PublicEntity;
use RZP\Mail\Merchant\RazorpayX\L2SubmissionGreylist;
use RZP\Mail\Merchant\RazorpayX\L2SubmissionWhitelist;
use RZP\Mail\Merchant\Rejection as RejectionEmail;
use RZP\Models\Merchant\Detail\Metric as DetailMetric;
use RZP\Models\Merchant\Document\OcrVerificationStatus;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Mail\Admin\NotifyActivationSubmission as NotifyAdmin;
use RZP\Mail\Merchant\NotifyActivationSubmission as NotifyMerchant;
use RZP\Mail\Merchant\NeedsClarificationEmail as ClarificationEmail;

class Core extends Base\Core
{
    use NotifyTrait;
    use DispatchesJobs;

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

        $merchantDetails->getValidator()->validateIsNotLocked($merchant);

        $merchantDetails->getValidator()->blockInstantActivationCriticalFields($input);

        $merchantDetails->edit($input);

        // do pan validation
        $this->verifyPOIDetailsIfApplicable($merchantDetails, $merchant, $input);

        $this->verifyCompanyPanDetailsIfApplicable($merchantDetails, $merchant, $input);

        return $this->repo
                    ->transactionOnLiveAndTest(
                        function () use (
                            $input,
                            $merchantDetails,
                            $merchant,
                            $originProduct
                        )
                        {
                            $merchantDetails = $this->editMerchantDetailFields($merchant, $input);

                            $response = $this->createResponse($merchantDetails);

                            if ($this->canSubmit($input, $response) === true)
                            {
                                // blacklisted merchant should not be allowed to submit l2 form
                                $merchantDetails->getValidator()->validateFullActivationForm($merchant);

                                $response = $this->submitActivationForm($merchant, $originProduct);
                            }
                            else
                            {
                                $response = $this->updateActivationProgress($merchant);
                            }

                            return $response;
                        });
    }

    public function submitActivationForm(Merchant\Entity $merchant, string $originProduct = Product::PRIMARY)
    {
        $this->repo->assertTransactionActive();

        $merchantDetails = $this->getMerchantDetails($merchant);

        $this->autoUpdateMerchantActivationFlows($merchant, null, [Detail\Constants::INTERNATIONAL_ACTIVATION]);

        $this->updatePoaVerificationStatusIfApplicable($merchantDetails, $merchant);

        // If a merchant does not have website or app, we would need to activate them
        // only with PLs, Invoices and should not get API keys in live mode. Merchant's has_key_access
        // should be set to true only if one submits website details, there by will be able to
        // generate/access keys.
        $this->checkAndMarkHasKeyAccess($merchantDetails, $merchant);

        $this->markSubmittedAndLock($merchantDetails);

        $this->updateActivationSource($merchant, $originProduct);

        $statusToBeUpdated = $this->getApplicableActivationStatus($merchantDetails, $merchant);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => $statusToBeUpdated,
        ];

        $this->updateActivationStatus($merchant, $activationStatusData, $merchant);

        $autoActivated = $this->autoActivateMerchantIfApplicable($merchant);

        $response = $this->updateActivationProgress($merchant);

        $response['auto_activated'] = $autoActivated;

        $eventAttributes = $merchant->toArrayEvent();

        $this->app['eventManager']->trackEvents($merchant, Merchant\Action::SUBMITTED, $eventAttributes);

        //
        // does penny testing for un-registered business type
        //
        $this->attemptPennyTesting($merchantDetails, $merchant); // async

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
     * Updates merchant detail poa status to verified if any one
     * of the document uploaded by merchant is verified
     *
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     */
    public function updatePoaVerificationStatusIfApplicable(Entity $merchantDetails, Merchant\Entity $merchant)
    {
        if ((new Merchant\Core)->isUnRegisteredOnBoardingEnabled($merchant,
                                                                 $merchantDetails->isUnregisteredBusiness()) === false)
        {
            return;
        }

        // no poa verification for linked accounts
        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $documents = $merchant->merchantDocuments;

        $isOcrVerified = false;

        $documentType = '';

        //
        // Update PoaVerificationStatus to Verified if any document uploaded has OCR Verified.
        //
        foreach ($documents as $document)
        {
            if ((isset($document[Document\Entity::OCR_VERIFY]) === true) and
                ($document[Document\Entity::OCR_VERIFY] === OcrVerificationStatus::VERIFIED))
            {
                $this->trace->info(
                    TraceCode::MERCHANT_VERIFY_POA,
                    [
                        'document_type' => $document[Document\Entity::DOCUMENT_TYPE],
                    ]);

                $documentType =  $document[Document\Entity::DOCUMENT_TYPE];

                $isOcrVerified = true;

                break;
            }
        }
        $poaVerificationStatus =
            ($isOcrVerified === true) ? PoaVerificationStatus::VERIFIED : PoaVerificationStatus::FAILED;

        $this->trace->count(DetailMetric::POA_VERIFICATION_STATUS_TOTAL,
                            [
                                Detail\Constants::POA_STATUS    => $poaVerificationStatus,
                                Detail\Constants::DOCUMENT_TYPE => $documentType
                            ]);

        $merchantDetails->setPoaVerificationStatus($poaVerificationStatus);

        $this->repo->saveOrFail($merchantDetails);
    }

    /**
     * fetches activation_flow and international_activation value
     * using business category and subcategory, then updates in
     * merchant_details table.
     *
     * For unregistered business bucket we skip activation flow
     *
     * @param Merchant\Entity $merchant
     *
     * @param Merchant\Entity $partner
     * @param array           $activationFlowTypes
     */
    public function autoUpdateMerchantActivationFlows(Merchant\Entity $merchant,
                                                      Merchant\Entity $partner = null,
                                                      array $activationFlowTypes = Detail\Constants::ACTIVATION_FLOWS
    )
    {
        $this->repo->assertTransactionActive();

        $merchantDetails = $this->getMerchantDetails($merchant);

        if ((new Merchant\Core)->isUnRegisteredOnBoardingEnabled($merchant,
                                                                 $merchantDetails->isUnregisteredBusiness()) === true)
        {
            $merchantDetails->setActivationFlow();
            $merchantDetails->setInternationalActivationFlow();

            return;
        }

        $this->updateActivationFlows($merchant, $partner, $activationFlowTypes);

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

    protected function autoUpdateActivationFlow(Merchant\Entity $merchant, $partner = null)
    {
        $merchantDetails = $this->getMerchantDetails($merchant);

        //
        // if request comes via on-boarding api, it will always be grey-list as on-boarding api
        // does not support instant activation flow
        //
        if (empty($partner) === false)
        {
            $activationFlow = ActivationFlow::GREYLIST;
        }
        else
        {
            $subcategory = $merchantDetails->getBusinessSubcategory();
            $category    = $merchantDetails->getBusinessCategory();

            $subcategoryMetaData = BusinessSubCategoryMetaData::getSubCategoryMetaData($category, $subcategory);

            $activationFlow = $subcategoryMetaData[Entity::ACTIVATION_FLOW];
        }

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
        Merchant\Entity $merchant)
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
            (new Merchant\Core)->autoUpdateCategoryDetails($merchant, $businessCategory, $businessSubcategory);
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

        $merchantDetails->getValidator()->performInstantActivationValidations($input);

        $merchantDetails->edit($input, 'instant_activation');

        // do pan validation

        $this->verifyPOIDetailsIfApplicable($merchantDetails, $merchant, $input);

        // do business pan validation
        $this->verifyCompanyPanDetailsIfApplicable($merchantDetails, $merchant, $input);


        return $this->repo->transactionOnLiveAndTest(function() use ($input, $merchantDetails, $merchant) {
            // The function below, uses isDirty() and hence must be called before saveOrFail over merchantDetails
            $this->autoUpdateMerchantCategoryDetailsIfApplicable($merchantDetails, $merchant);

            $this->autoUpdateMerchantActivationFlows($merchant);

            $this->updateToDefaultDepartmentVolumeIfApplicable($merchantDetails);

            $this->updateLegalEntity($input, $merchant);

            $this->repo->saveOrFail($merchantDetails);

            $merchantCore = new Merchant\Core();
            // Sync few input fields to merchant entity
            $merchant = $merchantCore->syncMerchantEntityFields($merchant, $input);

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

            $response = $this->createResponse($merchantDetails);

            // used to show the progress of the activation form on the dashboard
            $activationProgress = $response['verification']['activation_progress'];
            $merchantDetails->setActivationProgress($activationProgress);
            $this->repo->saveOrFail($merchantDetails);

            $this->trackActivationProgressEvents($merchant, $activationProgress);

            $this->app->hubspot->trackL1ContactProperties($input, $merchant, $merchantDetails->getActivationFlow());

            // Only Linked accounts will have auto Activated set to true.
            $response['auto_activated'] = false;

            return $response;
        });
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
        if (BusinessType::isUnregisteredBusiness($merchantDetails->getBusinessType()) === true)
        {
            if ($this->canProcessInstantActivation($merchantDetails) === true)
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

            case BusinessType::PROPRIETORSHIP:
                $allowedPOIStatus = [POIStatus::VERIFIED, POIStatus::FAILED, POIStatus::NOT_MATCHED];

                return (in_array($merchantDetails->getPoiVerificationStatus(), $allowedPOIStatus) === true);

            default :

                return (new FormSubmissionValidStatusesMap())->isDocumentsStatusValidForFormSubmission(
                        $merchantDetails,
                        FormSubmissionValidStatusesMap::DOCUMENT_LIST_FOR_L1
                    ) === true;
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

            return;
        }

        $fields = [Detail\Entity::PROMOTER_PAN, Detail\Entity::PROMOTER_PAN_NAME];

        if ($this->checkFieldsUpdation($fields, $input, $merchant->getId()) === false)
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

        $fields = [Detail\Entity::COMPANY_PAN, Detail\Entity::BUSINESS_NAME];

        if ($this->checkFieldsUpdation($fields, $input, $merchant->getId()) === false)
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

        $merchantDetails->edit($input, 'patchMerchantDetails');

        $this->autoUpdateMerchantCategoryDetailsIfApplicable($merchantDetails, $merchant);

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

        $this->autoUpdateMerchantCategoryDetailsIfApplicable($merchantDetails, $merchant);

        $this->repo->saveOrFail($merchantDetails);

        $this->updateLegalEntity($input, $merchant);

        // Sync few input fields to merchant entity
        (new Merchant\Core)->syncMerchantEntityFields($merchant, $input);

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

        //
        // Currently only for unregistered business we save documents in new table(Merchant documents) for
        // other business type we still save document in merchant detail table and sync both tables .
        //
        if ($merchantDetails->isUnregisteredBusiness() === false)
        {
            $merchantDetails->fill($merchantDetailsParams);

            $this->repo->saveOrFail($merchantDetails);
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
        else
        {
            $org = $merchant->org->toArray();

            $org['hostname'] = $merchant->org->getPrimaryHostName();

            $data = $merchantDetails->toArray();

            $data[Constants::IS_WHITELISTED_ACTIVATION] = $merchantDetails->getActivationFlow() === ActivationFlow::WHITELIST;

            $notifyMerchantMail = new NotifyMerchant($data, $org);

            Mail::queue($notifyMerchantMail);
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

                //
                // For Older merchant who are still in old flow ,
                // kyc clarification will be empty in this case form should not get unlocked
                //
                if (empty($merchantDetails->getKycClarificationReasons()) === false)
                {
                    $merchantDetails->setLocked(false);

                    $this->sendNeedsClarificationEmail($merchant);
                }

                $this->deactivateIfFlawedWebsite($merchant, $merchantDetails->getIssueFields());
            }

            $this->repo->saveOrFail($merchantDetails);

            $this->repo->saveOrFail($merchant);

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

        [$validationFields, $validationDocumentFields, $validationOptionalFields] = ValidationFields::getValidationFields($merchantDetails);

        if (self::shouldSkipBankAccountRegistration() === true)
        {
            $validationFields = array_diff($validationFields, RequiredFields::BANK_ACCOUNT_FIELDS);
        }

        $merchant = $merchantDetails->merchant;

        if ($merchant->isLinkedAccount() === true)
        {
            $validationFields         = RequiredFields::MARKETPLACE_ACCOUNT_FIELDS;
            $validationDocumentFields = [];
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

        return [$validationFields, $validationDocumentFields, $validationOptionalFields];
    }

    public function createResponse(Entity $merchantDetails): array
    {
        $merchantDetailsArr = $merchantDetails->toArray();

        $response = $merchantDetails->toArrayPublic();

        $requiredFields = [];

        [$validationFields, $validationDocumentFields, $validationOptionalFields] = $this->getValidationFields($merchantDetails);

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

        $totalFields = count($validationFields) + count($validationDocumentFields);

        $documentsResponse = (new Document\Core())->documentResponse($merchant);

        $response['documents'] = $documentsResponse;

        foreach ($validationFields as $key)
        {
            //
            // Add the key to the list of the required fields if:
            //- key is not present in  merchant detail
            //- and if the key that needs to be validated is not present in the merchant Document array
            //
            if (($this->isKeyNotInMerchantDetail($key, $merchantDetailsArr) === true) and
                (array_key_exists($key, $documentsResponse) === false))
            {
                $requiredFields[] = $key;
            }
        }

        $this->calculateRequiredDocumentFields(
            $validationDocumentFields,
            $documentsResponse,
            $requiredFields);

        $isAutoKycDocumentsVerificationStatusAllowed = (new FormSubmissionValidStatusesMap())->isDocumentsStatusValidForFormSubmission(
            $merchantDetails,
            FormSubmissionValidStatusesMap::DOCUMENT_LIST_L2);

        if (count($requiredFields) > 0 or
            $isAutoKycDocumentsVerificationStatusAllowed === false)
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

        $response[Merchant\Entity::ACTIVATED]                   = (int) $merchant->isActivated();
        $response[Merchant\Entity::LIVE]                        = $merchant->isLive();
        $response[Merchant\Entity::INTERNATIONAL]               = $merchant->isInternational();

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

        return $response;
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
        if ((new Merchant\Core())->isUnRegisteredOnBoardingEnabled($merchant, $merchantDetails->isUnregisteredBusiness()) === false)
        {
            return;
        }

        // adding this check for qa automation
        if ($this->env === 'func' and $merchantDetails->getBankDetailsVerificationStatus() !== null)
        {
            return;
        }

        // no penny testing for linked accounts
        if ($merchant->isLinkedAccount() === true)
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

        if ($this->shouldSkipBankAccountRegistration() == true)
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
     * 1) poaVerificationStatus is Verified and
     * 2) bankDetailsVerificationStatus is Verified and
     * 3) poiVerificationStatus is Verified and
     * 4) Unregistered on-boarding is enabled for merchant and
     *
     * Else change set activation status to under review
     *
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     *
     * @return string
     */
    public function getApplicableActivationStatus(Entity $merchantDetails, Merchant\Entity $merchant)
    {
        if (($merchantDetails->isPoaVerified() === true) and
            ($merchantDetails->isBankDetailStatusVerified() === true) and
            ($merchantDetails->isPoiVerified() === true) and
            ((new Merchant\Core)->isUnRegisteredOnBoardingEnabled($merchant, $merchantDetails->isUnregisteredBusiness()) === true))
        {
            return Status::ACTIVATED;
        }

        return Status::UNDER_REVIEW;
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
                $requiredFields[] = $requiredDocumentField;
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

    public function updateInternationalActivationFlow(Merchant\Entity $merchant, $international)
    {
        $merchantDetail = $merchant->merchantDetail;

        $internationalActivationFlow = ActivationFlow::BLACKLIST;

        if ($international === 1)
        {
            $internationalActivationFlow = BusinessSubCategoryMetaData::getFeatureValueUsingCategoryOrSubcategory(
                BusinessSubCategoryMetaData::INTERNATIONAL_ACTIVATION,
                $merchantDetail->getBusinessCategory(),
                $merchantDetail->getBusinessSubcategory(),
                ActivationFlow::BLACKLIST);
        }

        $merchantDetail->setInternationalActivationFlow($internationalActivationFlow);

        $this->repo->saveOrFail($merchantDetail);
    }


    /**
     * @param Merchant\Entity      $merchant
     * @param Merchant\Entity|null $partner
     * @param array                $activationFlowTypes
     */
    protected function updateActivationFlows(Merchant\Entity $merchant,
                                             Merchant\Entity $partner = null,
                                             array $activationFlowTypes = Detail\Constants::ACTIVATION_FLOWS): void
    {
        foreach ($activationFlowTypes as $activationFlowType)
        {
            switch ($activationFlowType)
            {
                case Detail\Constants::ACTIVATION:

                    $this->autoUpdateActivationFlow($merchant, $partner);

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
                    $pennyTesting->triggerPennyTesting($merchantDetail);
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
     * @param array  $fields
     * @param array  $input
     * @param string $merchantId
     *
     * @return bool
     */
    protected function checkFieldsUpdation(array $fields, array $input, string $merchantId)
    {
        $merchantDetails = $this->repo->merchant_detail->findByPublicId($merchantId);

        foreach ($fields as $field)
        {
            if ((isset($input[$field]) === true) and ($merchantDetails->getAttribute($field) !== $input[$field]))
            {
                return true;
            }
        }

        return false;
    }
}
