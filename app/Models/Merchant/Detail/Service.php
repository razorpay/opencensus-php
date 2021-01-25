<?php

namespace RZP\Models\Merchant\Detail;

use Throwable;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Admin;
use RZP\Models\Coupon;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Promotion;
use RZP\Models\Admin\Org;
use RZP\Constants\Timezone;
use RZP\Models\Promotion\Event;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Constants;
use RZP\Models\Merchant\Action as Action;
use RZP\Models\Merchant\Document as Document;
use RZP\Models\Merchant\Referral as Referral;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Merchant\SlackActions as SlackActions;
use RZP\Models\Merchant\Document\FileHandler\Factory;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Models\Merchant\Document\Core as DocumentCore;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApp;
use RZP\Models\Merchant\Detail\RejectionReasons as RejectionReasons;

class Service extends Base\Service
{
    use NotifyTrait;

    protected $core;

    protected $methodsCore;

    protected $validator;

    protected $accountCore;

    public function __construct(Core $core = null, Validator  $validator = null, Account\Core $accountCore = null)
    {
        parent::__construct();

        $this->core = $core ?? new Core();

        $this->validator = $validator ?? new Validator();

        $this->accountCore = $accountCore ?? new Account\Core();

    }

    public function fetchMerchantDetails()
    {
        $merchantDetails = $this->core->getMerchantDetails($this->merchant);

        return $this->core->createResponse($merchantDetails);
    }

    public function getMerchantMethodsCore()
    {
        return new Merchant\Methods\Core();
    }

    public function getDisabledBanks()
    {
        $methods = $this->getMerchantMethodsCore()->getEnabledAndDisabledBanks($this->merchant);

        return $methods['disabled'];
    }

    public function fetchActivationFiles(string $id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchantDetails = (new Core)->getMerchantDetails($merchant);

        $signedUrls = [];

        $fileFields = $this->getFileFields($merchant);

        foreach ($fileFields as $key => $value)
        {
            if (isset($merchantDetails[$key]) === true)
            {
                $signedUrls[$value] = $this->getSignedUrl($merchantDetails[$key], $id);
            }
        }

        return ['files' => $signedUrls];
    }

    public function saveMerchantDetailForPreSignUp(array $input)
    {
        $response = $this->saveMerchantDetails($input, $this->merchant);

        $this->app->hubspot->trackPreSignupEvent($input, $this->merchant);

        (new User\Service)->addUtmParameters($input);

        $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_FINISH_SIGNUP_SUCCESS, $this->merchant, null, $input);

        // Putting in a try catch block so that any error here does not disrupt
        // the main signup flow. This will be removed once X flow simplifies the payload for salesforce
        try
        {
            $this->app->salesforce->sendPreSignupDetails($input, $this->merchant);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SALESFORCE_FAILED_TO_DISPATCH_JOB);
        }

        return $response;
    }

    public function saveMerchantDetailsForActivation(array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($this->merchant->getMerchantId());

        $response = $this->saveMerchantDetails($input, $merchant);

        $this->app['terminals_service']->reRequestInternalInstrumentRequestsOnActivationFormSubmit($merchant->getId());

        $this->app->hubspot->trackL2ContactProperties($input, $this->merchant);

        $this->app['diag']->trackOnboardingEvent(EventCode::KYC_SAVE_MODIFICATIONS_SUCCESS, $this->merchant, null, $input);

        return $response;
    }

    public function saveMerchantDetails(array $input, Merchant\Entity $merchant)
    {
        //
        // When a linked account is created, mainly, 2 functions are executed -
        // 1. createSubMerchant
        // 2. saveMerchantDetails
        //
        // The first function creates a merchant entity and other supporting
        // entities like MerchantDetail, ScheduleTask, Method, etc. It also creates
        // a BankAccount entity in the Test database with dummy values so that the
        // merchant can start the integration using the test mode immediately.
        //
        // The second function accepts the actual bank account details of the merchant
        // and runs the createOrChangeBankAccount function call. This function creates
        // or updates the bankAccount entity in the database corresponding to the mode
        // that is extracted from the basic auth key used. Hence, if the key used
        // corresponds to live mode, a BankAccount entity will be created in the live
        // mode, but if it is used in the test mode, the entity that is already created
        // with the dummy data will be updated with the actual data and no entity will
        // be created in the Live mode,
        //
        // Hence, forcing the input mode to be live mode here, if not already.
        //
        $liveMode = $this->auth->getLiveConnection();

        $this->core()->setModeAndDefaultConnection($liveMode);

        $originProduct = $this->auth->getRequestOriginProduct();

        return $this->core()->saveMerchantDetails($input, $merchant, $originProduct);
    }

    public function saveInstantActivationDetails(array $input): array
    {
        $liveMode = $this->auth->getLiveConnection();

        $this->core()->setModeAndDefaultConnection($liveMode);

        $merchant = $this->repo->merchant->findOrFailPublic($this->merchant->getMerchantId());

        return $this->core()->saveInstantActivationDetails($input, $merchant);
    }

    /**
     * This function is used to patch merchant details fields
     *
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function patchMerchantDetails(array $input): array
    {
        //
        // Merchant needs to be set using X-Razorpay-account header.
        // Setting Merchant in header validates admin access to
        // that merchant in admin access middleware.
        //
        if (empty($this->merchant) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_CONTEXT_NOT_SET);
        }

        $merchantDetails = $this->core()->patchMerchantDetails($this->merchant, $input);

        return $merchantDetails->toArrayPublic();
    }

    /**
     * Bulk edits merchant attributes against given CSV input.
     * CSV file contains header as id, {attribute-name-1}, {attribute-name-2}, where attribute-name is name of attribute to be updated.
     * Note: Specific error handling and strict validation is being SKIPPED here, This is internal route and should be run with supervision.
     * @param  array $input
     * @return array
     */
    public function bulkEditMerchantAttributes(array $input): array
    {
        (new Validator)->validateInput(Validator::BULK_EDIT, $input);

        // Reads CSV content as associate array in $rows as [merchant id => <>, attribute-name => <>]
        $file          = $input[Entity::FILE]->getRealPath();
        $lines         = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $heading       = str_getcsv(array_shift($lines));
        $rows          = [];

        foreach ($lines as $line)
        {
            $rows[] = array_combine($heading, str_getcsv($line));
        }

        $total  = 0;
        $failed = 0;
        $failedIds = [];
        // Iteratively call core's edit method on each row
        foreach ($rows as $row)
        {
            ++$total;

            $tracePayload = compact('row');

            $this->trace->info(TraceCode::MERCHANT_BULK_EDIT_INPUT, $tracePayload);

            $merchantId = array_pull($row, 'id');

            // Normalizes attribute values - if it is read as null, converts to php's null
            foreach ($row as $k => & $v)
            {
                if (strtolower($v) === "null")
                {
                    $v = null;
                }
            }

            try
            {
                $this->editMerchantDetails($merchantId, $row);
            }
            catch (Throwable $e)
            {
                $this->trace->traceException($e, null, null, $tracePayload);

                ++$failed;

                $failedIds[] = $merchantId;
            }
        }

        return compact('total', 'failed', 'failedIds');
    }

    public function uploadActivationFileAdmin(string $merchantId, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        // Do not check if the activation form is locked
        return $this->uploadActivationFile($merchant, $input, false);
    }

    public function uploadActivationFileMerchant(array $input)
    {
        return $this->uploadActivationFile($this->merchant, $input);
    }

    /**
     * This is deprecated , You should use route merchant/documents/upload
     *
     * Upload the file passed in $input for $merchant
     *
     * @param  Merchant\Entity $merchant          Merchant Entity
     * @param  array           $input
     *                                            Input with the file
     * @param  boolean         $validateLock      If true, blocks edits if the form is locked. Can be set to false
     *                                            to bypass locked forms
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function uploadActivationFile(Merchant\Entity $merchant,
                                         array $input,
                                         bool $validateLock = true)
    {

        $this->validator->validateDocumentUpload($input);

        $core = new Core;

        $merchantDetails = $core->getMerchantDetails($merchant, $input);

        if ($validateLock === true)
        {
            $merchantDetails->getValidator()->validateIsNotLocked();
        }

        $fileAttributes = $this->storeActivationFile($merchantDetails, $input);

        $response = $this->repo->transaction(function() use ($merchant, $merchantDetails, $input, $fileAttributes) {

            $this->handleMerchantDocument($input, $merchantDetails, $merchant, $fileAttributes);

            // Previous $response would become stale while simultaneous uploads. So prepare fresh response.
            $response = (new Core)->createResponse($merchantDetails);

            return $response;
        }
        );

        $this->sendDocumentUploadEvent($merchant, $input);

        return $response;
    }


    /**
     * @param array           $input
     * @param Entity          $merchantDetails
     * @param Merchant\Entity $merchant
     *
     * @param                 $fileAttributes
     *
     * @throws Exception\BadRequestException
     */
    public function handleMerchantDocument(array &$input, Entity $merchantDetails, Merchant\Entity $merchant, $fileAttributes)
    {
        $this->deleteExistingDocuments($input, $merchantDetails);

        $merchantDetails->fill($input);

        //
        // for backward compatibility we are storing file in both merchant detail and merchant_document table
        //
        (new DocumentCore)->storeInMerchantDocument($merchant, $merchant, $fileAttributes);

        $this->storeInMerchantDetails($merchantDetails, $fileAttributes);
    }


    /**
     * @param Entity $merchantDetails
     * @param array  $fileAttributes
     */
    function storeInMerchantDetails(Entity $merchantDetails, array $fileAttributes)
    {
        $core = new Core;

        $input = array_map(function(array $fileAttribute) {

            return $fileAttribute[Document\Constants::FILE_ID];

        }, $fileAttributes);

        $merchantDetails->fill($input);

        $response = $core->createResponse($merchantDetails);

        $merchantDetails->setActivationProgress($response['verification']['activation_progress']);

        $this->repo->saveOrFail($merchantDetails);
    }

    /**
     * Deletes document from merchant document table .
     *
     * In old api we are storing documents in merchant detail table and in this table document re-upload will replace existing values
     * After moving documents to merchant document table we need to explicitly delete document from merchant document table
     * as in merchant documents we insert a new row for each document
     *
     *
     * @param $input
     * @param $merchantDetails
     */
    public function deleteExistingDocuments($input, $merchantDetails): void
    {
        $previousFileStoreIds = [];

        //
        //find the previous document uploaded with same document type and delete them from Merchant_documents table
        //
        foreach ($input as $key => $value)
        {
            $fileStoreId = $merchantDetails->getAttribute($key);

            if (isset($fileStoreId) === true)
            {
                $previousFileStoreIds[] = $fileStoreId;
            }
        }

        (new DocumentCore)->deleteDocuments($previousFileStoreIds);
    }

    /**
     * @param Base\PublicEntity $publicEntity
     *
     * @param array             $input
     * @param string|null       $documentSource
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function storeActivationFile(
        Base\PublicEntity $publicEntity,
        array $input,
        string $documentSource = null)
    {
        $params = [];

        $merchant = $publicEntity->merchant;

        foreach ($input as $type => $file)
        {
            (new Validator)->validateFile($file);

            $fileName = $this->getFileName($file, $merchant->getId());

            $documentUploadInput = [
                Document\Constants::TYPE      => $type,
                Document\Constants::FILE      => $file,
                Document\Constants::FILE_NAME => $fileName,
                Document\Constants::ENTITY    => $publicEntity,
                Document\Constants::MERCHANT  => $merchant,
            ];

            $documentSource = $documentSource ?? Factory::getApplicableSource($merchant->getId());

            Document\Source::validateSource($documentSource);

            $fileHandler = Factory::getFileStoreHandler($documentSource);

            $params[$type] = $fileHandler->uploadFile($documentUploadInput);
        }

        return $params;
    }

    public function editMerchantDetails($id, array $input)
    {
        $slackAction = null;

        if (isset($input['locked']) === true or
            isset($input['comment']) === true)
        {
            if (isset($input['locked']) === true)
            {
                $action = ($input['locked'] === true) ? Action::LOCK : Action::UNLOCK;

                $slackAction = ($input['locked'] === true) ? SlackActions::LOCK : SlackActions::UNLOCK;
            }
            else
            {
                $action = Action::EDIT_COMMENT;
            }

            $admin = $this->app['basicauth']->getAdmin();

            $admin->hasMerchantActionPermissionOrFail($action);
        }

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchantDetailCore = $this->core;

        $merchantDetails = $merchantDetailCore->editMerchantDetailFields($merchant, $input);

        if (isset($slackAction) === true)
        {
            $this->logActionToSlack($merchant, $slackAction);
        }

        return $merchantDetailCore->createResponse($merchantDetails);
    }

    public function editMerchantDetailsByPartner($merchantId, array $input)
    {
        $partnerMerchant = $this->app['basicauth']->getMerchant();

        $this->accountCore->validatePartnerAccess($partnerMerchant, $merchantId);

        Account\Entity::verifyIdAndStripSign($merchantId);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->core()->markSubmittedAndLock($merchant->merchantDetail);

        $merchantDetails = $this->core()->editMerchantDetailFields($merchant, $input);

        return $merchantDetails->toArrayPublic();
    }

    private function getFileFields(Merchant\Entity $merchant) : array
    {
        return ($merchant->isLinkedAccount() === true) ? Constants::UPLOAD_KEYS_ACCOUNT : Constants::UPLOAD_KEYS;
    }

    /**
     * @param string $fileStoreId
     * @param string $merchantId
     *
     * @param string $source
     *
     * @return string|null
     * @throws Exception\LogicException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function getSignedUrl(string $fileStoreId, string $merchantId, string $source = null)
    {
        if ($fileStoreId === DEConstants::DUMMY_ACTIVATION_FILE)
        {
            return null;
        }

        $source = $source ?? Factory::getApplicableSource($merchantId, $fileStoreId);

        Document\Source::validateSource($source);

        $fileHandler = Document\FileHandler\Factory::getFileStoreHandler($source);

        return $fileHandler->getSignedUrl($fileStoreId, $merchantId);
    }

    private function getFieldsToStepMap() : array
    {
        // Fetching Action Form details schema based on account type.
        $isLinkedAccount = $this->merchant->isLinkedAccount();

        if ($isLinkedAccount === true)
        {
            return Merchant\Constants::STEP_MAP_ACCOUNT;
        }
        else
        {
            return Merchant\Constants::STEP_MAP;
        }
    }

    private function getStepsList() : array
    {
        $stepsList = array_values($this->getFieldsToStepMap());

        return array_values(array_unique($stepsList));
    }

    private function calculateSteps($merchantDetails) : array
    {
        $stepFinished = [];

        $stepMap = $this->getFieldsToStepMap();

        $requiredFields = $merchantDetails['verification']['required_fields'] ?? [];

        foreach ($requiredFields as $key)
        {
            if (isset($stepMap[$key]) === true)
            {
                $stepFinished[] = $stepMap[$key];
            }
        }

        return $stepFinished;
    }

    /**
     * This function is used for archiving merchant activation form
     * @param string $merchantId
     * @param array $input
     *
     * @return array
     */
    public function updateActivationArchive(string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantDetails = $merchant->merchantDetail;

        $admin = $this->app['basicauth']->getAdmin();

        $merchantDetails = $this->core->updateActivationArchive($merchantDetails, $input, $admin);

        return $merchantDetails->toArrayPublic();
    }

    /**
     * This function is used for updating merchant activation status
     * @param string $merchantId
     * @param array $input
     *
     * @return array
     */
    public function updateActivationStatus(string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $admin = $this->app['basicauth']->getAdmin();

        $merchantDetails = (new Core)->updateActivationStatus($merchant, $input, $admin);

        return $merchantDetails->toArrayPublic();
    }

    public function updateActivationStatusByPartner($merchantId, array $input): array
    {
        $partnerMerchant = $this->app['basicauth']->getMerchant();

        (new Account\Core)->validatePartnerAccess($partnerMerchant, $merchantId);

        Account\Entity::verifyIdAndStripSign($merchantId);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantDetails = $this->core()->updateActivationStatus($merchant, $input, $partnerMerchant);

        return $merchantDetails->toArrayPublic();
    }

    /**
     * This function is used for getting the activation status change log of a merchant
     * @param string $merchantId
     *
     * @return array
     */
    public function getActivationStatusChangeLog(string $merchantId): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $activationStatusChangeLog = (new Merchant\Core)->getActivationStatusChangeLog($merchant);

        return $activationStatusChangeLog->toArrayPublic();
    }

    /**
     * This function is used for updating website details of a merchant
     * @param array $input
     *
     * @return array
     */
    public function updateWebsiteDetails(array $input): array
    {
        $merchantDetails = $this->merchant->merchantDetail;

        $response = (new Core)->updateWebsiteDetails($merchantDetails, $input);

        return $response;
    }

    /**
     * This function is used for getting business categories subcategories list
     * sub category meta fields will be dependent on auth
     *
     * @return array
     */
    public function getBusinessCategories(): array
    {
        $businessCategoriesMap = BusinessCategory::SUBCATEGORY_MAP;
        $businessCategories    = [];

        foreach ($businessCategoriesMap as $businessCategory => $subCategories)
        {
            $businessCategories[$businessCategory] = [];
            $subCategoriesMetaData                 = [];

            foreach ($subCategories as $subCategory)
            {
                $subcategoryMetaDataFields = BusinessSubCategoryMetaData::SUB_CATEGORY_METADATA[$subCategory];

                if ($this->isSubcategoryToBeShownOnDashboard($subcategoryMetaDataFields) === true)
                {
                    $subCategoriesMetaData[$subCategory] = $this->getSubCategoryMetaDataFields($subcategoryMetaDataFields);
                }

            }
            $businessCategories[$businessCategory][BusinessCategory::DESCRIPTION]   = BusinessCategory::DESCRIPTIONS[$businessCategory];
            $businessCategories[$businessCategory][BusinessCategory::SUBCATEGORIES] = $subCategoriesMetaData;
        }

        return $businessCategories;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function getBusinessDetails(array $input): array
    {
        $businessDetails = (new Core)->getBusinessDetails($input);

        return $businessDetails;
    }


    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\BaseException
     */
    public function getCompanySearchList(array $input): array
    {
        $companySearchList = (new Core)->getCompanySearchList($input);

        return $companySearchList;
    }

    /**
     * This function is used for getting needs clarification reasons for fields
     *
     * @return array
     */
    public function getNeedsClarificationReasons()
    {
        $needsClarificationReasonsMap = NeedsClarificationMetaData::REASON_MAPPING;
        $reasonDetails                = NeedsClarificationReasonsList::REASON_DETAILS;
        $response                     = [];

        foreach ($needsClarificationReasonsMap as $field => $reasons)
        {
            $reasonList = [];

            foreach ($reasons as $reason)
            {
                $reasonList[$reason] = $reasonDetails[$reason];
            }

            $response[$field] = [NeedsClarificationMetaData::REASONS => $reasonList];
        }

        return $response;
    }

    /**
     * returns subcategories meta data as per auth
     * for admin all meta data fields(description, category, category2, activation category) will be returned
     * for other then admin description and category2 will be returned
     *
     * @param array $subcategoryMetaDataFields
     *
     * @return array
     */
    private function getSubCategoryMetaDataFields(array $subcategoryMetaDataFields)
    {
        if ($this->auth->isAdminAuth() === true)
        {
            return $subcategoryMetaDataFields;
        }

        return array_only($subcategoryMetaDataFields, BusinessSubCategoryMetaData::NORMAL_AUTH_FIELDS);
    }

    /**
     * @param $subcategoryMetaDataFields
     *
     * @return bool
     */
    private function isSubcategoryToBeShownOnDashboard($subcategoryMetaDataFields): bool
    {
        if ($this->auth->isAdminAuth() === true)
        {
            return true;
        }

        return (isset($subcategoryMetaDataFields[BusinessSubCategoryMetaData::EXISTING_OR_NEW_SUBCATEGORY]) === true) and
               ($subcategoryMetaDataFields[BusinessSubCategoryMetaData::EXISTING_OR_NEW_SUBCATEGORY] === BusinessSubCategoryMetaData::EXISTING_SUBCATEGORY);
    }

    public function getRejectionReasons()
    {
        return RejectionReasons::REJECTION_REASONS_MAPPING;
    }

    public function getMerchantDetailsForAdmin() : array
    {
        // Formatting the data as required by the controller.
        $merchantDetails = $this->fetchMerchantDetails();

        // Finished steps will be calculated based on required fields.
        $this->calculateFinishedSteps($merchantDetails);

        return $merchantDetails;
    }

    private function calculateFinishedSteps(array & $merchantDetails)
    {
        // Get steps for the current merchant.
        $steps = $this->getStepsList();

        if($merchantDetails['can_submit'] === true)
        {
            $merchantDetails['steps_finished'] = $steps;
        }
        else
        {
            // By checking merchant details unfinished steps will be calculated.
            $unfinishedSteps = $this->calculateSteps($merchantDetails);

            if(count($unfinishedSteps) !== 0)
            {
                $unfinishedSteps = array_unique($unfinishedSteps);

                $finishedSteps = array_values(array_diff($steps, $unfinishedSteps));

                $merchantDetails['steps_finished'] = $finishedSteps;
            }
        }
    }

    /**
     * Will get pre signup details from merchant details.
     *
     * @return array
     */
    public function getPreSignupDetails(): array
    {
        // Referrer merchant doesn't need to complete presignup details.
        $referrerMerchant = $this->merchant->getReferrer();

        $presignupDetails = [];

        // Referrer Merchant check for presignup details.
        if ((empty($referrerMerchant) === true) or
            (Merchant\Entity::verifyUniqueId($referrerMerchant, false) === 0))
        {
            $merchantDetails = $this->fetchMerchantDetails();

            $presignupFields = Constants::PRE_SIGNUP_FIELDS;

            foreach ($presignupFields as $key)
            {
                if (empty($merchantDetails[$key]) === false)
                {
                    $presignupDetails[$key] = (string) $merchantDetails[$key];
                }
                else
                {
                    $presignupDetails[$key] = null;
                }
            }
        }

        return $presignupDetails;
    }

    /**
     * Edit pre signup details.
     *
     * @param array $input
     *
     * @return array
     * @throws Throwable
     */
    public function editPreSignupDetails(array $input) : array
    {
        (new Validator)->validateInput('pre_signup', $input);

        $this->trace->count(Merchant\Metric::PRE_EDIT_SIGNUP_TOTAL);

        $merchant = $this->app['basicauth']->getMerchant();

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $input)
        {
            $this->applyCoupon($input);

            $originProduct = $this->auth->getRequestOriginProduct();

            (new Promotion\Core)->applyPromotion(
                                                $merchant,
                                                $originProduct,
                                                Event\Constants::SIGN_UP);

            $this->handlePreSignUpOptionalFields( $input);

            $this->applyReferralPartner($input);

            $this->saveMerchantDetailForPreSignUp($input);

            if (empty($input[Entity::BUSINESS_NAME]) === false)
            {
                (new Merchant\Core)->editPreSignupFields($this->merchant, $input);

                // Save User Information of contact name nad contact Email.

                $originProduct = $this->auth->getRequestOriginProduct();

                $user = $this->merchant->primaryOwner($originProduct);

                $userEditData['contact_mobile'] = $input['contact_mobile'] ?? null;
                $userEditData['name']           = $input['contact_name'] ?? null;

                $userEditData = array_filter($userEditData);

                (new User\Validator)->validateInput('pre_signup', $userEditData);

                (new User\Service)->edit($user->id, $userEditData);

                //Creating virtual account for a merchant in test mode.
                //Handling within try catch to avoid any breaking of pre sign up flow.
                try
                {
                    (new Merchant\Activate)->activateBusinessBankingIfApplicable($this->merchant);
                }
                catch (Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::BANKING_ACCOUNT_CREATION_TEST_MODE_FAILED);
                }
            }
        });

        return $this->getPreSignupDetails();
    }

    /**
     * As part of experiment we want to remove company name in pre_Signup flow , so using contact name as company name
     * This will be handled in L1 as we already take company name in L1 form
     *
     * @param array $input
     */
    private function handlePreSignUpOptionalFields(array & $input)
    {

        if (empty($input[Entity::BUSINESS_NAME]) === true and
            empty($input[Entity::CONTACT_NAME]) === false)
        {
            $input[Entity::BUSINESS_NAME] = $input[Entity::CONTACT_NAME];
        }
    }

    /**
     * Checks whether coupon_code is present in input and applies
     *
     * @param array $input
     *
     * @throws Throwable
     */
    private function applyCoupon(array &$input)
    {
        if (empty($input[Entity::COUPON_CODE]) === true)
        {
            return;
        }

        $this->trace->info(TraceCode::COUPON_APPLY_REQUEST, $input);

        $merchant = $this->app['basicauth']->getMerchant();

        $couponInput = [
            Coupon\Entity::CODE => $input[Entity::COUPON_CODE],
        ];

        (new Coupon\Core)->apply($merchant, $couponInput);

        $this->trace->count(Merchant\Metric::SIGNUP_COUPON_TOTAL);

        unset($input[Entity::COUPON_CODE]);
    }

    /**
     * @param array $input
     *
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     * @throws Throwable
     */
    private function applyReferralPartner(array &$input)
    {
        if ((isset($input[Entity::REFERRAL_CODE]) === false) or (empty($input[Entity::REFERRAL_CODE]) === true))
        {
            return;
        }

        $refCode = $input[Entity::REFERRAL_CODE];

        $this->trace->info(TraceCode::MERCHANT_REFERRAL_APPLY_REQUEST, $input);

        $subMerchant = $this->app['basicauth']->getMerchant();

        $referral = (new Referral\Core)->fetchReferralByReferralCode($refCode);

        if (empty($referral) === false)
        {
            $partnerId = $referral[Referral\Entity::MERCHANT_ID];

            $partner = $this->repo->merchant->findOrFailPublic($partnerId);

            $merchantCore = new Merchant\Core;

            $merchantCore->createPartnerSubmerchantAccessMap($partner, $subMerchant, MerchantApp::REFERRED);

            $linkedAccount = false;

            // update merchant pricing plan to the one specified by partner in partner config if applicable
            $merchantCore->assignSubMerchantPricingPlan($partner, $subMerchant, $linkedAccount, MerchantApp::REFERRED);

            $data = [
                'status'       => 'success',
                'merchant_id'  => $subMerchant->getId(),
                'partner_id'   => $partnerId,
                'source'       => PartnerConstants::REFERRAL
            ];

            $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIP_SUBMERCHANT_SIGNUP,
                $partner, null,
                $data);
        }

        unset($input[Entity::REFERRAL_CODE]);
    }

    /**
     * This function is used to get zapier data for activation
     *
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    public function getActivationZapierData(Merchant\Entity $merchant): array
    {
        $date = Carbon::createFromTimeStamp(time(), Timezone::IST)->format('j/m/Y');

        $merchantDetails = $merchant->merchantDetail;

        return [
            Constants::DATE          => $date,
            Merchant\Entity::ID      => $merchant->id,
            Merchant\Entity::EMAIL   => $merchant->email,
            Merchant\Entity::NAME    => $merchant->name,
            Entity::CONTACT_NAME     => $merchantDetails->contact_name,
            Entity::BUSINESS_NAME    => $merchantDetails->business_name,
            Entity::BUSINESS_DBA     => $merchantDetails->business_dba,
            Entity::BUSINESS_WEBSITE => $merchantDetails->business_website,
            Constants::REF           => $merchant->referrer,
        ];
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function bulkAssignReviewer(array $input)
    {
        (new Validator)->validateInput('bulk_assign_reviewer', $input);

        $merchants  = $input[Entity::MERCHANTS];

        $reviewerId = $input[Entity::REVIEWER_ID];

        return (new Core)->bulkAssignReviewer($reviewerId, $merchants);
    }

    public function merchantsMtuUpdate(array $input)
    {
        (new Validator)->validateInput('merchant_mtu_update', $input);

        $merchants = $input[Entity::MERCHANTS];

        $value = $input[Entity::LIVE_TRANSACTION_DONE];

        return (new Core)->merchantsMtuUpdate($merchants, $value);
    }

    public function getMerchantActivationReviewers()
    {
        $orgId = $this->auth->getOrgId();

        Org\Entity::verifyIdAndStripSign($orgId);

        $permission = $this->repo
                            ->permission
                            ->findByOrgIdAndPermission($orgId, Admin\Permission\Name::EDIT_ACTIVATE_MERCHANT);

        if (empty($permission) === true)
        {
            throw new Exception\RuntimeException('Missing Permission');
        }

        $admins = [];

        foreach ($permission->roles as $role)
        {
            foreach ($role->admins as $roleAdmin)
            {
                $admins[] = $roleAdmin->toArrayPublic();
            }
        }

        return multidim_array_unique($admins, Admin\Admin\Entity::ID);
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array           $input
     */
    protected function sendDocumentUploadEvent(Merchant\Entity $merchant, array $input): void
    {
        $eventAttributes = [];

        foreach ($input as $key => $value)
        {
            if (Document\Type::isValid($key) === true)
            {
                $eventAttributes[Constants::DOCUMENT_TYPE] = $key;
                break;
            }
        }

        $this->app['diag']->trackOnboardingEvent(EventCode::KYC_UPLOAD_DOCUMENT_SUCCESS, $merchant, null, $eventAttributes);
    }

    /**
     * @param $merchantId
     * @param $input
     *
     * @return mixed
     * @throws Throwable
     */
    Public function putAdditionalWebsite($merchantId, $input)
    {
        $core = new Core();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantDetails = $core->getMerchantDetails($merchant);

        $response = $core->addAdditionalWebsiteDetails($merchantDetails, $input);

        return $response;
    }

    public function deleteAdditionalWebsites($merchantId, $input)
    {
        $core = new Core();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $merchantDetails = $core->getMerchantDetails($merchant);

        $response = $core->deleteAdditionalWebsites($merchantDetails, $input);

        return $response;
    }

    /**
     * Retuns file name to be used for storing files in file store
     *
     * @param        $file
     * @param string $merchantId
     *
     * @return string
     * @throws \Exception
     */
    public function getFileName($file, string $merchantId): string
    {
        //
        // Adding a prefix hash for filename to avoid overwrites to the same fileName on S3.
        //
        $partial = substr(bin2hex(random_bytes(6)), 0, 5);

        $fileIdentifier = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $fileName = 'api/' . $merchantId . '/' . $partial . '/' . $fileIdentifier;

        return $fileName;
    }

    public function retryPennyTestingCron()
    {
        (new Core())->retryPennyTestingCron();

        return ['success' => true];
    }

    /**
     * @param array  $input
     * @param string $verificationType
     *
     * @return array
     * @throws Throwable
     */
    public function verifyMerchantAttributes(array $input, string $verificationType): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($this->merchant->getMerchantId());

        return (new Core())->verifyMerchantAttributes($merchant, $verificationType, $input);
    }

    protected function applyPromotion(array $input, string $eventName)
    {
        $product = null;

        $isProductBanking = $this->auth->isProductBanking();

        if ($isProductBanking === false)
        {
            // we are not supporting normal promotions through this flow.
            // This to avoid the code to run into unknown issues.
            // If PG plans to use this flow for promotion, after modifying
            // the flow accordingly they can disable this check
            return;
        }
        else
        {
            $product = Merchant\Balance\Type::BANKING;
        }

        if ($this->mode === Mode::TEST)
        {
            // banking promotions will run only in live mode.
            return;
        }

        $this->trace->info(TraceCode::PROMOTION_APPLY_REQUEST, $input);

        $merchant = $this->auth->getMerchant();

        $merchantPromotion = (new Promotion\Core)->applyEventPromotionToMerchant(
                                        $eventName,
                                        $product,
                                        $merchant);

        return $merchantPromotion;
    }

    public function updateBusinessSuggestedAddressAndPin(array $input)
    {
        $merchantIds = array_keys($input);

        $merchantIdsChunk = array_chunk($merchantIds, 50, true);

        $updatedIds = [];

        $notUpdatedIds = [];

        foreach ($merchantIdsChunk as $mids)
        {
            $merchants = $this->repo->merchant->findManyWithRelations($mids, ['merchantDetail']);

            foreach ($merchants as $merchant)
            {
                $merchantId = $merchant->getId();

                $params = $input[$merchantId];

                (new Validator)->validateInput('edit', $params);

                try
                {
                    $suggestedPin = $params[Entity::BUSINESS_SUGGESTED_PIN];
                    $suggestedAddress = $params[Entity::BUSINESS_SUGGESTED_ADDRESS];

                    $merchantDetail = $merchant->merchantDetail;

                    $merchantDetail->setBusinessSuggestedPin($suggestedPin);
                    $merchantDetail->setBusinessSuggestedAddress($suggestedAddress);

                    $this->repo->merchant_detail->saveOrFail($merchantDetail);

                    $featureInput = [
                        'name'        => [Feature\Constants::SUGGESTED_ADDRESS_OPT_IN],
                        'entity_ids'  => [$merchantId],
                        'entity_type' => 'merchant',
                        'should_sync' => true,
                    ];

                    (new Feature\Service)->multiAssignFeature($featureInput);

                    $this->trace->info(TraceCode::MERCHANT_DETAIL_SUGGESTED_FIELDS_UPDATE_REQUEST,
                        [
                            'merchant_id'   => $merchantId,
                            Entity::BUSINESS_SUGGESTED_PIN      => $suggestedPin,
                            Entity::BUSINESS_SUGGESTED_ADDRESS  => $suggestedAddress,
                        ]);
                }
                catch (\Throwable $ex)
                {
                    $this->trace->error(TraceCode::MERCHANT_DETAIL_SUGGESTED_FIELDS_UPDATE_SKIPPED,
                        [
                            'merchant_id'        => $merchantId,
                            'reason'             => $ex->getMessage(),
                        ]);

                    $notUpdatedIds[] = $merchantId;

                    continue;
                }

                $updatedIds[] = $merchantId;
            }
        }

        $response = [
            'updated_ids'       => $updatedIds,
            'not_updated_ids'   => $notUpdatedIds
        ];

        $this->trace->info(TraceCode::MERCHANT_DETAIL_SUGGESTED_FIELDS_UPDATED, $response);

        return $response;
    }

    public function getGstinSelfServeStatus()
    {
        $status = DEConstants::GSTIN_SELF_SERVE_STATUS_NOT_STARTED;

        $data = $this->getGstinSelfServeInputFromCache();

        if ($data !== null)
        {
            $status = DEConstants::GSTIN_SELF_SERVE_STATUS_IN_PROGRESS;
        }

        return $status;
    }

    public function updateGstinSelfServe($input)
    {
        $this->validator->validateInput('gstin_self_serve', $input);

        $flow = $this->getGstinSelfServeFlow();

        $this->trace->info(TraceCode::GSTIN_UPDATE_SELF_SERVE_INITIATED, [
            'input' => $input,
            'flow'  => $flow,
        ]);

        $this->storeGstinSelfServeInput($input);

        switch ($flow)
        {
            case DEConstants::GSTIN_SELF_SERVE_V2_FLOW:
                $response = $this->updateGstinSelfServeV2($input);
                break;
            case DEConstants::GSTIN_SELF_SERVE_V1_FLOW:
            default:
                $response = $this->updateGstinSelfServeV1($input);
        }

        return $response;
    }

    protected function getGstinSelfServeFlow()
    {
        return DEConstants::GSTIN_SELF_SERVE_V1_FLOW;
    }

    protected function updateGstinSelfServeV1($input)
    {
        return $input;
    }

    protected function updateGstinSelfServeV2($input)
    {
        throw new Exception\ServerErrorException('not implemented', ErrorCode::SERVER_ERROR);
    }

    protected function storeGstinSelfServeInput($input)
    {
        $cacheKey = $this->getGstinSelfServeInputCacheKey();

        $this->app['cache']->put($cacheKey, $input, DEConstants::GSTIN_SELF_SERVE_INPUT_CACHE_TTL);
    }

    protected function getGstinSelfServeInputCacheKey()
    {
        return sprintf(DEConstants::GSTIN_SELF_SERVE_INPUT_CACHE_KEY_FORMAT, $this->merchant->getId());
    }

    protected function getGstinSelfServeInputFromCache()
    {
        $cacheKey = $this->getGstinSelfServeInputCacheKey();

        return $this->app['cache']->get($cacheKey);
    }
}
