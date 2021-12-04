<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Services\Segment\EventCode as SegmentEvent;
use Throwable;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Admin;
use RZP\Models\Coupon;
use RZP\Models\Partner;
use RZP\Diag\EventCode;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Promotion;
use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use RZP\Constants\IndianStates;
use RZP\Models\Promotion\Event;
use RZP\Models\Merchant\Account;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\Constants;
use Illuminate\Support\Facades\Mail;
use RZP\Error\PublicErrorDescription;
use RZP\Mail\Merchant as MerchantMail;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Action as Action;
use RZP\Models\Comment\Core as CommentCore;
use RZP\Models\Merchant\Document as Document;
use RZP\Models\Merchant\Referral as Referral;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Models\Workflow\Action as WorkflowAction;
use \RZP\Models\State\Entity as StateChangeEntity;
use RZP\Models\Workflow\Service as WorkflowService;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Core as BvsCore;
use RZP\Models\Merchant\SlackActions as SlackActions;
use RZP\Models\Merchant\Document\FileHandler\Factory;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Models\Workflow\Observer as WorkflowObserver;
use RZP\Models\Merchant\Document\Core as DocumentCore;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Workflow\Action\Differ\Core as DifferCore;
use RZP\Notifications\Dashboard\Events as DashboardEvents;
use RZP\Models\Merchant\Detail\BusinessSubcategory as Sub;
use RZP\Models\Workflow\Action\Core as WorkFlowActionCore;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BvsConstant;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Workflow\Action\Differ\Entity as DifferEntity;
use RZP\Jobs\Transfers\LinkedAccountBankVerificationStatusBackfill;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApp;
use RZP\Models\Merchant\Detail\RejectionReasons as RejectionReasons;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;
use RZP\Models\Workflow\Observer\Constants as WorkflowObserverConstants;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Notifications\Dashboard\Constants as DashboardNotificationConstants;

class Service extends Base\Service
{
    use NotifyTrait;

    const PAYMENT_DATA_NOT_FOUND_ON_DRUID = 'payment data not found on druid';

    protected $core;

    protected $methodsCore;

    protected $validator;

    protected $accountCore;

    protected $ba;

    public function __construct(Core $core = null, Validator  $validator = null, Account\Core $accountCore = null)
    {
        parent::__construct();

        $this->core = $core ?? new Core();

        $this->validator = $validator ?? new Validator();

        $this->accountCore = $accountCore ?? new Account\Core();

        $this->ba=$this->app['basicauth'];

    }

    public function fetchMerchantDetails()
    {
        $merchantDetails = $this->core->getMerchantDetails($this->merchant);

        $response = $this->core->createResponse($merchantDetails);

        $partnerActivation = (new Partner\Core())->getPartnerActivation($this->merchant);

        if (!empty($partnerActivation))
        {
            $response[DetailConstants::LOCK_COMMON_FIELDS] = $this->core->fetchCommonFieldsToBeLocked($partnerActivation);
        }

        return $response;
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

        $partnerIntent = false;

        if ($this->merchant !== null)
        {
            $partnerIntentResponse = (new Merchant\Service())->fetchPartnerIntent();

            $partnerIntent = $partnerIntentResponse[Constants::PARTNER_INTENT] ?? false;
        }

        $input[Constants::PARTNER_INTENT] = $partnerIntent;

        $this->trace->info(TraceCode::UTM_PARAMS, [
            'merchant'    => $this->merchant->getId(),
            'eventParams' => $input
        ]);

        $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_FINISH_SIGNUP_SUCCESS, $this->merchant, null, $input);

        unset($input[Constants::PARTNER_INTENT]);

        $attributeCore = new Merchant\Attribute\Core;

        try
        {
            $campaignTypeAttr = $attributeCore->fetch($this->merchant, Product::BANKING, Merchant\Attribute\Group::X_SIGNUP, Merchant\Attribute\Type::CAMPAIGN_TYPE);
        }
        catch (\Throwable $e)
        {
            $campaignTypeAttr = null;
        }

        if ($campaignTypeAttr !== null) {
            $input['x_onboarding_category'] = 'self_serve';
        }

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

    public function otpSendViaEmail($input)
    {
        $email = $input[User\Entity::EMAIL];

        $user = $this->user;

        try
        {
            $emailUser = $this->repo->user->findByEmail($email);
        }
        catch (Exception\BadRequestException $e)
        {
            $emailUser = null;
        }

        if ((empty($emailUser) === false) and
            ($user->getId() !== $emailUser->getId()) and
            (new User\Core())->checkIfEmailAlreadyExists($input[User\Entity::EMAIL]))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_EMAIL_ALREADY_EXISTS);
        }

        $userInput = [
            User\Entity::EMAIL  => $input[User\Entity::EMAIL]
        ];

        (new User\Service())->edit($this->user->getId(), $userInput);

        return (new User\Service())->sendOtpEmailVerification($this->merchant, $this->user, $input);
    }

    public function postSaveEmail($input)
    {
        (new Validator)->validateInput('activation_email', $input);

        $userInput = [
            User\Entity::EMAIL  => $input[Merchant\Entity::EMAIL]
        ];

        $merchantInput = [
            User\Entity::EMAIL => $input[Merchant\Entity::EMAIL]
        ];

        (new User\Service())->edit($this->user->getId(), $userInput);
        (new Merchant\Service())->edit($this->merchant->getId(),$merchantInput);

        $merchantDetails = $this->merchant->merchantDetail;

        $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails, $input) {
            $merchantDetails->setContactEmail($input[Merchant\Entity::EMAIL]);
            $this->repo->saveOrFail($merchantDetails);
        });
    }

    public function saveMerchantDetailsForActivation(array $input)
    {
        $activationFormMilestone = $input[Entity::ACTIVATION_FORM_MILESTONE] ?? null;

        $merchant = $this->repo->merchant->findOrFailPublic($this->merchant->getMerchantId());

        if ($activationFormMilestone === DEConstants::L1_SUBMISSION)
        {
            $response = $this->saveInstantActivationDetails($input);
        }
        else
        {
            $response = $this->saveMerchantDetails($input, $merchant);

            $this->app['terminals_service']->reRequestInternalInstrumentRequestsOnActivationFormSubmit($merchant->getId());

            $this->app->hubspot->trackL2ContactProperties($input, $this->merchant);

            $this->app['diag']->trackOnboardingEvent(EventCode::KYC_SAVE_MODIFICATIONS_SUCCESS, $this->merchant, null, $input);
        }

        $partnerActivation = (new Partner\Core())->getPartnerActivation($merchant);

        if (empty($partnerActivation) === false)
        {
            $response[DetailConstants::LOCK_COMMON_FIELDS] = $this->core->fetchCommonFieldsToBeLocked($partnerActivation);
        }

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

    public function uploadMerchant(array $input)
    {
        return (new Upload\Core)->uploadMerchant($input);
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
    public function getActivationStatusChangeLog(string $merchantId, $mode = null): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $activationStatusChangeLog = (new Merchant\Core)->getActivationStatusChangeLog($merchant, $mode);

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

    public function putBusinessWebsiteUpdatePostWorkflow(array $input)
    {
        $previousWebsite = $this->merchant->merchantDetail->getWebsite();

        $newUrl = $input[DetailConstants::URL_TYPE] === DEConstants::URL_TYPE_WEBSITE ?  $input[DetailConstants::BUSINESS_WEBSITE_MAIN_PAGE] : $input[DetailConstants::BUSINESS_APP_URL];

        $this->core()->updateBusinessWebsite($this->merchant , $newUrl);

        $event = $previousWebsite ? DashboardEvents::MERCHANT_BUSINESS_WEBSITE_UPDATE : DashboardEvents::MERCHANT_BUSINESS_WEBSITE_ADD;

        $args = [
            Constants::MERCHANT         => $this->merchant,
            DashboardEvents::EVENT      => $event,
            Constants::PARAMS           => [
                DashboardNotificationConstants::UPDATED_BUSINESS_WEBSITE   => $newUrl
            ]
        ];

        if($event === DashboardEvents::MERCHANT_BUSINESS_WEBSITE_UPDATE)
        {
            $args[Constants::PARAMS][DashboardNotificationConstants::PREVIOUS_BUSINESS_WEBSITE] = $previousWebsite;
        }

        (new DashboardNotificationHandler($args))->send();
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
     * @param array $input
     *
     * @return array
     * @throws Exception\BaseException
     */
    public function getGstInList(): array
    {
        $gstList = (new Core)->getGSTDetailsList();

        return $gstList;
    }

    /**
     * This function is used for getting needs clarification reasons for fields
     *
     * @return array
     */
    public function getNeedsClarificationReasons()
    {
        $needsClarificationReasonsMap = NeedsClarificationMetaData::REASON_MAPPING;
        $needsClarificationMerchantReasonsMap = NeedsClarificationMetaData::MERCHANT_REASON_MAPPING;
        $reasonDetails                = NeedsClarificationReasonsList::REASON_DETAILS;
        $response                     = [];

        if($this->ba->isAdminAuth()===false)
        {
            $needsClarificationReasonsMap=array_merge_recursive($needsClarificationReasonsMap,$needsClarificationMerchantReasonsMap);
        }
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

        (new Validator)->validateSignupViaChannel($input, $merchant);

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

                $userEditData[User\Entity::CONTACT_MOBILE] = $input['contact_mobile'] ?? null;
                $userEditData[User\Entity::NAME]           = $input['contact_name'] ?? null;
                $userEditData[User\Entity::EMAIL]          = $input['contact_email'] ?? null;

                $userEditData = array_filter($userEditData);

                (new User\Validator)->validateInput('pre_signup', $userEditData);

                /**
                 * If a user signs up on PG as unregistered business
                 * and switches to X, then during product switch,
                 * we don't create a record in merchant_users table for unregistered business.
                 * If this user fills presignup questions, then they get an exception since $user is null
                 * JIRA ticket: https://jira.corp.razorpay.com/browse/RX-8249
                 */
                if (empty($user) === false)
                {
                    (new User\Service)->edit($user->id, $userEditData);
                }

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

        $referralProduct = optional($referral)->getProduct() ?? Product::PRIMARY;

        $requestProduct = $this->auth->getRequestOriginProduct();

        if (empty($referral) === false and
            ($referralProduct === $requestProduct))
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
                'source'       => PartnerConstants::REFERRAL,
                'product_group'=> $referralProduct,
            ];

            $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIP_SUBMERCHANT_SIGNUP,
                $partner, null,
                $data);

            if ($partner->isFeatureEnabled(FeatureConstants::SKIP_SUBM_ONBOARDING_COMM) === true)
            {
                $this->app->hubspot->skipMerchantOnboardingComm($subMerchant->getEmail());
            }

            $this->app->hubspot->trackSubmerchantSignUp($partner->getEmail());

            $dimension = [
                'partner_type' => $partner->getPartnerType(),
                'source'       => PartnerConstants::REFERRAL
            ];

            $this->trace->count(PartnerMetric::SUBMERCHANT_CREATE_TOTAL, $dimension);
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

    public function postAppsflyerAttributionDetails($input)
    {
        $this->trace->info(TraceCode::APPSFLYER_ATTRIBUTION_DETAILS, $input);

        $eventName = $input['event_name'] ?? '';

        if(empty($eventName) or $eventName !== 'install')
        {
            return;
        }

        $appsflyerId = $input['appsflyer_id'] ?? '';

        if(empty($appsflyerId) === true)
        {
            $this->trace->info(TraceCode::APPSFLYER_ATTRIBUTION_DETAILS_ERROR, [
                'data'  => $input,
                'error' => 'missing appsflyer id'
            ]);

            return;
        }

        $userDeviceDetails = $this->repo->user_device_detail->fetchByAppsflyerId($appsflyerId);

        if(empty($userDeviceDetails) === true)
        {
            $this->trace->info(TraceCode::APPSFLYER_ATTRIBUTION_DETAILS_ERROR, [
                'data'  => $input,
                'error' => 'missing user device details'
            ]);

            return;
        }

        $this->app['rzp.mode'] = Mode::LIVE;
        $this->core()->setModeAndDefaultConnection(Mode::LIVE);

        $merchantId = $userDeviceDetails->getMerchantId();

        $segmentProperties = [];

        foreach ($input as $key => $value)
        {
            $segmentProperties["app_" . $key] = $value;
        }

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->app['segment-analytics']->pushIdentifyEvent($merchant, $segmentProperties);

        $this->app['segment-analytics']->buildRequestAndSend(true);
    }

    public function updateMerchantFraudType($input)
    {
        $merchantId = $input['merchant_id'];

        $fraudType = $input['fraud_type'];

        $response = [];

        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);

        if ($merchantDetail !== null)
        {
            if ($merchantDetail->getFraudType() !== $fraudType)
            {
                try
                {
                    $merchantDetail->setFraudType($fraudType);

                    $this->repo->merchant_detail->saveOrFail($merchantDetail);

                    $this->trace->info(TraceCode::MERCHANT_DETAIL_FRAUD_TYPE_UPDATE_REQUEST,
                        [
                            'merchant_id'   => $merchantId,
                            Entity::FRAUD_TYPE      => $fraudType
                        ]);

                    $response = [
                        'updated_ids'       => $merchantId
                    ];
                }
                catch (\Throwable $ex)
                {
                    $this->trace->error(TraceCode::MERCHANT_DETAIL_FRAUD_TYPE_UPDATE_SKIPPED,
                        [
                            'merchant_id'        => $merchantId,
                            'reason'             => $ex->getMessage(),
                        ]);

                    $response = [
                        'not_updated_ids'   => $merchantId
                    ];
                }
            }
        }

        $this->trace->info(TraceCode::MERCHANT_DETAIL_FRAUD_TYPE_UPDATED, $response);

        return $response;
    }

    public function getGstinSelfServeStatus()
    {
        $status = DEConstants::GSTIN_SELF_SERVE_STATUS_NOT_STARTED;

        $data = $this->getGstinSelfServeInputFromCache();

        list($isOpenWorkFlow, $rejectionReason) = $this->getWorkflowDataForGstinSelfServe();

        if (($data !== null) or
            ($isOpenWorkFlow === true))
        {
            $status = DEConstants::GSTIN_SELF_SERVE_STATUS_IN_PROGRESS;
        }
        $this->trace->info(TraceCode::GSTIN_UPDATE_SELF_SERVE_STATUS, [
            DEConstants::STATUS                => $status,
            DetailConstants::REJECTION_REASON  => $rejectionReason
        ]);

        return [
            DEConstants::STATUS               => $status,
            DetailConstants::REJECTION_REASON => $rejectionReason
        ];
    }

    protected function getWorkflowDataForGstinSelfServe()
    {
        [$entityId, $entity] = (new Merchant\Core)->fetchWorkflowData(Constants::GSTIN_UPDATE_SELF_SERVE,  $this->merchant);

        $action = (new WorkFlowActionCore())->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [Permission\Name::EDIT_MERCHANT_GSTIN_DETAIL]
        );

        if (empty($action) === true)
        {
            return [false, null];
        }

        $rejectionReason = $this->getRejectionReasonForGstInSelfServe($action->getId());

        return [$action->isOpen(), $rejectionReason];
    }

    protected function getRejectionReasonForGstInSelfServe($actionId)
    {
        $observerData = (new WorkflowService())->getWorkflowObserverData(WorkflowAction\Entity::getSignedId($actionId));

        $showRejection =  $observerData[WorkflowObserverConstants::SHOW_REJECTION_REASON_ON_DASHBOARD] ?? 'true';

        if (($showRejection === 'true') and
            (isset($observerData[WorkflowObserverConstants::REJECTION_REASON])))
        {
            $rejectionReason = json_decode($observerData[WorkflowObserverConstants::REJECTION_REASON], true);

            return $rejectionReason[WorkflowObserverConstants::MESSAGE_BODY] ?? null;
        }

        return null;
    }

    public function updateGstinSelfServe($input)
    {
        $this->validator->validateInput('gstin_self_serve', $input);

        $this->merchant->getValidator()->validateIsActivated($this->merchant);

        $this->trace->info(TraceCode::GSTIN_UPDATE_SELF_SERVE_INITIATED, []);

        $payload = $this->getUpdateGstinSelfServeBvsPayload($input);

        $validation = (new BvsCore)->verify($this->merchant->getId(), $payload);

        if ($validation === null)
        {
            throw new Exception\ServerErrorException('', ErrorCode::SERVER_ERROR);
        }

        $this->trace->info(TraceCode::GSTIN_UPDATE_SELF_SERVE_VALIDATION_CREATED, $validation->toArrayPublic());

        $fileId = $this->uploadGstInCertificateForGstinSelfServe(
            $input[DetailConstants::GSTIN_SELF_SERVE_CERTIFICATE],
            $this->merchant->merchantDetail
        );

        unset($input[DetailConstants::GSTIN_SELF_SERVE_CERTIFICATE]);

        $input = array_merge($input, [
            Merchant\Entity::MERCHANT_ID               => $this->merchant->getId(),
            BvsConstant::VALIDATION_ID                 => $validation->getValidationId(),
            DetailConstants::GSTIN_CERTIFICATE_FILE_ID => $fileId,
        ]);

        $this->storeGstinSelfServeInput($input);

        return $input;
    }


    public function handleGstinSelfServeCallback(Entity $detail, Merchant\BvsValidation\Entity $validation)
    {
        $this->trace->info(TraceCode::GSTIN_SELF_SERVE_BVS_CALLBACK_RECEIVED, $validation->toArrayPublic());

        switch ($validation->getValidationStatus())
        {
            case 'success':
                $this->handleGstinSelfServeCallbackSuccess($detail);
                break;
            default:
                $this->handleGstinSelfServeCallbackFailure($detail);
        }

        $this->deleteGstinSelfServeInput();
    }


    /**
     * @param Entity $detail
     */
    private function handleGstinSelfServeCallbackSuccess(Entity $detail): void
    {
        $input = $this->getGstinSelfServeInputFromCache();

        $registeredBusinessAddressDetails =  $this->getRegisteredBusinessAddressFromBvsForGstinUpdateSelfServe($detail->getMerchantId(), $input[BvsConstant::VALIDATION_ID]);

        $detail->edit(
            array_merge([
                    Entity::GSTIN => $input[Entity::GSTIN]
                ],
                $registeredBusinessAddressDetails
            )
        );

        $this->repo->merchant_detail->saveOrFail($detail);

        // if any previous rejected workflow of gstin exist : do not show rejection reason for any old rejected workflow
        $this->stopShowingRejectionReasonForGstInSelfServe($detail->getId(), $detail->getEntity());

        $this->sendMailForGstinUpdatedSelfServe();

        $this->trace->info(TraceCode::GSTIN_UPDATED_WITH_REGISTERED_ADDRESS, []);
    }

    protected function getRegisteredBusinessAddressFromBvsForGstinUpdateSelfServe($merchantId, $validationId)
    {
        $verificationDetails = $this->getBvsValidationArtefactDetails($merchantId,
            BvsConstant::GSTIN,
            $validationId
        );

        $registeredAddress = $verificationDetails[BvsConstant::ENRICHMENT_DETAIL_FIELDS]->online_provider->details->primary_address->value;

        $this->trace->info(TraceCode::GSTIN_BUSINESS_REGISTERED_ADDRESS_FROM_BVS, [$registeredAddress]);

        return $this->getComponentOfRegisteredAddressForGstInSelfServe($registeredAddress);
    }

    /**
     * Bvs provides address as string in format of '<business_registered_address>, <business_registered_city>, <business_registered_state>, <business_registered_pin>'
     * Ex : 1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI, Mumbai City, Maharashtra, 400018
     * This function extracts address fields
     * @param $registeredAddress
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\ServerErrorException
     */
    protected function getComponentOfRegisteredAddressForGstInSelfServe($registeredAddress)
    {
        $components = explode(',' , $registeredAddress);

        $size = sizeof($components);

        if ($size < 4)
        {
            throw new Exception\ServerErrorException(
            'Failed to get business registered address fields',
            ErrorCode::SERVER_ERROR);
        }

        return [
            Entity::BUSINESS_REGISTERED_PIN     => trim($components[$size - 1]),
            Entity::BUSINESS_REGISTERED_STATE   => $this->getStateCodeFromStateName(trim($components[$size - 2])),
            Entity::BUSINESS_REGISTERED_CITY    => trim($components[$size - 3]),
            Entity::BUSINESS_REGISTERED_ADDRESS => trim(implode(',', array_slice($components, 0, $size - 3)))
        ];
    }

    protected function getStateCodeFromStateName($stateName)
    {
        $stateCode = IndianStates::getStateCode($stateName);

        if(empty($stateCode) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_INVALID_STATE_CODE
            );
        }

        return $stateCode;
    }

    protected function handleGstinSelfServeCallbackFailure(Entity $oldDetailEntity)
    {
        $input = $this->getGstinSelfServeInputFromCache();

        $newDetailsEntity = clone $oldDetailEntity;

        $newDetailsEntity->edit([
            Entity::GSTIN => $input[Entity::GSTIN],
        ]);

        $this->app['workflow']
            ->setPermission(Permission\Name::EDIT_MERCHANT_GSTIN_DETAIL)
            ->setRouteName(DetailConstants::GSTIN_UPDATE_SELF_SERVE_ROUTE_NAME)
            ->setRouteParams([])
            ->setInput($input)
            ->setController(DetailConstants::GSTIN_UPDATE_SELF_SERVE_WORKFLOW_CONTROLLER)
            ->setMethod('POST')
            ->setEntityAndId($oldDetailEntity->getEntity(), $oldDetailEntity->getId())
            ->handle($oldDetailEntity, $newDetailsEntity, true);


        // for sanity
        $this->app['workflow']
            ->setInput(null)
            ->setPermission(null)
            ->setRouteName(null)
            ->setRouteParams(null)
            ->setController(null)
            ->setWorkflowMaker(null)
            ->setWorkflowMakerType(null)
            ->setMakerFromAuth(true);

        $this->addGstinCertificateUrlInWorkflowCommentForGstinSelfServe(
            $input[DetailConstants::GSTIN_CERTIFICATE_FILE_ID],
            $oldDetailEntity
        );
    }

    protected function stopShowingRejectionReasonForGstInSelfServe($entityId, $entity)
    {
        $action = (new WorkFlowActionCore())->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [Permission\Name::EDIT_MERCHANT_GSTIN_DETAIL]
        );

        if ((empty($action) === false) and
            ($action->isRejected() === true))
        {
            (new WorkflowService())->updateWorkflowObserverData(WorkflowAction\Entity::getSignedId($action->getId()),[
                    WorkflowObserverConstants::SHOW_REJECTION_REASON_ON_DASHBOARD => 'false'
            ]);
        }
    }

    public function updateMerchantGstinDetailsOnSelfServeWorkflowApprove($input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($input[Merchant\Entity::MERCHANT_ID]);

        $merchantDetails = $merchant->merchantDetail;

        $merchantDetails->edit([
                Entity::GSTIN => $input[Entity::GSTIN],
            ]
        );

        $this->repo->merchant_detail->saveOrFail($merchantDetails);

        $this->sendMailForGstinUpdatedSelfServe(DashboardEvents::GSTIN_UPDATED_ON_WORKFLOW_APPROVE);
    }

    protected function storeGstinSelfServeInput($input)
    {
        $cacheKey = $this->getGstinSelfServeInputCacheKey();

        $this->app['cache']->put($cacheKey, $input, DEConstants::GSTIN_SELF_SERVE_INPUT_CACHE_TTL);
    }

    protected function deleteGstinSelfServeInput()
    {
        $cacheKey = $this->getGstinSelfServeInputCacheKey();

        $this->app['cache']->delete($cacheKey);
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

    /**
     * @param $input
     * @return array
     */
    protected function getUpdateGstinSelfServeBvsPayload($input): array
    {
        return [
            BvsConstant::CUSTOM_CALLBACK_HANDLER => 'gstin_self_serve_callback_handler',
            BvsConstant::ARTEFACT_TYPE           => BvsConstant::GSTIN,
            BvsConstant::CONFIG_NAME             => 'gstin',
            BvsConstant::VALIDATION_UNIT         => BvsValidationConstants::IDENTIFIER,
            BvsConstant::DETAILS                 => [
                BvsConstant::GSTIN      => $input[Entity::GSTIN],
                BvsConstant::LEGAL_NAME => $this->merchant->merchantDetail->getPromoterPanName() ?? '',
                BvsConstant::TRADE_NAME => $this->merchant->merchantDetail->getBusinessName() ?? '',
            ],
        ];
    }

    protected function addGstinCertificateUrlInWorkflowCommentForGstinSelfServe($fileId, $merchantDetail)
    {
        $workFlowAction = (new WorkFlowActionCore())->fetchOpenActionOnEntityOperation($merchantDetail->getId(),
            $merchantDetail->getEntity(),
            Permission\Name::EDIT_MERCHANT_GSTIN_DETAIL
        )->first();

        if (is_null($workFlowAction) === true)
        {
            $this->trace->error(TraceCode::GSTIN_UPDATE_WORKFLOW_ACTION_NOT_FOUND, [
                'merchant_id' => $this->merchant->getId(),
            ]);
        }
        else
        {
            $comment = sprintf(
                DEConstants::GSTIN_CERTIFICATE_WORKFLOW_COMMENT,
                $this->app->config->get('applications.dashboard.url'),
                $fileId
            );

            $commentEntity = (new CommentCore())->create([
                'comment' => $comment,
            ]);

            $commentEntity->entity()->associate($workFlowAction);

            $this->repo->saveOrFail($commentEntity);
        }

        $this->trace->info(TraceCode::GSTIN_CERTIFICATE_URL_ADDED_IN_WORKFLOW_COMMENT, [
            'workflow_action' => $workFlowAction->toArrayPublic(),
        ]);
    }

    protected function sendMailForGstinUpdatedSelfServe($event = DashboardEvents::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS)
    {
        $args = [
            Constants::MERCHANT         => $this->merchant,
            DashboardEvents::EVENT      => $event,
            Constants::PARAMS           => []
        ];

        (new DashboardNotificationHandler($args))->send();
    }

    protected function uploadGstInCertificateForGstinSelfServe($gstinCertificate, $merchantDetails)
    {
        $fileInputs = [
            DetailConstants::GSTIN_SELF_SERVE_CERTIFICATE => $gstinCertificate
        ];

        $fileAttributes = $this->storeActivationFile($merchantDetails, $fileInputs);

        if ((is_array($fileAttributes) === false) or
            (isset($fileAttributes[DetailConstants::GSTIN_SELF_SERVE_CERTIFICATE]) === false))
        {
            throw new Exception\ServerErrorException(
                'gstin certificate upload failed',
                ErrorCode::SERVER_ERROR);
        }

        return $fileAttributes[DetailConstants::GSTIN_SELF_SERVE_CERTIFICATE][Document\Constants::FILE_ID];
    }

    public function getAovConfig()
    {
        $response = [];

        foreach (Merchant\AvgOrderValue\Constants::AOV_RANGES as $range)
        {
            $response['config'][] = [
                'min' => $range[0],
                'max' => $range[1],
            ];
        }

        return $response;
    }

    public function getMerchantTncById($id): array
    {
        $this->trace->info(TraceCode::MERCHANT_TNC_GET_REQUEST, [
            "tnc_id" => $id
        ]);

        $tnc = $this->repo->merchant_tnc->findOrFailPublic($id);

        $merchantDetail = $tnc->merchantDetail;

        $merchant = $merchantDetail->merchant;

        $merchantEmail = (new Merchant\Email\Service())->proxyGetSupportDetails($tnc->merchantDetail->merchant);

        $publicTncDetails = [
            'link' => (new Merchant\Tnc\Core)->getMerchantTncLink($merchant, $id)
        ];

        foreach (DetailConstants::PUBLIC_TNC_DETAILS as $var => $entities)
        {
            foreach ($entities as $entity)
            {
                if (isset(${$var}[$entity]) === true)
                {
                    $publicTncDetails[$entity] = ${$var}[$entity];
                }
                else
                {
                    $publicTncDetails[$entity] = null;
                }
            }
        }

        $businessCategory = $merchantDetail[Entity::BUSINESS_CATEGORY];

        $businessSubcategory = $merchantDetail[Entity::BUSINESS_SUBCATEGORY];

        $publicTncDetails[Entity::BUSINESS_CATEGORY] = BusinessCategory::DESCRIPTIONS[$businessCategory];

        $publicTncDetails[Entity::BUSINESS_SUBCATEGORY] = Sub::DESCRIPTIONS[$businessSubcategory];

        $this->trace->info(TraceCode::MERCHANT_TNC_GET_REQUEST_SUCCESS, [
            "publicTncDetails" => $publicTncDetails
        ]);

        return $publicTncDetails;
    }

    public function saveMerchantTnc(array $input)
    {
        if ((new Merchant\Detail\Core)->isMerchantTncApplicable($this->merchant) === true)
        {
            $emailInput = [
                'email' => $input['support_email']
            ];

            unset($input['support_email']);

            (new Merchant\Email\Service)->proxyEditSupportDetails($this->merchant, $emailInput);

            return (new Merchant\Tnc\Core)->createOrEditTnc($this->merchant->merchantDetail, $input);
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_TNC_NOT_APPLICABLE);
        }
    }

    public function createPartnerActivationForPartners(array $input)
    {
        $partnerCore =  new Partner\Core();
        return $partnerCore->createPartnerActivationForPartners($input);
    }

    public function getFirstL2SubmissionDate()
    {
        $activationStatusChangeLogs = $this->getActivationStatusChangeLog($this->merchant->getId(), Mode::LIVE)['items'];

        $this->trace->info(
            TraceCode::SHOW_CREATE_TICKET_POPUP_DEBUG,
            [
                'action_state_logs without filter' => $activationStatusChangeLogs,
            ]);

        $activationStatusChangeLogs = array_values(array_filter($activationStatusChangeLogs, function ($activationStatusChangeLog)
        {
            return ($activationStatusChangeLog[StateChangeEntity::NAME] === Status::UNDER_REVIEW);
        }));

        $this->trace->info(
            TraceCode::SHOW_CREATE_TICKET_POPUP_DEBUG,
            [
                'action_state_logs after filter' => $activationStatusChangeLogs,
            ]);

        return empty($activationStatusChangeLogs) === false ? $activationStatusChangeLogs[0][StateChangeEntity::CREATED_AT] : 0;
    }

    /**
     * @param string $merchantId
     * @param string $validationArtefact
     * @param null $validationId
     * @return
     * @throws Exception\LogicException
     */
    public function getBvsValidationArtefactDetails(string $merchantId, string $validationArtefact, $validationId = null)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $factory = new Merchant\AutoKyc\Bvs\requestDispatcher\Factory();

        $requestDispatcher =  $factory->getBvsRequestDispatcherForArtefact(
            $validationArtefact, $merchant, $merchant->merchantDetail);

        return $requestDispatcher->fetchValidationDetails($validationId);
    }

    /**
     * This function is used for add/edit business website details of an activated merchant
     * @param array $input
     *
     * @return array
     */
    public function postSaveBusinessWebsite(string $urlType, array $input)
    {
        $merchantDetails = $this->merchant->merchantDetail;

        if($merchantDetails->getActivationStatus() != Status::ACTIVATED)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED);
        }

        $response = (new Core)->postSaveBusinessWebsite($urlType, $input);

        return $response;
    }

    public function getDecryptedWebsiteCommentForWebsiteSelfServe(string $actionId)
    {
        WorkflowAction\Entity::verifyIdAndStripSign($actionId);

        $this->trace->info(
            TraceCode::MERCHANT_DECRYPT_WEBSITE_COMMENT_FOR_SELF_SERVE, [
                "actionId"     => $actionId,
            ]
        );

        $comments = $this->repo
            ->comment
            ->fetchByActionIdWithRelations(
                $actionId, [WorkflowAction\Entity::ADMIN]);

        return $this->core()->getDecryptedWebsiteCommentForWebsiteSelfServe($comments);
    }

    public function isMidBelongsToMswipe(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantDetails = $merchant->merchantDetail;

        $pattern = "/mswipe.com/i";

        //to check if website/additonal_website contains "mswipe.com"
        $website = $merchantDetails->getWebsite();

        if (preg_match($pattern, $website))
        {
            return true;
        }

        $additionalWebsites = $merchantDetails->getAdditionalWebsites();
        if (empty($additionalWebsites) === false)
        {
            foreach ($additionalWebsites as $additionalWebsite)
            {
                if (preg_match($pattern, $additionalWebsite))
                {
                    return true;
                }
            }
        }

        return false;
    }

    public function postAddAdditionalWebsiteSelfServe($urlType, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_ADD_ADDITIONAL_WEBSITE_DETAILS,
            [
                DetailConstants::URL_TYPE   => $urlType,
                Constants::INPUT            => $input
            ]
        );

        $core = new Core();

        $merchantDetails = $this->merchant->merchantDetail;

        $response = $core->postAddAdditionalWebsiteSelfServe($merchantDetails, $urlType, $input);

        return $response;
    }

    public function putAddAdditionalWebsiteSelfServePostWorkflowApproval(array $input)
    {
        $merchantId = $input[Constants::MERCHANT_ID];

        $newUrl = ($input[DetailConstants::URL_TYPE] === DetailConstants::URL_TYPE_WEBSITE) ? $input[DetailConstants::ADDITIONAL_WEBSITE_MAIN_PAGE] : $input[DetailConstants::ADDITIONAL_APP_URL];

        $additionalWebsite = [Entity::ADDITIONAL_WEBSITE => $newUrl];

        $response = $this->putAdditionalWebsite($merchantId, $additionalWebsite);

        return $response;
    }

    public function getAdditionalWebsiteWorkflowStatus()
    {
        $status = (new Merchant\Service())->openWorkflowExists(Constants::ADD_ADDITIONAL_WEBSITE);

        return $status;
    }

    public function getAgentApprovedTransactionLimit(Merchant\Entity $merchant)
    {

        $actionEntity = (new WorkFlowActionCore())->fetchOpenActionOnEntityOperation($merchant->getMerchantId(),
            $merchant->getEntity(),
            PermissionName::INCREASE_TRANSACTION_LIMIT,
            $merchant->getOrgId()
        )->first();

        $differEntity = (new DifferCore)->fetchRequest($actionEntity->getId());

        if (empty($differEntity) === true)
        {
            throw new Exception\ServerErrorException('Workflow action differ entity not found',
                ErrorCode::SERVER_ERROR);
        }

        $this->trace->info(TraceCode::GET_AGENT_APPROVED_TRANSACTION_LIMIT, [
            DifferEntity::ACTION_ID                                  => $actionEntity->getId(),
            DifferEntity::WORKFLOW_OBSERVER_DATA                     => $differEntity[DifferEntity::WORKFLOW_OBSERVER_DATA] ?? [],
        ]);

        if(isset($differEntity[DifferEntity::WORKFLOW_OBSERVER_DATA][WorkflowObserver\Constants::APPROVED_TRANSACTION_LIMIT]) == true)
        {
            return $differEntity[DifferEntity::WORKFLOW_OBSERVER_DATA][WorkflowObserver\Constants::APPROVED_TRANSACTION_LIMIT];
        }
    }

    public function updateLinkedAccountBankVerificationStatus($merchantIds)
    {
        $updateCount = 0;

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $this->trace->info(TraceCode::LA_MERCHANT_DETAILS_EDIT_REQUEST,
                [
                    'merchant_id' => $merchantId
                ]);

                $merchantDetail = $this->repo->merchant_detail->findOrFail($merchantId);

                $merchantDetail->setBankDetailsVerificationStatus(BankDetailsVerificationStatus::VERIFIED);

                $this->repo->merchant_detail->saveOrFail($merchantDetail);

                $this->trace->info(TraceCode::LA_MERCHANT_DETAIL_SUGGESTED_FIELDS_UPDATED,
                    [
                        'merchant_id' => $merchantId
                    ]);

                $updateCount += 1;
            }
            catch (Throwable $e)
            {
                $this->trace->traceException($e,null, TraceCode::LA_MERCHANT_DETAIL_BANK_STATUS_UPDATE_FAILED);
            }
        }
        return $updateCount;
    }
}
