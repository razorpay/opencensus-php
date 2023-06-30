<?php

namespace RZP\Models\Merchant\Detail;

use RZP\lib\TemplateEngine;
use DOMDocument;
use RZP\Http\RequestHeader;
use RZP\Constants\Environment;
use RZP\Models\DeviceDetail\Constants as DDConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Services\Segment\Constants as SegmentConstants;
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
use RZP\Service\WhatCmsService;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\Constants;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Models\Merchant\XChannelDefinition;
use Illuminate\Support\Facades\Mail;
use RZP\Error\PublicErrorDescription;
use RZP\Mail\Merchant as MerchantMail;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Services\KafkaMessageProcessor;
use RZP\Models\Merchant\Action as Action;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Comment\Core as CommentCore;
use RZP\Models\Batch\Header as BatchHeader;
use RZP\Models\Batch\Status as BatchStatus;
use RZP\Models\Merchant\Credits as Credits;
use RZP\Models\Merchant\Document as Document;
use RZP\Models\Merchant\Referral as Referral;
use RZP\Notifications\AdminDashboard\Events;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Services\Segment as SegmentAnalytics;
use RZP\Notifications\AdminDashboard\Handler;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Models\BankingAccount as BankingAccount;
use RZP\Models\Workflow\Action as WorkflowAction;
use RZP\Models\Merchant\CapitalSubmerchantUtility;
use \RZP\Models\State\Entity as StateChangeEntity;
use \WpOrg\Requests\Exception as RequestsException;
use RZP\Models\Transaction\CreditType as CreditType;
use RZP\Services\Segment\EventCode as SegmentEvent;
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
use RZP\Models\Merchant\AccessMap\Core as AccessMapCore;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Workflow\Action\Differ\Core as DifferCore;
use RZP\Notifications\Dashboard\Events as DashboardEvents;
use RZP\Models\Merchant\Detail\BusinessSubcategory as Sub;
use RZP\Models\Workflow\Action\Core as WorkFlowActionCore;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BvsConstant;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Workflow\Action\Differ\Entity as DifferEntity;
use RZP\Jobs\Transfers\LinkedAccountBankVerificationStatusBackfill;
use \RZP\Models\DeviceDetail\Attribution\Core as AttributionCore;
use RZP\Models\Merchant\FreshdeskTicket\Entity as FDTicketEntity;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Models\Merchant\Invoice\Service as MerchantInvoiceService;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApp;
use RZP\Models\Merchant\Detail\RejectionReasons as RejectionReasons;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;
use RZP\Models\Workflow\Observer\Constants as WorkflowObserverConstants;
use Platform\Bvs\Legaldocumentmanager\V1\LegalDocumentsManagerResponse;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Notifications\Dashboard\Constants as DashboardNotificationConstants;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessDetailConstants;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient\BvsValidationClient;
use RZP\Models\Transaction\Service as TransactionService;
use GuzzleHttp\Client as HttpClient;
use RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;
use RZP\Models\Merchant\AutoKyc\Bvs\BaseResponse\LegalDocumentBaseResponse;
use RZP\Models\Merchant\Consent\Details\Entity as MerchantConsentDetails;
use RZP\Models\Merchant\Consent\Entity as MerchantConsent;
use RZP\Models\Merchant\Consent\Core as ConsentCore;
use RZP\Models\Merchant\Consent\Constants as ConsentConstant;
use Illuminate\Database\Query\Builder;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Store;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\Store\ConfigKey as StoreConfigKey;
use RZP\Models\Merchant\Referral\Entity as ReferralEntity;
use RZP\Models\Merchant\Consent\Processor\Factory as ProcessorFactory;

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

    public function fetchMerchantDetailsForAccountReceivables(): array
    {
        $merchantDetails = $this->merchant->merchantDetail;

        $response = [
            Merchant\Entity::BILLING_LABEL      => $this->merchant->getLabelForInvoice(),
            Entity::GSTIN                       => $merchantDetails->getGstin(),
            Entity::COMPANY_CIN                 => $merchantDetails->getCompanyCin(),
            Entity::BUSINESS_REGISTERED_ADDRESS => $merchantDetails->getBusinessRegisteredAddress(),
            Entity::BUSINESS_REGISTERED_STATE   => $merchantDetails->getBusinessRegisteredStateName(),
            Entity::BUSINESS_REGISTERED_CITY    => $merchantDetails->getBusinessRegisteredCity(),
            Entity::BUSINESS_REGISTERED_PIN     => $merchantDetails->getBusinessRegisteredPin(),
            'merchant_brand_logo'               => $this->merchant->getFullLogoUrlWithSize(),
            'merchant_brand_color'              => $this->merchant->getBrandColorElseDefault(),
            'merchant_contrast_color'           => $this->merchant->getContrastOfBrandColor(),
            Merchant\Entity::EMAIL              => $this->merchant->getEmail(),
        ];

        $bankingAccountList = $this->merchant->activeBankingAccounts();
        $currentAccount = current(array_filter($bankingAccountList->toArray(), function ($account) {
            return $account[BankingAccount\Entity::ACCOUNT_TYPE] === 'current';
        }));

        if (empty($currentAccount) === false) {
            $response['bank_account'] = [
                "name"           => $currentAccount[BankingAccount\Entity::BENEFICIARY_NAME] ?? '',
                "ifsc"           => $currentAccount[BankingAccount\Entity::ACCOUNT_IFSC] ?? '',
                "account_number" => $currentAccount[BankingAccount\Entity::ACCOUNT_NUMBER] ?? '',
            ];
        }

        return $response;
    }

    public function fetchMerchantDetailsForAccountingIntegrations()
    {
        $merchantDetails = $this->merchant->merchantDetail;

        $response = [
            Merchant\Entity::ID                 => $this->merchant->getId(),
            Entity::ACTIVATION_STATUS           => $merchantDetails->getActivationStatus(),
        ];

        return $response;
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

        $input[Constants::PHANTOM_ONBOARDING] = \Request::all()[Constants::PHANTOM_ONBOARDING_FLOW_ENABLED] ?? false;

        $this->trace->info(TraceCode::UTM_PARAMS, [
            'merchant'    => $this->merchant->getId(),
            'eventParams' => $input
        ]);

        $this->app['diag']->trackOnboardingEvent(EventCode::SIGNUP_FINISH_SIGNUP_SUCCESS, $this->merchant, null, $input);

        unset($input[Constants::PARTNER_INTENT]);

        $attributeCore = new Merchant\Attribute\Core;

        try
        {
            $campaignTypeAttr = $attributeCore->fetch($this->merchant, Product::BANKING,
                Merchant\Attribute\Group::X_SIGNUP, Merchant\Attribute\Type::CAMPAIGN_TYPE);
        }
        catch (\Throwable $e)
        {
            $campaignTypeAttr = null;
        }

        if ($campaignTypeAttr !== null)
        {
            $input['x_onboarding_category'] = 'self_serve';
        }

        if ($this->auth->isProductBanking())
        {
            $xChannelDefinitionService = new XChannelDefinition\Service;
            $xChannelDefinitionService->storeChannelDetails($this->merchant, $input);
            $xChannelDefinitionService->addChannelDetailsInPreSignupSFPayload($this->merchant, $input);

            // Set SF Lead's Progress as pre-signup to avoid confusion with
            // Signup completed lead (pre_signup done + email verification done)
            $input['lead_progress'] = 'Pre signup lead';

            try
            {
                $caOnboardingFlowAttr = $attributeCore->fetch($this->merchant, Product::BANKING,
                    Merchant\Attribute\Group::X_MERCHANT_CURRENT_ACCOUNTS, Merchant\Attribute\Type::CA_ONBOARDING_FLOW);
            }
            catch (\Throwable $e)
            {
                $caOnboardingFlowAttr = null;
            }

            if ($caOnboardingFlowAttr !== null)
            {
                $input['ca_onboarding_flow'] = $caOnboardingFlowAttr->getValue();
            }

            // added lumberjack integration to match data pulled from SF with product data
            app('diag')->trackOnboardingEvent(EventCode::X_CA_ONBOARDING_LEAD_UPSERT, $this->merchant, null, $input);
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
        $input[User\Entity::EMAIL] = mb_strtolower($input[User\Entity::EMAIL]);

        $email = $input[User\Entity::EMAIL];

        $user = $this->user;

        $merchant = $this->app['basicauth']->getMerchant();
        $merchantId = $merchant->getId();

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

        // route requests when email validation is done.
        try {
            $body = [
                'merchant_id' => $merchantId,
                User\Entity::EMAIL => $email
            ];

            $pgosProxyController = new MerchantOnboardingProxyController();

            $response = $pgosProxyController->handlePGOSProxyRequests('merchant_activation_save', $body, $merchant);

            $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                'merchant_id' => $merchantId,
                'response' => $response,
            ]);
        }
        catch (RequestsException $e) {

            if (checkRequestTimeout($e) === true) {
                $this->trace->info(TraceCode::PGOS_PROXY_TIMEOUT, [
                    'merchant_id' => $merchantId,
                ]);
            }

        }
        catch (\Throwable $exception) {
            // this should not introduce error counts as it is running in shadow mode
            $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                'error_message' => $exception->getMessage()
            ]);
        }

        if(empty($emailUser) === true)
        {
            $this->repo->transactionOnLiveAndTest(function() use ($user, $input) {
                $user->setEmail($input[Merchant\Entity::EMAIL]);
                $this->repo->saveOrFail($user);
            });

            $this->repo->transactionOnLiveAndTest(function() use ($merchant, $input) {
                $merchant->setAttribute(User\Entity::EMAIL, $input[Merchant\Entity::EMAIL]);
                $this->repo->saveOrFail($merchant);
            });

            $merchantDetails = $this->merchant->merchantDetail;

            $this->repo->transactionOnLiveAndTest(function() use ($merchantDetails, $input) {
                $merchantDetails->setContactEmail($input[Merchant\Entity::EMAIL]);
                $this->repo->saveOrFail($merchantDetails);
            });

            $properties = [
                'email' => $input[Merchant\Entity::EMAIL]
            ];

            $this->app['segment-analytics']->pushIdentifyEvent($merchant, $properties);
        }

        $requestOriginProduct = $this->auth->getRequestOriginProduct();

        $isProductBanking = ($requestOriginProduct === Product::BANKING);

        $inputData = ["isRequestFromXVerifyEmail" => $isProductBanking];

        return (new User\Service())->sendOtpEmailVerification($this->merchant, $this->user, $input, $inputData);
    }

    public function saveMerchantDetailsForActivation(array $input)
    {
        $activationFormMilestone = $input[Entity::ACTIVATION_FORM_MILESTONE] ?? null;

        $consent = $input[DEConstants::CONSENT] ?? null;

        $merchantId = $this->merchant->getMerchantId();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->allowEditingOfBusinessNameAndDBAKYC($merchant, $input);

        Entity::modifyConvertEmptyStringsToNull($input);

        Merchant\PhantomUtility::checkAndSetContextForPhantomSource($input);

        $partnerId = $this->getPartnerInfoFromInput($input);

        $isPhantomOnboardingFlow = Merchant\PhantomUtility::validatePhantomOnBoarding($partnerId);

        if ($activationFormMilestone === DEConstants::L1_SUBMISSION)
        {
            $response = $this->saveInstantActivationDetails($input);
        }
        else
        {
            if ($activationFormMilestone === DEConstants::L2_SUBMISSION and $consent != null)
            {
                // If merchant has not accepted the legal documents, merchant can not submit the L2 form.
                if ($consent === false)
                {
                    throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, [
                        'error description' => 'The merchant has not accepted the legal documents.'
                    ]);
                }

                try {

                    //if legal documents are not present already, store them in database
                    if($this->checkIfConsentsPresent($merchantId, ConsentConstant::VALID_LEGAL_DOC_L2) === false)
                    {
                        $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
                            'merchant_id' => $merchantId,
                            'message'     => 'Consents are not present.'
                        ]);

                        $this->storeConsents($merchantId, $input);

                        $documents_detail = $this->getDocumentsDetails($input);

                        $legalDocumentsInput = [
                            DEConstants::DOCUMENTS_DETAIL => $documents_detail
                        ];

                        $processor = (new ProcessorFactory())->getLegalDocumentProcessor();

                        $response = $processor->processLegalDocuments($legalDocumentsInput);

                        $responseData = $response->getResponseData();

                        $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENT_DETAILS, [
                            'merchant_id' => $merchantId,
                            'response'    => $responseData
                        ]);

                        $documentDetailsInput = $input[DEConstants::DOCUMENTS_DETAIL];

                        foreach ($documentDetailsInput as $documentDetailInput)
                        {
                            $type = $activationFormMilestone.'_'.$documentDetailInput['type'] ;

                            $merchantConsentDetail = $this->repo->merchant_consents->fetchMerchantConsentDetails($merchantId, $type);

                            $updateInput = [
                                'status'     => ConsentConstant::INITIATED,
                                'updated_at' => Carbon::now()->getTimestamp(),
                                'request_id' => $responseData['id']
                            ];

                            (new ConsentCore())->updateConsentDetails($merchantConsentDetail, $updateInput);
                        }

                    }
                    else
                    {
                        $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
                            'merchant_id' => $merchantId,
                            'message' => 'Consents are already present.'
                        ]);
                    }
                }
                catch (\Throwable $exception)
                {
                    $this->trace->info(TraceCode::CONSENT_CREATION_ERROR, [
                        'merchant_id' => $merchantId,
                        'message'     => $exception->getMessage()
                    ]);
                }
            }

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

        if ($isPhantomOnboardingFlow)
        {
            $productInput = [Merchant\Product\Util\Constants::PRODUCT_NAME => Merchant\Product\Name::PAYMENT_GATEWAY];
            (new Merchant\Product\Core())->createMerchantProduct($merchant, $productInput);
        }

        // send the request to merchant onboarding service, once processing is done at API end
        // this should not affect the current flow, hence wrapped in try catch
        try
        {
            $pgosProxyController = new MerchantOnboardingProxyController();

            $input['merchantId'] = $merchantId;

            $pgosResponse = $pgosProxyController->handlePGOSProxyRequests('merchant_activation_save', $input, $this->merchant);

            $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                'response' => $pgosResponse
            ]);
        }
        catch (RequestsException $e) {
            if (checkRequestTimeout($e) === true) {
                $this->trace->info(TraceCode::PGOS_PROXY_TIMEOUT, [
                    'merchant_id' => $merchantId,
                ]);
            } else {
                $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                    'merchant_id' => $merchantId,
                    'error_message' => $e->getMessage()
                ]);
            }

        }
        catch (\Throwable $exception) {
            // this should not introduce error counts as it is running in shadow mode
            $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                'merchant_id' => $merchantId,
                'error_message' => $exception->getMessage()
            ]);
        }
        finally {
            unset($input['merchantId']);
        }

        return $response;
    }

    private function allowEditingOfBusinessNameAndDBAKYC($merchant, $input)
    {
        if (($merchant->org->isFeatureEnabled(Feature\Constants::ORG_PROGRAM_DS_CHECK) === false))
        {
            return;
        }

        // for test cases
        if (isset($merchant->merchantDetail) === false)
        {
            return false;
        }

        // temporary check to prevent merchants from editing DBA for compliance
        $businessDBA = $merchant->merchantDetail->getBusinessDba();

        if ((isset($input[Entity::BUSINESS_DBA]) === true) and
            (isset($businessDBA) === true) and
            ($businessDBA !== ''))
        {

            throw new Exception\BadRequestValidationFailureException(
                'DBA name cannot be changed'
            );
        }

        $businessName = $merchant->merchantDetail->getBusinessName();
        if ((isset($input[Entity::BUSINESS_NAME]) === true) and
            (isset($businessDBA) === true) and
            ($businessDBA !== ''))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Business Name cannot be changed'
            );
        }
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

        /**
         * This log is added temporarily to validate merchant's attributes values (live, activated, activated_at) when
         * admin updates the merchant details.
         */
        $this->trace->info(TraceCode::PATCH_MERCHANT_DETAILS, [
            'merchant_id'  => $this->merchant->getMerchantId(),
            'activate'     => $this->merchant->getActivated(),
            'live'         => $this->merchant->isLive(),
            'activated_at' => $this->merchant->getActivatedAt()
        ]);

        /**
         * Due to a bug which shows up intermittently, a few of the merchant's attributes (live, activated, activated_at)
         * are reset in this flow because the values are picked up from the cache and sometimes cache does not have updated data.
         * Hence even though merchant activation is completed, the merchant does not seem to be activated. This way
         * the updated merchant details will be fetched from the database. The bug could not be reproduced.
         * Ref - https://razorpay.slack.com/archives/C043K5N223F/p1673425345546419 for more details.
         */
        $merchant = $this->repo->merchant->findOrFailPublic($this->merchant->getMerchantId());

        $merchantDetails = $this->core()->patchMerchantDetails($merchant, $input);

        return $merchantDetails->toArrayPublic();
    }

    public function patchSmartDashboardMerchantDetails(array $input, $merchantId = null): array
    {
        $this->trace->info(TraceCode::SMART_DASHBOARD_MERCHANT_EDIT, [
            'input' => array_keys($input),
        ]);

        if(!empty($merchantId)){
            $this->merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            if(!empty($this->merchant)){
                unset($input[Entity::MERCHANT_ID]);
            }
            $this->trace->info(TraceCode::SMART_DASHBOARD_MERCHANT_EDIT, [
                'merchant' => $this->merchant,
            ]);
        }

        if (empty($this->merchant) === true)
        {
            $this->trace->err(ErrorCode::BAD_REQUEST_MERCHANT_CONTEXT_NOT_SET,
                ["error" => ErrorCode::BAD_REQUEST_MERCHANT_CONTEXT_NOT_SET]);
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_CONTEXT_NOT_SET);
        }

        (new Merchant\Validator)->validateSmartDashboardMerchantEditInput($input);

        $merchant = $this->merchant;

        $merchantEditInput = [];
        $merchantDetailEditInput = [];
        $merchantBusinessDetailEditInput = [];
        $merchantDocumentEditInput = [];

        foreach ($input as $key => $value)
        {
            $keyString = explode("|", $key);

            $attribute = $keyString[1] ?? null;

            switch ($keyString[0])
            {
                case 'merchant':
                    $merchantEditInput[$attribute] = $value;
                    break;
                case 'merchant_business_detail':
                    if(count($keyString)==2){
                        $merchantBusinessDetailEditInput[$attribute] =  $value;
                    }else{
                        $merchantBusinessDetailEditInput[$attribute] = [$keyString[2] => $value];
                    }
                    break;
                case 'documents':
                    $merchantDocumentEditInput[$attribute] = $value;
                    break;
                default:
                    $merchantDetailEditInput[$keyString[0]] = $value;
            }
        }

        $merchantDetailCore = $this->core;;

        if (count($merchantEditInput) > 0)
        {
            (new Merchant\Core)->edit($merchant, $merchantEditInput);
        }

        if (count($merchantBusinessDetailEditInput) > 0)
        {
            (new BusinessDetail\Core)->editBusinessDetail($merchant->merchantDetail, $merchantBusinessDetailEditInput);
        }

        if (count($merchantDocumentEditInput) > 0)
        {
            $this->uploadActivationFile($merchant, $merchantDocumentEditInput);
        }

        if (count($merchantDetailEditInput) > 0)
        {
            $merchantDetailCore->editMerchantDetailFields($merchant, $merchantDetailEditInput);
        }

        return (new Merchant\Service)->getSmartDashboardMerchantDetails($this->merchant);
    }

    public function postApplyCoupon(array $input)
    {
        $merchant   = $this->app['basicauth']->getMerchant();

        $merchantId = $merchant->getMerchantId();

        (new Coupon\Validator())->validateInput('apply_coupon_code', $input);

        $couponCode = $input[Coupon\Entity::CODE];

        $couponInput = [
            Coupon\Entity::CODE => $couponCode,
        ];

        // validates coupon code and merchant promotion
        $coupon = (new Coupon\Core())->validateAndGetDetails($merchant, $couponInput, false);

        $promotion = $coupon->source;

        if ($promotion->getCreditType() != CreditType::AMOUNT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ONLY_AMOUNT_CREDITS_COUPON_APPLICABLE
            );
        }

        if (empty($promotion->partner) === false and (new AccessMapCore)->isSubMerchant($merchant->getMerchantId()))
        {

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_NOT_APPLICABLE
            );
        }

        $merchantBalance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(),
                                                                          Merchant\Balance\Type::PRIMARY);

        $existingCredits = $merchantBalance->reload()->getAmountCredits();

        $this->trace->info(TraceCode::AMOUNT_CREDITS_COUPON_APPLY_REQUEST, [
            "merchantId"      => $merchantId,
            "input"           => $input,
            "existingCredits" => $existingCredits,
        ]);

        if (isset($input[DetailConstants::TOKEN]) === true)
        {
            //fetch the data from cache and if token matches then apply the coupon and expire any existing credits.
            $token = $input[DetailConstants::TOKEN];

            $cacheKey = DetailConstants::COUPON_CODE_CACHE_KEY_PREFIX . $merchantId . '_' . $couponCode;

            $cacheValue = $this->app['cache']->get($cacheKey);

            if ($token == $cacheValue)
            {
                //expire existing credits and apply coupon
                $this->trace->info(TraceCode::CREDITS_EXPIRE_REQUEST, [
                    "merchantId" => $merchantId,
                    "couponCode" => $couponCode,
                ]);

                //expire existing credits
                $this->expireRemainingCredits($merchant);

                (new Coupon\Core())->applyCouponCode($merchant, $coupon, false);

                [$segmentEventName, $segmentProperties] = $this->core->pushSelfServeSuccessEventsToSegment();

                $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Coupon Code Applied';

                $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                    $this->merchant, $segmentProperties, $segmentEventName
                );

                return [
                    'applied' => true,
                ];
            }
            else
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_COUPON_REQUEST_TIMED_OUT
                );
            }
        }
        else
        {
            /*
             when api is called for the first time, system will check if there is already an unexpired coupon applied.
             If yes, we will store the data [mid_token] in cache (with an expiry of 60 mins) and return token to the user
            */
            if ($existingCredits != 0)
            {
                //User have unexpired coupon code in their profile
                $token = UniqueIdEntity::generateUniqueId();

                $cacheKey = DetailConstants::COUPON_CODE_CACHE_KEY_PREFIX . $merchantId . '_' . $couponCode;

                $this->app['cache']->put($cacheKey, $token, DetailConstants::TOKEN_TTL * 60);

                return [
                    DetailConstants::TOKEN => $token,
                    'applied'              => false,
                    'data'                 => [
                        'available_credits' => $existingCredits
                    ],
                ];
            }
            else
            {
                (new Coupon\Core())->applyCouponCode($merchant, $coupon);

                [$segmentEventName, $segmentProperties] = $this->core->pushSelfServeSuccessEventsToSegment();

                $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Coupon Code Applied';

                $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                    $this->merchant, $segmentProperties, $segmentEventName
                );

                return [
                    'applied' => true,
                ];
            }
        }
    }

    public function expireRemainingCredits(Merchant\Entity $merchant)
    {
        $creditsId = $this->repo->credits->getUnexpiredCreditIdsForMerchantOfType($merchant->getMerchantId(), "amount");

        $credits = $this->repo->credits->getCreditEntities($creditsId);

        foreach($credits as $credit)
        {
            $credit->setExpiredAt(Carbon::now()->getTimestamp());

            $this->repo->saveOrFail($credit);

            $creditsToExpire = $credit->getUnusedCredits();

            if($creditsToExpire > 0)
            {
                $creditInput = [
                    Credits\Entity::CAMPAIGN => $credit->getCampaign() . 'Expired',
                    Credits\Entity::VALUE    => $creditsToExpire * -1,
                    Credits\Entity::TYPE     => $credit->getType(),
                ];

                (new Merchant\Promotion\Core())->forceExpireCredits($merchant, $creditInput);
            }
        }
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

            $originalFileName = $this->getOriginalFileName($file, $merchant->getId());

            $documentUploadInput = [
                Document\Constants::TYPE               => $type,
                Document\Constants::FILE               => $file,
                Document\Constants::FILE_NAME          => $fileName,
                Document\Constants::ENTITY             => $publicEntity,
                Document\Constants::MERCHANT           => $merchant,
                Document\Constants::ORIGINAL_FILE_NAME => $originalFileName

            ];

            $documentSource = $documentSource ?? Factory::getApplicableSource($merchant->getId());

            Document\Source::validateSource($documentSource);

            $fileHandler = Factory::getFileStoreHandler($documentSource, $this->ba->getMerchantId());

            $params[$type] = $fileHandler->uploadFile($documentUploadInput);
        }

        return $params;
    }

    private function getOriginalFileName($file, string $merchantId): string
    {
        return $file->getClientOriginalName();
    }

    public function uploadMerchant(array $input)
    {
        return (new Upload\Core)->uploadMerchant($input);
    }

    public function uploadMiqBatch(array $input): array
    {
        try
        {
            return (new Upload\Core)->processMerchantEntry($input);
        }
        catch (Exception\BaseException $e)
        {
            $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, [
                BatchHeader::MIQ_MERCHANT_NAME      => $input[BatchHeader::MIQ_MERCHANT_NAME],
                BatchHeader::MIQ_CONTACT_EMAIL     => $input[BatchHeader::MIQ_CONTACT_EMAIL],
            ]);

            $error = $e->getError();

            $input[BatchHeader::STATUS]            = BatchStatus::FAILURE;

            $input[BatchHeader::ERROR_CODE]        = $error->getPublicErrorCode();

            $input[BatchHeader::ERROR_DESCRIPTION] = $error->getDescription();

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, [
                BatchHeader::MIQ_MERCHANT_NAME      => $input[BatchHeader::MIQ_MERCHANT_NAME],
                BatchHeader::MIQ_CONTACT_EMAIL     => $input[BatchHeader::MIQ_CONTACT_EMAIL],
            ]);

            $input[BatchHeader::STATUS]     = BatchStatus::FAILURE;

            $input[BatchHeader::ERROR_CODE] = ErrorCode::SERVER_ERROR;

            $input[BatchHeader::ERROR_DESCRIPTION] = PublicErrorDescription::SERVER_ERROR;
        }

        return $input;
    }

    public function sendWhatsappNotification($id, array $input): array
    {
        (new Validator)->validateInput(__FUNCTION__, $input);

        try
        {
            $merchant = $this->repo->merchant->findOrFail($id);

            $status = $this->app['stork_service']->optInStatusForWhatsapp(
                $this->mode,
                $merchant->merchantDetail->getContactMobile(),
                'api.' . $this->mode . '.admin_dashboard'
            );

            if (array_key_exists('consent_status', $status) === false || $status['consent_status'] === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_OPT_OUT_WHATSAPP_NOTIFICATION);
            }
        }
        catch (Exception\TwirpException $e)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_NOT_FOUND);
        }

        $ticketEntity = (new Merchant\FreshdeskTicket\Service())->getTicketRzpEnitity(
            $input[Merchant\FreshdeskTicket\Entity::TICKET_ID],
            Merchant\FreshdeskTicket\Type::SUPPORT_DASHBOARD,
            $id, Merchant\FreshdeskTicket\Constants::RZPIND
        );

        if ($ticketEntity === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NO_TICKETS_FOUND_FOR_CUSTOMER);
        }

        (new Handler([
            'ticket'   => $ticketEntity,
            'documents'=> $input['documents'],
        ]))->sendForEvent(Events::NEEDS_CLARIFICATION);

        return [
            'success' => true
        ];
    }

    public function getRequestDocumentList(array $input): array
    {
        return [
            'count' => count(\RZP\Models\Merchant\Detail\Constants::DOCUMENTS_LIST_FOR_NEEDS_CLARIFICATION_NOTIFICATION),
            'items' => \RZP\Models\Merchant\Detail\Constants::DOCUMENTS_LIST_FOR_NEEDS_CLARIFICATION_NOTIFICATION,
        ];
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

        $this->allowEditingOfMIQForCompliance($merchant, $input);

        $merchantDetailCore = $this->core;

        $merchantDetails = $merchantDetailCore->editMerchantDetailFields($merchant, $input);

        if (isset($slackAction) === true)
        {
            $this->logActionToSlack($merchant, $slackAction);
        }

        return $merchantDetailCore->createResponse($merchantDetails);
    }

    public function allowEditingOfMIQForCompliance($merchant, $input)
    {

        if ($merchant->org->isFeatureEnabled(Feature\Constants::ORG_PROGRAM_DS_CHECK) === false)
        {
            return;
        }

        // for test cases
        if (isset($merchant->merchantDetail) === false)
        {
            return false;
        }

        $businessDBA = $merchant->merchantDetail->getBusinessDba();

        // temporary check to prevent merchants from editing business name for compliance
        if ((isset($merchant->merchantDetail) === true) and
            (isset($input[Entity::BUSINESS_NAME]) === true) and
            (isset($businessDBA) === true) and
            ($businessDBA !== ''))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Business name cannot be changed'
            );
        }

        // temporary check to prevent merchants from editing DBA for compliance
        if ((isset($merchant->merchantDetail) === true) and
            (isset($input[Entity::BUSINESS_DBA]) === true) and
            (isset($businessDBA) === true) and
            ($businessDBA !== ''))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Busisness DBA cannot be changed'
            );
        }
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

    /**
     * This function is used for updating merchant activation status
     * @param string $merchantId
     * @param array $input
     *
     * @return array
     */

    public function getNCAdditionalDocuments(){

        return (new Core())->getNCAdditionalDocuments();
    }

    public function updateActivationStatusInternal(string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->trace->info(TraceCode::MERCHANT_UPDATE_ACTIVATION_STATUS_INTERNAL, [
            DetailConstants::INPUT => $input,
            Entity::MERCHANT_ID    => $merchantId

        ]);

        $merchant->merchantDetail->getValidator()->validateInput('activationStatusInternal', $input);

        $maker = $this->repo->admin->findOrFailPublic( Admin\Admin\Entity::stripDefaultSign($input[DetailConstants::WORKFLOW_MAKER_ADMIN_ID]));

        unset($input[DetailConstants::WORKFLOW_MAKER_ADMIN_ID]);

        $this->app['workflow']->setMakerFromAuth(false);
        $this->app['workflow']->setWorkflowMaker($maker);
        $this->app['workflow']->setWorkflowMakerType(MakerType::ADMIN);

        $this->app['workflow']->setPermission(PermissionName::EDIT_ACTIVATE_MERCHANT);

        $this->app['basicauth']->setOrgId($merchant->getOrgId());

        $merchantDetails = (new Core)->updateActivationStatus($merchant, $input, $maker);

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

    public function merchantContactUpdatePostWorkflow(array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($input[Entity::MERCHANT_ID]);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $input)
        {
            $this->core()->changeMerchantUserMobile($merchant, $input);

            $this->core()->merchantContactUpdatePostWorkflow($merchant, $input);
        });

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

        $this->sendSelfServeSuccessAnalyticsEventToSegmentForAddOrUpdateBusinessWebsite($event);

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

    public function getBusinessCategoriesV2(): array
    {
        $categoriesMap      = BusinessCategoriesV2\BusinessParentCategory::CATEGORY_MAP;
        $parentCategories   = [];

        foreach ($categoriesMap as $parentCategory => $categories)
        {
            $categoriesMetaData = [];

            foreach ($categories as $category)
            {
                $subCategoriesMetaData = [];

                foreach (BusinessCategoriesV2\BusinessCategory::SUBCATEGORY_MAP[$category] as $subCategory)
                {
                    $subcategoryMetaDataFields = BusinessCategoriesV2\BusinessSubCategoryMetaData::SUB_CATEGORY_METADATA[$subCategory];

                    $subCategoriesMetaData[] = [
                        BusinessCategoriesV2\BusinessSubCategoryMetaData::SUBCATEGORY_NAME => $subcategoryMetaDataFields[BusinessCategoriesV2\BusinessSubCategoryMetaData::DESCRIPTION],
                        BusinessCategoriesV2\BusinessSubCategoryMetaData::SUBCATEGORY_VALUE => $subCategory,
                        Entity::ACTIVATION_FLOW => $subcategoryMetaDataFields[Entity::ACTIVATION_FLOW],
                        BusinessCategoriesV2\BusinessSubCategoryMetaData::NON_REGISTERED_ACTIVATION_FLOW => $subcategoryMetaDataFields[BusinessCategoriesV2\BusinessSubCategoryMetaData::NON_REGISTERED_ACTIVATION_FLOW],
                        BusinessCategoriesV2\BusinessSubCategoryMetaData::DISPLAY_ORDER => $subcategoryMetaDataFields[BusinessCategoriesV2\BusinessSubCategoryMetaData::DISPLAY_ORDER],
                    ];
                }

                $array_column = array_column($subCategoriesMetaData, BusinessCategoriesV2\BusinessSubCategoryMetaData::DISPLAY_ORDER);
                array_multisort($array_column, $subCategoriesMetaData);

                $categoriesMetaData[] = [
                    BusinessCategoriesV2\BusinessCategory::CATEGORY_NAME    => BusinessCategoriesV2\BusinessCategory::DESCRIPTIONS[$category],
                    BusinessCategoriesV2\BusinessCategory::CATEGORY_VALUE   => $category,
                    BusinessCategoriesV2\BusinessCategory::DISPLAY_ORDER    => BusinessCategoriesV2\BusinessCategory::DISPLAY_ORDER_LIST[$category],
                    BusinessCategoriesV2\BusinessCategory::SUBCATEGORIES    => $subCategoriesMetaData,
                ];
            }

            $array_column1 = array_column($categoriesMetaData, BusinessCategoriesV2\BusinessCategory::DISPLAY_ORDER);
            array_multisort($array_column1, $categoriesMetaData);

            $parentCategories[] =  [
                BusinessCategoriesV2\BusinessParentCategory::PARENT_CATEGORY_NAME   => BusinessCategoriesV2\BusinessParentCategory::DESCRIPTIONS[$parentCategory],
                BusinessCategoriesV2\BusinessParentCategory::PARENT_CATEGORY_VALUE  => $parentCategory,
                BusinessCategoriesV2\BusinessParentCategory::DISPLAY_ORDER          => BusinessCategoriesV2\BusinessParentCategory::DISPLAY_ORDER_LIST[$parentCategory],
                BusinessCategoriesV2\BusinessParentCategory::CATEGORIES             => $categoriesMetaData,
            ];
        }

        $array_column2 = array_column($parentCategories, BusinessCategoriesV2\BusinessParentCategory::DISPLAY_ORDER);
        array_multisort($array_column2, $parentCategories);

        return $parentCategories;
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

        $isReferrerMerchantFromPhantomOrEasy = false;

        if (!empty($referrerMerchant))
        {
            // is referred merchant on easy onboarding
            $isReferrerMerchantFromPhantomOrEasy = $this->merchant->isSignupCampaign(DDConstants::EASY_ONBOARDING);
            if ($isReferrerMerchantFromPhantomOrEasy === false)
            {
                $isReferrerMerchantFromPhantomOrEasy = Merchant\PhantomUtility::isPhantomOnBoardingWhitelistedForPartner($referrerMerchant);
            }
        }

        $presignupDetails = [];

        // Referrer Merchant check for presignup details.
        if ((empty($referrerMerchant) === true) or
            (Merchant\Entity::verifyUniqueId($referrerMerchant, false) === 0) or
            ($isReferrerMerchantFromPhantomOrEasy === true))
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
        $merchantId = $merchant->getId();
        (new Validator)->validateSignupViaChannel($input, $merchant);
        (new Validator)->validateUniqueContactMobile($input, $merchantId);

        // route request to PGOS
        // required for regular dashboard onboarding, to be removed later
        try {
            $input['merchant_id'] = $merchantId;

            $pgosProxyController = new MerchantOnboardingProxyController();

            $response = $pgosProxyController->handlePGOSProxyRequests('merchant_activation_save', $input, $merchant);

            $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                'merchant_id' => $merchantId,
                'response' => $response,
            ]);
        }
        catch (RequestsException $e) {

            if (checkRequestTimeout($e) === true) {
                $this->trace->info(TraceCode::PGOS_PROXY_TIMEOUT, [
                    'merchant_id' => $merchantId,
                ]);
            } else {
                $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                    'merchant_id' => $merchantId,
                    'error_message' => $e->getMessage()
                ]);
            }

        }
        catch (\Throwable $exception) {
            // this should not introduce error counts as it is running in shadow mode
            $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                'merchant_id' => $merchantId,
                'error_message' => $exception->getMessage()
            ]);
        }
        finally {
           unset($input['merchant_id']);
        }

        $refCode = null;

        if ((isset($input[Entity::REFERRAL_CODE]) === true) and (empty($input[Entity::REFERRAL_CODE]) === false))
        {
            $refCode = $input[Entity::REFERRAL_CODE];
        }

        unset($input[Entity::REFERRAL_CODE]);

        $referral = null;

        if (empty($refCode) === false)
        {
            $referral = (new Referral\Core)->fetchReferralByReferralCode($refCode);
        }

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $input, $referral)
        {
            $this->applyCoupon($input);

            $originProduct = $this->auth->getRequestOriginProduct();

            (new Promotion\Core)->applyPromotion(
                                                $merchant,
                                                $originProduct,
                                                Event\Constants::SIGN_UP);

            if ($merchant->isSignupCampaign(DDConstants::EASY_ONBOARDING) === false)
            {
                $this->handlePreSignUpOptionalFields($input);
            }

            if (empty($referral) === false)
            {
                $this->trace->info(TraceCode::MERCHANT_REFERRAL_APPLY_REQUEST, $input);

                $this->applyReferralPartner($referral);
            }

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

                    if((empty($merchant) === false) and (($this->app['basicauth']->getRequestOriginProduct() === ProductType::BANKING) or $this->mode === 'test'))
                    {
                        $this->app['x-segment']->sendEventToSegment(SegmentEvent::X_SIGNUP_SUCCESS, $merchant);
                    }
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

        $this->createLegalDocumentsForBanking($merchant);

        $this->createCapitalApplicationIfApplicable($merchant, $referral);

        return $this->getPreSignupDetails();
    }

    /**
     * This function consumes merchant & referral code and based on referral product, it makes merchant a submerchant and take necessary actions.
     * For capital product, we also create LOC applications if it doesnt exist already.
     *
     * @param string $refCode
     * @param Merchant\Entity $merchant
     *
     * @return void
     */
    public function applyReferralIfApplicable(string $refCode, Merchant\Entity $merchant)
    {
        $referral = (new Referral\Core)->fetchReferralByReferralCode($refCode);

        if (empty($referral) === true)
        {
            return;
        }

        switch ($referral->getProduct())
        {
            case Product::CAPITAL:

                $flag = (new CapitalSubmerchantUtility())->isCapitalReferralCodeApplicable($merchant, $referral);

                if($flag === true)
                {
                    $this->applyReferralPartner($referral, $merchant);

                    $this->createCapitalApplicationIfApplicable($merchant, $referral);
                }
                break;

            default:
                break;
        }
    }

    /**
     * Create Capital Application for a submerchant if referral code is present,
     * the referred product is Capital and the capital partnership experiment is enabled for
     * the referring partner.
     *
     * @param Merchant\Entity $subMerchant
     * @param ReferralEntity|null $referral
     *
     * @return void
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     */
    private function createCapitalApplicationIfApplicable(Merchant\Entity $subMerchant, Referral\Entity $referral = null): void
    {
        // If referral code is present, but there is no referral entity associated against it
        // we need not create an application for submerchant.
        // This could happen in cases where the referral code is a typo or when the merchant
        // who referred is no longer a partner
        if (empty($referral) === true)
        {
            return;
        }

        $referralProduct = $referral->getProduct() ?? Product::PRIMARY;

        if ($referralProduct === Product::CAPITAL)
        {
            $isCapitalPartnershipExpEnabled = (new CapitalSubmerchantUtility())->isCapitalPartnershipEnabledForPartner($referral->getMerchantId());

            if ($isCapitalPartnershipExpEnabled === true)
            {
                $partner = $this->repo->merchant->findOrFailPublic($referral->getMerchantId());

                CapitalSubmerchantUtility::addTagAndAttributeForCapitalSubmerchant($partner->getId(), $subMerchant);

                $productIds = CapitalSubmerchantUtility::getLOSProductIds();

                $locProductId = $productIds[Constants::CAPITAL_LOC_EMI_PRODUCT_NAME];

                CapitalSubmerchantUtility::createCapitalApplicationForSubmerchant(
                    $subMerchant,
                    $partner,
                    [
                        Constants::LEAD_SOURCE    => "Partner",
                        Constants::LEAD_SOURCE_ID => $partner->getId(),
                        Constants::SOURCE_DETAILS => $partner->getName(),
                        Constants::PRODUCT_ID     => $locProductId
                    ],
                    PartnerConstants::REFERRAL
                );
            }
        }
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

        (new Coupon\Core)->apply($merchant, $couponInput,false);

        $this->trace->count(Merchant\Metric::SIGNUP_COUPON_TOTAL);

        unset($input[Entity::COUPON_CODE]);
    }

    /**
     * @param ReferralEntity $referral
     * @param Merchant\Entity|null $merchant
     */
    private function applyReferralPartner(Referral\Entity $referral, Merchant\Entity $merchant = null)
    {
        $subMerchant = $this->app['basicauth']->getMerchant() ?? $merchant;

        $referralProduct = $referral->getProduct() ?? Product::PRIMARY;

        $isCapitalLocSignupPageVisited = false;

        if ($referralProduct == Product::CAPITAL)
        {
            $actualReferralProduct = $referralProduct;

            $referralProduct = Product::BANKING;

            $this->trace->info(
                TraceCode::PARTNER_REFERRAL_FOR_CAPITAL,
                [
                    "referral_code"           => $referral->getReferralCode(),
                    "referral_product"        => $referralProduct,
                    "actual_referral_product" => $actualReferralProduct,
                    "partner_id"              => $referral->getMerchantId(),
                    "submerchant_id"          => $subMerchant->getId(),
                ]
            );

            $isCapitalPartnershipExpEnabled = (new CapitalSubmerchantUtility())->isCapitalPartnershipEnabledForPartner($referral->getMerchantId());

            if ($isCapitalPartnershipExpEnabled === false)
            {
                return;
            }

            $utmParams = [];
            (new User\Service)->addUtmParameters($utmParams);

            $isCapitalLocSignupPageVisited = ((isset($utmParams['first_page']) and ($utmParams['first_page'] === User\Constants::CAPITAL_LOC_SIGNUP_STATIC_PAGE))
                or (isset($utmParams['final_page']) and ($utmParams['final_page'] === User\Constants::CAPITAL_LOC_SIGNUP_STATIC_PAGE))
                or (isset($utmParams['website']) and ($utmParams['website'] === User\Constants::CAPITAL_LOC_SIGNUP_STATIC_PAGE)));
        }

        $requestProduct = $this->auth->getRequestOriginProduct();

        if ($referralProduct === $requestProduct or ( $referral->getProduct() == Product::CAPITAL and $isCapitalLocSignupPageVisited ))
        {
            $mappingInput = [
                'partner_id'     => $referral->getMerchantId(),
                'source'         => PartnerConstants::REFERRAL,
                'actual_product' => $referral->getProduct() ?? Product::PRIMARY
            ];

            $this->applyPartnerSubMerchantMapping($subMerchant, $mappingInput, $referralProduct);
        }
    }

    private function applyPartnerSubMerchantMapping($subMerchant, $input, $product)
    {
        $partnerId = $input[Merchant\Constants::PARTNER_ID];

        $partner = $this->repo->merchant->findOrFailPublic($partnerId);

        $merchantCore = new Merchant\Core;

        $role = null;

        if ($product === Product::BANKING)
        {
            // In current system when subM is coming via referral link, we assign referred application.
            // And in the current system for 'referred' applications partner user is not added to subM for banking product for aggregator / fully managed partners, but is added for PG
            // Now going forward, we will only use one type of app for subM for a given partner type, which will be 'managed' applications for aggregator / fully managed partners.
            // To maintain current behaviour, we will not create user for X by default.
            // But also with the migration to view_only role for banking product, we will have to attach a view_only role to user going forward and to keep things sync and for phased rollout, we are reusing the same experiment with which we are changing the role to view_only, when subM is created via partner dashboard
            $role = User\Role::VIEW_ONLY;
        }

        $merchantCore->createPartnerSubmerchantAccessMap($partner, $subMerchant, null, $role);

        $linkedAccount = false;

        // update merchant pricing plan to the one specified by partner in partner config if applicable
        $merchantCore->assignSubMerchantPricingPlan($partner, $subMerchant, $linkedAccount);

        if ($partner->isFeatureEnabled(FeatureConstants::SKIP_SUBM_ONBOARDING_COMM) === true)
        {
            $this->app->hubspot->skipMerchantOnboardingComm($subMerchant->getEmail());
        }

        if ($input['source'] === PartnerConstants::PHANTOM)
        {
            $partnerLinkingData = [
                Constants::PHANTOM_ONBOARDING => true
            ];

            $this->app['diag']->trackOnboardingEvent(EventCode::PARTNER_LINKING_CONSENT_RESPONSE_RESULT,
                                                     $partner, null, $partnerLinkingData);
        }

        $isSignUpFlow = \Request::all()[Merchant\Constants::PHANTOM_SIGNUP] ?? true;

        if ($isSignUpFlow)
        {
            $this->sendSubMerchantSignupEvents($partner, $subMerchant, $input, $product);
        }
    }

    private function sendSubMerchantSignupEvents($partner, $subMerchant, $input, $product)
    {
        $data = [
            'status'       => 'success',
            'merchant_id'  => $subMerchant->getId(),
            'partner_id'   => $partner->getId(),
            'source'       => $input['source'],
            'product_group'=> $product,
            'actual_product' => $input['actual_product']
        ];

        $merchantCore = new Merchant\Core;

        $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIP_SUBMERCHANT_SIGNUP,
                                                 $partner, null,
                                                 $data);

        $this->app->hubspot->trackSubmerchantSignUp($partner->getEmail());

        $dimension = [
            'partner_type' => $partner->getPartnerType(),
            'source'       => $input['source']
        ];

        $this->trace->count(PartnerMetric::SUBMERCHANT_CREATE_TOTAL, $dimension);
        $merchantCore->pushSettleToPartnerSubmerchantMetrics($partner->getId(), $subMerchant->getId());

        $merchantCore->sendPartnerLeadInfoToSalesforce($subMerchant->getId(), $partner->getId(), $product);
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
                            ->findByOrgIdAndPermission($orgId, PermissionName::EDIT_ACTIVATE_MERCHANT);

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
     * @param bool $isSelfServe
     *
     * @return mixed
     * @throws Throwable
     */
    public function putAdditionalWebsite($merchantId, $input, $isSelfServe = false)
    {
        $core = new Core();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchantName =  $merchant->getName();

        $merchantDetails = $core->getMerchantDetails($merchant);

        $response = $core->addAdditionalWebsiteDetails($merchantDetails, $input);

        if ($isSelfServe === true)
        {
            [$segmentEventName, $segmentProperties] = $core->pushSelfServeSuccessEventsToSegment();

            $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Additional Website - App Url Updated';

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, $segmentEventName
            );

            $args = [
                Constants::MERCHANT         => $merchant,
                DashboardEvents::EVENT      => DashboardEvents::ADD_ADDITIONAL_WEBSITE_SUCCESS,
                Constants::PARAMS           => [
                    DashboardNotificationConstants::MERCHANT_NAME      => $merchantName,
                    DashboardNotificationConstants::ADDITIONAL_WEBSITE => $input[Entity::ADDITIONAL_WEBSITE]
                ]
            ];

            (new DashboardNotificationHandler($args))->send();
        }
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

        $fileIdentify = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $fileIdentifier = preg_replace("/[^\w\-\.]/",'', $fileIdentify);

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

    /**
     * @param $payload
     *
     * @throws \Exception
     */
    public function storeTerminalProcurementBannerStatus($payload)
    {
        switch (strtoupper($payload['payment_method']))
        {
            case DEConstants::UPI:
                $this->storeTerminalProcurementBannerStatusForUPI($payload);
                break;
            default:
                throw new Exception\LogicException(
                    'Invalid payment method passed for displaying terminal procurement banner');
        }
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

    public function postAppsflyerAttributionDetails($input)
    {
        $this->trace->info(TraceCode::APPSFLYER_ATTRIBUTION_DETAILS, $input);

        $appsflyerId = $input['appsflyer_id'] ?? '';

        if(empty($appsflyerId) === true)
        {
            $this->trace->info(TraceCode::APPSFLYER_ATTRIBUTION_DETAILS_ERROR, [
                'data'  => $input,
                'error' => 'missing appsflyer id'
            ]);

            return;
        }

        $this->app['rzp.mode'] = Mode::LIVE;
        $this->core()->setModeAndDefaultConnection(Mode::LIVE);

        $attributionDetails = $this->repo->app_attribution_detail->fetchByAppsflyerId($appsflyerId);

        if(empty($attributionDetails) === false)
        {
            $this->trace->info(TraceCode::APPSFLYER_ATTRIBUTION_DETAILS_ERROR, [
                'data'  => $input,
                'error' => 'attribution details already present'
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

        $merchantId = $userDeviceDetails->getMerchantId();

        $userId = $userDeviceDetails->getUserId();

        $segmentProperties = [];

        foreach ($input as $key => $value)
        {
            $segmentProperties["app_" . $key] = $value;
        }

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        try
        {
            (new AttributionCore())->storeAttributionFromAppsflyer($merchantId, $userId, $input);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::APPSFLYER_ATTRIBUTION_DETAILS_ERROR, [
                'data'          => $input,
                'merchant_id'   => $merchantId,
                'user_id'       => $userId,
                'error'         => $e->getMessage()
            ]);
        }

        $segmentProperties[SegmentAnalytics\Constants::EVENT_MILESTONE] = SegmentEvent::IDENTIFY_APP_ATTRIBUTION;

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
            [PermissionName::EDIT_MERCHANT_GSTIN_DETAIL]
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
        $isAddAction = $this->isAddGstinSelfServeAction($this->merchant->merchantDetail);

        $traceCode = ($isAddAction) ? TraceCode::GSTIN_ADD_SELF_SERVE_INITIATED : TraceCode::GSTIN_UPDATE_SELF_SERVE_INITIATED;

        $this->trace->info($traceCode, [
            Entity::GSTIN => $input[Entity::GSTIN]
        ]);

        $this->validator->validateInput('gstin_self_serve', $input);

        // only activated merchants can update gstin detail
        if ($isAddAction === false)
        {
            $this->merchant->getValidator()->validateIsActivated($this->merchant);
        }

        $payload = $this->getUpdateGstinSelfServeBvsPayload($input);

        $fileId = $this->uploadGstInCertificateForGstinSelfServe(
            $input[DetailConstants::GSTIN_SELF_SERVE_CERTIFICATE],
            $this->merchant->merchantDetail
        );

        unset($input[DetailConstants::GSTIN_SELF_SERVE_CERTIFICATE]);

        $input = array_merge($input, [
            Merchant\Entity::MERCHANT_ID               => $this->merchant->getId(),
            DetailConstants::GSTIN_CERTIFICATE_FILE_ID => $fileId,
            DEConstants::IS_ADD_GSTIN_OPERATION        => $isAddAction,
        ]);

        $this->storeGstinSelfServeInput($input);

        $validation = (new BvsCore($this->merchant, $this->merchant->merchantDetail))->verify($this->merchant->getId(), $payload);

        if ($validation === null)
        {
            throw new Exception\ServerErrorException('bvs validation create failed', ErrorCode::SERVER_ERROR);
        }

        $input[BvsConstant::VALIDATION_ID]  = $validation->getValidationId();

        $response = $input;

        switch ($validation->getValidationStatus())
        {
            case BvsValidationConstants::SUCCESS:
                $response[Constants::SYNC_FLOW] = true;
                $response[Constants::WORKFLOW_CREATED] = false;
                break;
            case BvsValidationConstants::FAILED:
                $response[Constants::SYNC_FLOW] = true;
                $response[Constants::WORKFLOW_CREATED] = true;
                break;
            default:
                $response[Constants::SYNC_FLOW] = false;
                $response[Constants::WORKFLOW_CREATED] = null;
        }

        $traceCode = ($isAddAction) ? TraceCode::GSTIN_ADD_SELF_SERVE_VALIDATION_CREATED : TraceCode::GSTIN_UPDATE_SELF_SERVE_VALIDATION_CREATED;

        $this->trace->info($traceCode, [
            $validation->toArrayPublic(),
            Constants::SYNC_FLOW => $response[Constants::SYNC_FLOW],
            Constants::WORKFLOW_CREATED => $response[Constants::WORKFLOW_CREATED]
        ]);

        return $response;
    }

    protected function pushBvsResultToSegmentForGstinSelfServe(Entity $detail, Merchant\BvsValidation\Entity $validation)
    {
        try
        {
            $input = $this->getGstinSelfServeInputFromCache($detail->getMerchantId());

            $ruleExecution = $validation->getRuleExecutionList();

            $segmentEventName = ($input[DEConstants::IS_ADD_GSTIN_OPERATION]) ? SegmentEvent::ADD_GSTIN_BVS_RESULT : SegmentEvent::EDIT_GSTIN_BVS_RESULT;

            $segmentProperties = [];

            $segmentProperties['result'] = $validation->getValidationStatus();

            $segmentProperties['failure_reason'] = $validation->getErrorCode();

            if ((isset($ruleExecution) === true) and
                (isset($ruleExecution[0]) === true))
            {
                $segmentProperties['name_match_percentage_bvs'] = [
                    $ruleExecution[0]['rule_execution_result']['remarks'],
                    $ruleExecution[1]['rule_execution_result']['remarks']
                ];
            }
            else
            {
                $segmentProperties['name_match_percentage_bvs'] = [];
            }

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $detail->merchant, $segmentProperties, $segmentEventName);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::GSTIN_SEGMENT_EVENT_PUSH_FAIL
            );
        }
    }

    public function handleGstinSelfServeCallback(Entity $detail, Merchant\BvsValidation\Entity $validation)
    {
        $this->trace->info(TraceCode::GSTIN_SELF_SERVE_BVS_CALLBACK_RECEIVED, $validation->toArrayPublic());

        $this->pushBvsResultToSegmentForGstinSelfServe($detail, $validation);

        switch ($validation->getValidationStatus())
        {
            case 'success':
                $this->handleGstinSelfServeCallbackSuccess($detail, $validation->getValidationId());
                break;
            default:
                $this->handleGstinSelfServeCallbackFailure($detail);
        }

        $this->deleteGstinSelfServeInput($detail->getId());
    }

    protected function pushInvoicesResultToSegmentForGstinSelfServe(Entity $detail, array $result)
    {
        $segmentEventName = SegmentEvent::INVOICES_CREATE_RESULT;

        $segmentProperties = [];

        $segmentProperties['result'] = $result;

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $detail->merchant, $segmentProperties, $segmentEventName);
    }

    /**
     * @param Entity $detail
     */
    private function handleGstinSelfServeCallbackSuccess(Entity $detail, string $validationId): void
    {
        $input = $this->getGstinSelfServeInputFromCache($detail->getId());

        $oldGstin = $detail->getGstin();

        if (isset($input[Entity::GSTIN]) === false)
        {
            throw new Exception\ServerErrorException(
                'Failed to get gstin data from cache',
                ErrorCode::SERVER_ERROR);
        }

        $isAddOperation = $input[DetailConstants::IS_ADD_GSTIN_OPERATION];

        try
        {
            $registeredBusinessAddressDetails =  $this->getRegisteredBusinessAddressFromBvsForGstinUpdateSelfServe($detail->getMerchantId(), $validationId);

            // This method is used for backfilling(storing in S3) the PG merchant invoices PDFs for the months on or before Dec-2020 before the merchant details are updated.
            if (($isAddOperation === false) and
                ($detail->getCreatedAt() < strtotime("01-01-2021")))
            {
                $result = (new MerchantInvoiceService())->
                backFillMerchantInvoiceB2cPDFs([$detail->getMerchantId()], "7", "2017", "2020", "12");

                $this->pushInvoicesResultToSegmentForGstinSelfServe($detail, $result);

                $this->trace->info(TraceCode::UPLOAD_FILE_DETAILS, [$result]);
            }

            $detail->edit(
                array_merge([
                        Entity::GSTIN => $input[Entity::GSTIN]
                    ],
                    $registeredBusinessAddressDetails
                )
            );
        }

        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::GSTIN_SELF_SERVE_WORKFLOW_RAISED_AFTER_BVS_SUCCESS, []);

            $this->handleGstinSelfServeCallbackFailure($detail);

            return;
        }

        $this->repo->merchant_detail->saveOrFail($detail);

        [$segmentEventName, $segmentProperties] = $this->core->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'GST Updated';

        $segmentProperties[SegmentConstants::IS_WORKFLOW] = 'false';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $detail->merchant, $segmentProperties, $segmentEventName
        );

        // if any previous rejected workflow of gstin exist : do not show rejection reason for any old rejected workflow
        $this->stopShowingRejectionReasonForGstInSelfServe($detail->getId(), $detail->getEntity(), $input[DEConstants::IS_ADD_GSTIN_OPERATION]);

        $this->sendNotificationForGstinUpdatedSelfServe($isAddOperation, true, $detail->merchant);

        $traceCode = ($isAddOperation) ? TraceCode::GSTIN_ADDED_WITH_REGISTERED_ADDRESS : TraceCode::GSTIN_UPDATED_WITH_REGISTERED_ADDRESS;

        $this->trace->info($traceCode, [
            Constants::OLD_GSTIN  => $oldGstin,
            Constants::NEW_GSTIN  => $input[Entity::GSTIN],
        ]);
    }

    public function getRegisteredBusinessAddressFromBvsForGstinUpdateSelfServe($merchantId, $validationId)
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
            Entity::BUSINESS_REGISTERED_CITY    => $this->getCityFromRegisteredAddress(trim($components[$size - 3])),
            Entity::BUSINESS_REGISTERED_ADDRESS => trim(implode(',', array_slice($components, 0, $size - 3)))
        ];
    }

    protected function getCityFromRegisteredAddress($cityName)
    {
        $cityName = preg_replace('/[^A-Za-z ]/', ' ', $cityName);

        $cityName = preg_replace('/\s+/', ' ', $cityName);

        return $cityName;
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

    protected function pushWorkflowCreatedEventToSegmentForGstinSelfServe(Entity $detail, array $input)
    {
        $segmentProperties = [];

        $segmentEventName = ($input[DEConstants::IS_ADD_GSTIN_OPERATION]) ? SegmentEvent::ADD_GSTIN_WORKFLOW_CREATED : SegmentEvent::EDIT_GSTIN_WORKFLOW_CREATED;

        $segmentProperties['workflow_type'] = ($input[DEConstants::IS_ADD_GSTIN_OPERATION]) ? 'Gstin Add' : 'Gstin Edit';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $detail->merchant, $segmentProperties, $segmentEventName);
    }

    protected function isAddGstinSelfServeAction(Entity $merchantDetail)
    {
        return empty($merchantDetail->getGstin()) === true;
    }

    protected function handleGstinSelfServeCallbackFailure(Entity $oldDetailEntity)
    {
        $input = $this->getGstinSelfServeInputFromCache($oldDetailEntity->getId());

        if (isset($input[Entity::GSTIN]) === false)
        {
            throw new Exception\ServerErrorException(
                'Failed to get gstin data from cache',
                ErrorCode::SERVER_ERROR);
        }

        $newDetailsEntity = clone $oldDetailEntity;

        $newDetailsEntity->edit([
            Entity::GSTIN => $input[Entity::GSTIN],
        ]);

        $isAddOperation = $input[DetailConstants::IS_ADD_GSTIN_OPERATION];

        $permissionName = ($isAddOperation) ? PermissionName::EDIT_MERCHANT_GSTIN_DETAIL : PermissionName::UPDATE_MERCHANT_GSTIN_DETAIL;

        $this->app['workflow']
            ->setPermission($permissionName)
            ->setRouteName(DetailConstants::GSTIN_UPDATE_SELF_SERVE_ROUTE_NAME)
            ->setRouteParams([])
            ->setInput($input)
            ->setWorkflowMakerType(MakerType::MERCHANT)
            ->setWorkflowMaker($oldDetailEntity->merchant)
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
            $oldDetailEntity,
            $permissionName
        );

        $this->pushWorkflowCreatedEventToSegmentForGstinSelfServe($oldDetailEntity, $input);

        $traceCode = ($isAddOperation) ? TraceCode::GSTIN_ADD_WORKFLOW_CREATED : TraceCode::GSTIN_UPDATE_WORKFLOW_CREATED;

        $this->trace->info($traceCode, [
            Constants::OLD_GSTIN  => $oldDetailEntity->getGstin(),
            Constants::NEW_GSTIN  => $input[Entity::GSTIN],
        ]);
    }

    protected function stopShowingRejectionReasonForGstInSelfServe($entityId, $entity, $isAddOperation)
    {
        $permissionName = ($isAddOperation) ? PermissionName::EDIT_MERCHANT_GSTIN_DETAIL : PermissionName::UPDATE_MERCHANT_GSTIN_DETAIL;

        $action = (new WorkFlowActionCore())->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [$permissionName]
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
        $isAddOperation = $input[DetailConstants::IS_ADD_GSTIN_OPERATION];

        $merchant = $this->repo->merchant->findOrFailPublic($input[Merchant\Entity::MERCHANT_ID]);

        $merchantDetails = $merchant->merchantDetail;

        $oldGstin = $merchantDetails->getGstin();

        // This method is used for backfilling(storing in S3) the PG merchant invoices PDFs for the months on or before Dec-2020 before the merchant details are updated.
        if (($isAddOperation === false) and
            ($merchantDetails->getCreatedAt() < strtotime("01-01-2021")))
        {
            $result = (new MerchantInvoiceService())->
            backFillMerchantInvoiceB2cPDFs([$merchant->getId()], "7", "2017", "2020", "12");

            $this->pushInvoicesResultToSegmentForGstinSelfServe($merchantDetails, $result);

            $this->trace->info(TraceCode::UPLOAD_FILE_DETAILS, [$result]);
        }

        $merchantDetails->edit([
                Entity::GSTIN => $input[Entity::GSTIN],
            ]
        );

        $this->repo->merchant_detail->saveOrFail($merchantDetails);

        [$segmentEventName, $segmentProperties] = $this->core->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'GST Updated';

        $segmentProperties[SegmentConstants::IS_WORKFLOW] = 'true';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $segmentProperties, $segmentEventName
        );

        $traceCode = ($isAddOperation) ? TraceCode::GSTIN_ADD_WORKFLOW_APPROVED : TraceCode::GSTIN_UPDATE_WORKFLOW_APPROVED;

        $this->trace->info($traceCode, [
            Constants::OLD_GSTIN  => $oldGstin,
            Constants::NEW_GSTIN  => $input[Entity::GSTIN],
        ]);

        $this->sendNotificationForGstinUpdatedSelfServe($isAddOperation, false, $merchant);
    }

    protected function storeGstinSelfServeInput($input)
    {
        $cacheKey = $this->getGstinSelfServeInputCacheKey();

        $this->trace->info(TraceCode::GSTIN_SELF_SERVE_SET_CACHE_DATA, [
            DEConstants::CACHE_KEY   => $cacheKey,
            DEConstants::CACHE_DATA  => $input,
        ]);

        $this->app['cache']->put($cacheKey, $input, DEConstants::GSTIN_SELF_SERVE_INPUT_CACHE_TTL);
    }

    protected function deleteGstinSelfServeInput($merchantId)
    {
        $cacheKey = $this->getGstinSelfServeInputCacheKey($merchantId);

        $this->trace->info(TraceCode::GSTIN_SELF_SERVE_DELETE_CACHE_DATA, [
            DetailConstants::CACHE_KEY => $cacheKey,
        ]);

        $this->app['cache']->delete($cacheKey);
    }

    protected function getGstinSelfServeInputCacheKey($merchantId = null)
    {
        if (is_null($merchantId) === true)
        {
            $merchantId = $this->merchant->getId();
        }

        return sprintf(DEConstants::GSTIN_SELF_SERVE_INPUT_CACHE_KEY_FORMAT, $merchantId);
    }

    public function getGstinSelfServeInputFromCache($merchantId = null)
    {
        $cacheKey = $this->getGstinSelfServeInputCacheKey($merchantId);

        $data = $this->app['cache']->get($cacheKey);

        $this->trace->info(TraceCode::GSTIN_SELF_SERVE_GET_CACHE_DATA, [
            DetailConstants::CACHE_KEY  => $cacheKey,
            DetailConstants::CACHE_DATA => $data,
        ]);

        return $data;
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

    protected function addGstinCertificateUrlInWorkflowCommentForGstinSelfServe($fileId, $merchantDetail, $permissionName)
    {
        $workFlowAction = (new WorkFlowActionCore())->fetchOpenActionOnEntityOperation($merchantDetail->getId(),
            $merchantDetail->getEntity(),
            $permissionName
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

            $this->trace->info(TraceCode::GSTIN_CERTIFICATE_URL_ADDED_IN_WORKFLOW_COMMENT, [
                'workflow_action' => $workFlowAction->toArrayPublic(),
            ]);
        }
    }

    protected function sendNotificationForGstinUpdatedSelfServe($isAddOperation, $isBvsValidationSuccessEvent, $merchant)
    {
        $event = '';

        if ($isBvsValidationSuccessEvent === true)
        {
            $event = ($isAddOperation) ?  DashboardEvents::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS : DashboardEvents::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS;
        }
        else
        {
            $event = ($isAddOperation) ? DashboardEvents::GSTIN_ADDED_ON_WORKFLOW_APPROVE : DashboardEvents::GSTIN_UPDATED_ON_WORKFLOW_APPROVE;
        }

        $merchantDetails = $merchant->merchantDetail;

        $args = [
            Constants::MERCHANT         => $merchant,
            DashboardEvents::EVENT      => $event,
            Constants::PARAMS           => [
                Entity::GSTIN                       => $merchantDetails[Entity::GSTIN],
                Entity::BUSINESS_REGISTERED_ADDRESS => $merchantDetails[Entity::BUSINESS_REGISTERED_ADDRESS],
                Entity::BUSINESS_REGISTERED_PIN     => $merchantDetails[Entity::BUSINESS_REGISTERED_PIN],
                Entity::BUSINESS_REGISTERED_CITY    => $merchantDetails[Entity::BUSINESS_REGISTERED_CITY],
                Entity::BUSINESS_REGISTERED_STATE   => $merchantDetails[Entity::BUSINESS_REGISTERED_STATE],
                DetailConstants::GSTIN_OPERATION    => ($isAddOperation) ? DetailConstants::ADDED : DetailConstants::UPDATED
            ]
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
            return ($activationStatusChangeLog[StateChangeEntity::NAME] === Status::UNDER_REVIEW or
                $activationStatusChangeLog[StateChangeEntity::NAME] === Status::ACTIVATED_MCC_PENDING);
        }));

        $this->trace->info(
            TraceCode::SHOW_CREATE_TICKET_POPUP_DEBUG,
            [
                'action_state_logs after filter' => $activationStatusChangeLogs,
            ]);

        if (empty($activationStatusChangeLogs) === false)
        {
            if (count($activationStatusChangeLogs) == 1)
            {
                return $activationStatusChangeLogs[0][StateChangeEntity::CREATED_AT];
            }

            return min($activationStatusChangeLogs[0][StateChangeEntity::CREATED_AT], $activationStatusChangeLogs[1][StateChangeEntity::CREATED_AT]);
        }

        return 0;
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
        return $this->core()->getBvsValidationArtefactDetails($merchantId,$validationArtefact,$validationId);
    }

    /**
     * This function is used for edit contact details of an merchant and login mobile number of owner user
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     * @throws BadRequestException
     * @throws Throwable
     */
    public function updateMerchantContact(string $id, array $input)
    {
        $this->validator->validateInput('update_contact_and_login_mobile', $input);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        // check if the merchant has more or zero users as [OWNERS] with the old contact mobile
        $this->validator->validateUniqueMerchantOwnerUserForMobile($merchant, $input[DetailConstants::OLD_CONTACT_NUMBER]);

        // add validator for contact already exists
        $this->validator->validateMerchantUniqueNumberExcludingCurrentMerchantDetails($id, $input[DetailConstants::NEW_CONTACT_NUMBER]);

        return (new Core)->updateMerchantContact($merchant, $input);
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

    public function putAddAdditionalWebsiteSelfServePostWorkflowApproval(array $input, bool $isSelfServe)
    {
        $merchantId = $input[Constants::MERCHANT_ID];

        $newUrl = ($input[DetailConstants::URL_TYPE] === DetailConstants::URL_TYPE_WEBSITE) ? $input[DetailConstants::ADDITIONAL_WEBSITE_MAIN_PAGE] : $input[DetailConstants::ADDITIONAL_APP_URL];

        $additionalWebsite = [Entity::ADDITIONAL_WEBSITE => $newUrl];

        $response = $this->putAdditionalWebsite($merchantId, $additionalWebsite, $isSelfServe);

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
            DifferEntity::ACTION_ID               => $actionEntity->getId(),
            DifferEntity::WORKFLOW_OBSERVER_DATA  => $differEntity[DifferEntity::WORKFLOW_OBSERVER_DATA] ?? [],
        ]);

        if(isset($differEntity[DifferEntity::WORKFLOW_OBSERVER_DATA][WorkflowObserver\Constants::APPROVED_TRANSACTION_LIMIT]) == true)
        {
            return $differEntity[DifferEntity::WORKFLOW_OBSERVER_DATA][WorkflowObserver\Constants::APPROVED_TRANSACTION_LIMIT];
        }
    }

    public function getAgentApprovedInternationalTransactionLimit(Merchant\Entity $merchant)
    {

        $actionEntity = (new WorkFlowActionCore())->fetchOpenActionOnEntityOperation($merchant->getMerchantId(),
            $merchant->getEntity(),
            PermissionName::INCREASE_INTERNATIONAL_TRANSACTION_LIMIT,
            $merchant->getOrgId()
        )->first();

        $differEntity = (new DifferCore)->fetchRequest($actionEntity->getId());

        if (empty($differEntity) === true)
        {
            throw new Exception\ServerErrorException('Workflow action differ entity not found',
                ErrorCode::SERVER_ERROR);
        }

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

    /**
     * @param string $merchantId
     *
     * @return array
     */
    public function getEnhancedActivationDetails(string $merchantId)
    {
        $artefacts = [Constant::CIN,Constant::GSTIN,Constant::LLPIN];
        $result=[];

        foreach ($artefacts as $artefact)
        {
            try
            {
                $result[$artefact] = $this -> core-> getBvsValidationArtefactDetails($merchantId,$artefact);
            }catch(\Exception $e)
            {
                $err = [];
                $err['merchant_details'] = $merchantId;
                $err['message'] = 'No '.$artefact .' Details Found';
                $err['exception'] = $e;
                $this->trace->debug(TraceCode::DEBUG_LOGGING,$err);
            }
        }
        return $result;
    }

    public function getBusinessTypes()
    {
        $core = new Core();

        $merchant_id=null;

        if($this->ba->isAdminAuth()===false)
        {
            $merchant_id = $this->merchant->getId();
        }

        return $core->getBusinessTypes($merchant_id);

    }

    public function getMerchantSupportedPlugins()
    {

        $merchant = $this->merchant;

        return (new Core())->getMerchantSupportedPlugins($merchant);

    }

    public function merchantIdentityVerification(array $input)
    {
        return (new Core())->merchantIdentityVerification($input);
    }

    public function processIdentityVerificationDetails(array $input)
    {
        return (new Core())->processIdentityVerificationDetails($input);
    }

    public function getMerchantInfo($merchant_id)
    {
        $core = new Core();

        return $core->getMerchantInfo($merchant_id);
    }

    public function getMerchantPlugin($merchant_id)
    {
        return (new Core())->getMerchantPlugin($merchant_id);
    }

    /**
     * This is used as part of ITF test cases to mock penny testing validation events from BVS.
     * Sample Input
     * [
        'data' => [
            'validation_id' => 'JCWowgGD7ccKNL',
            'error_code' => 'INPUT_DATA_ISSUE',
            'error_description' => 'invalid data submitted',
            'status' => 'failed',
        ],
    ]
     * @param array $input
     * @return boolean
     * */
    public function mockBvsValidationEvent($input)
    {
        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_BVS_EVENTS, $input, $this->mode);

        return true;
    }

    public function checkIfConsentsPresent($merchantId, $validDocTypes)
    {
        $consentDetails = $this->repo->merchant_consents->getConsentDetailsForMerchantIdAndConsentFor($merchantId, $validDocTypes);

        if($consentDetails === null)
        {
            return false;
        }

        return true;
    }

    public function storeConsents(string $merchantId, array $input, string $userId = null, string $status = ConsentConstant::PENDING, string $requestId = null)
    {
        $documentDetailsInput = $input[DEConstants::DOCUMENTS_DETAIL] ?? null;

        if ($documentDetailsInput === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, [
                'error description' => 'Legal documents are not present in the request. '
            ]);
        }

        foreach ($documentDetailsInput as $documentDetailInput)
        {
            $merchantConsentDetail = new MerchantConsentDetails();

            $createdAt = $input[DEConstants::DOCUMENTS_ACCEPTANCE_TIMESTAMP] ?? Carbon::now()->getTimestamp();

            $id = (new Entity)->generateUniqueIdFromTimestamp($createdAt);

            $merchantConsentDetailInput = [
                'id'         => $id,
                'url'        => $documentDetailInput[DEConstants::URL],
                'created_at' => $createdAt
            ];

            $merchantConsent = new MerchantConsent();

            //This to know the milestone at which consents are stored and this value
            // should be unique for each merchant to avoid duplicate submission of same legal document.
            $consentType = $input[Entity::ACTIVATION_FORM_MILESTONE] ? ($input[Entity::ACTIVATION_FORM_MILESTONE] . '_' . $documentDetailInput[DEConstants::TYPE]) : $documentDetailInput[DEConstants::TYPE];

            $metadata = [
                ConsentConstant::IP_ADDRESS => $input[DEConstants::IP_ADDRESS] ?? $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip(),
                ConsentConstant::USER_AGENT => $this->app['request']->header('X-User-Agent') ?? $this->app['request']->header('User-Agent') ?? null,
            ];

            $merchantConsentInput = [
                'merchant_id' => $merchantId,
                'consent_for' => $consentType,
                'details_id'  => $id,
                'status'      => $status,
                'created_at'  => $createdAt,
                'metadata'    => $metadata,
                'user_id'     => $userId ?? $this->app['request']->header(RequestHeader::X_DASHBOARD_USER_ID),
                'entity_id'   => $input[MerchantConsent::ENTITY_ID],
                'entity_type' => $input[MerchantConsent::ENTITY_TYPE],
                'id'          => (new Entity)->generateUniqueId(),
                'request_id'  => $requestId
            ];

            try
            {
                $this->repo->transaction(function() use ($merchantConsentDetail, $merchantConsent, $merchantConsentDetailInput, $merchantConsentInput) {

                    $merchantConsentDetail->build($merchantConsentDetailInput);

                    $this->repo->merchant_consent_details->saveOrFail($merchantConsentDetail);

                    $merchantConsent->build($merchantConsentInput);

                    $this->repo->merchant_consents->saveOrFail($merchantConsent);

                });
            }
            catch (LogicException $e)
            {
                throw new LogicException($e->getMessage(), $e->getCode());
            }
        }
    }

    /**
     * @param $documentDetailInput
     * @return string|string[]|null
     */
    public function getFileContentInHtml($url, array &$mapConsentUrlToFileContent = [])
    {
        // if html content is fetched in the request for the same url will store in array and return
        // rather than fetching it again

        if (empty($mapConsentUrlToFileContent[$url]) === false)
        {
            return $mapConsentUrlToFileContent[$url];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $result = curl_exec($ch);

        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            echo $error_msg;
        }

        $unescapeString = stripcslashes($result);

        if (empty($unescapeString) === true)
        {
            $this->trace->info(
                TraceCode::FETCH_HTML_CONTENT_FAILURE,
                [
                    'status_code' => $http_status,
                    'url'         => $url,
                    'result'      => $result
                ]
            );
        }

        $dom = new DOMDocument("1.0", "utf-8");
        $dom->formatOutput = true;
        libxml_use_internal_errors(true);
        $dom->loadHTML($unescapeString);
        libxml_clear_errors();

        $content_body =  $this->get_match($unescapeString);

        if($content_body === null)
        {
            $this->trace->info(
                TraceCode::STORAGE_CONSENT_FETCH_DOCUMENT,
                [
                    'message' => 'Could not fetch correct body of the document'
                ]);
        }

        $content_body_str = (string)$content_body;

        //regex to remove self closing tags
        $cleaned_content_body_str = preg_replace('/<(path|img|xml|br|hr)(.*?)>/s', " ", $content_body_str);

        $final_content_body = '<html lang="en"><head><title></title></head><body>' . $cleaned_content_body_str . '</body></html>';

        curl_close($ch);

        $mapConsentUrlToFileContent[$url] = $final_content_body;

        return $mapConsentUrlToFileContent[$url];
    }

    private function get_match($content)
    {
        //Regex statement to fetch main body
        if (preg_match('/<main(.*?)>(.*?)<\/main>/s', $content,$matches))
        {
            $content_body = $matches[0];

            $clean_content = preg_replace('/<footer>(.*?)<\/footer>/s', ' ', $content_body);

            return $clean_content;
        }
        else {
            return null;
        }
    }

    protected function createLegalDocumentsForBanking(Merchant\Entity $merchant)
    {
        if ($this->app['basicauth']->getRequestOriginProduct() !== ProductType::BANKING
            or $this->app['request']->header(RequestHeader::X_DASHBOARD_USER_ID) === null)
        {
            // Don't proceed further if product is not 'banking' or user_id is not sent in request
            // user_id presence is checked since pre_signup is called internally in flows where user_id is not passed
            // and in these flows, we don't want merchant_consents & legal documents creation.

            $this->trace->info(TraceCode::USER_HEADER_MISSING, [
                Constants::MERCHANT_ID => $merchant->getId(),
                'method'               => 'createLegalDocumentsForBanking',
            ]);

            return;
        }

        $input = [
            Entity::ACTIVATION_FORM_MILESTONE => DEConstants::X_SUBMISSION,
            DEConstants::DOCUMENTS_DETAIL => [
                  [
                      DEConstants::TYPE => Constants::PRIVACY_POLICY,
                      DEConstants::URL  => Constants::RAZORPAY_PRIVACY_POLICY_URL
                  ],
                  [
                      DEConstants::TYPE => Constants::TERMS_OF_USE,
                      DEConstants::URL  => $this->getTermsOfUsePage(),
                  ]
            ]
        ];

        // Sends Legal documents to BVS & creates merchant_consents
        // Merchant_consents should be created in 'Pending' state. If BVS call succeeds, we update status of this record.
        $this->storeConsents($merchant->getId(), $input);

        // Surrounding this with a try-catch to prevent failure of pre_signup due to any BVS related issue
        try
        {
            $documentsDetail = $this->getDocumentsDetails($input);

            $legalDocumentsInput = [
                DEConstants::DOCUMENTS_DETAIL => $documentsDetail
            ];

            $processor = (new ProcessorFactory())->getLegalDocumentProcessor();

            $response = $processor->processLegalDocuments($legalDocumentsInput, DEConstants::RX);

            $responseData = $response->getResponseData();

            foreach ($documentsDetail as $documentDetailInput)
            {
                $type = $documentDetailInput['type'] ;

                $merchantConsentDetail = $this->repo->merchant_consents->fetchMerchantConsentDetails($merchant->getId(), $type);

                $input = [
                    'status'     => ConsentConstant::INITIATED,
                    'updated_at' => Carbon::now()->getTimestamp(),
                    'request_id' => $responseData['id']
                ];

                (new ConsentCore())->updateConsentDetails($merchantConsentDetail, $input);
            }

        }
        catch(Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BVS_CREATE_LEGAL_DOCUMENTS_FAILED);
        }
    }

    protected function getTermsOfUsePage(): string
    {
        $cookieValue = trim(\Cookie::get('rzp_utm'), '"');

        $utmParams = json_decode($cookieValue, true);

        $website = $utmParams[Constants::WEBSITE] ?? '';

        $isCaPage = false;

        foreach (DEConstants::CA_PAGES as $caPage)
        {
            if($website === $caPage)
            {
                $isCaPage = true;
                break;
            }
        }

        if ($isCaPage)
        {
            return Constants::RAZORPAY_CA_TERMS_OF_USE;
        }

        return Constants::RAZORPAY_TERMS_OF_USE;
    }

    /**
     * @param $input
     *
     * @return array
     */
    public function getDocumentsDetails($input, array &$mapConsentUrlToFileContent = []): array
    {
        $documentDetailsInput = $input[DEConstants::DOCUMENTS_DETAIL];

        $documents_detail = [];

        foreach ($documentDetailsInput as $documentDetailInput)
        {
            $consentType = $input[Entity::ACTIVATION_FORM_MILESTONE] ? ($input[Entity::ACTIVATION_FORM_MILESTONE] . '_' . $documentDetailInput[DEConstants::TYPE]) : $documentDetailInput[DEConstants::TYPE];

            $document_detail = [
                "type"         => $consentType,
                "content_type" => "html",
                "content"      => $this->getDocumentDetailsContent($documentDetailInput, $mapConsentUrlToFileContent)
            ];

            $documents_detail[] = $document_detail;
        }

        return $documents_detail;
    }

    private function getDocumentDetailsContent($input, array &$mapConsentUrlToFileContent = []) : string
    {
        $isTestingEnvironment = $this->app['env'] === Environment::TESTING;

        if ($isTestingEnvironment)
        {
            return 'Dummy Content';
        }

        if (isset($input[DEConstants::CONTENT]) === true)
        {
            return $input[DEConstants::CONTENT];
        }

        return $this->getFileContentInHtml($input['url'], $mapConsentUrlToFileContent);
    }

    private function sendSelfServeSuccessAnalyticsEventToSegmentForAddOrUpdateBusinessWebsite(string $event)
    {
        [$segmentEventName, $segmentProperties] = $this->core->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = ($event === DashboardEvents::MERCHANT_BUSINESS_WEBSITE_UPDATE) ? "Business Website Updated" : "Business Website Added";

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }

    private function getPartnerInfoFromInput(array &$input)
    {
        if ((isset($input[Merchant\Constants::PARTNER_ID]) === false) or
            (empty($input[Merchant\Constants::PARTNER_ID]) === true))
        {
            return '';
        }

        $partnerId = $input[Merchant\Constants::PARTNER_ID];

        $partner = $this->repo->merchant->findOrFailPublic($partnerId);

        (new Merchant\Validator())->validateIsAggregatorPartner($partner);

        unset($input[Merchant\Constants::PARTNER_ID]);

        return $partnerId;
    }

    public function checkIfConsentsPresentForPartner($merchantId, $validDocTypes, $partnerId)
    {
        $consentDetails = $this->repo->merchant_consents->getConsentDetailsForMerchantIdAndConsentForPartner($merchantId, $validDocTypes, $partnerId);

        if($consentDetails === null)
        {
            return false;
        }

        return true;
    }

    public function createMerchantConsent(string $merchantId, array $input, array $legalDocumentsInput, array $validDocTypes)
    {
        $partnerId = $input[DetailConstants::ENTITY_ID];

        if ($this->checkIfConsentsPresentForPartner($merchantId, $validDocTypes, $partnerId) === true)
        {
            $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
                'message' => 'Consents are already present for partner ' . $partnerId
            ]);

            return;
        }
        //if legal documents are not present already, store them in database
        $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS, [
            'message' => 'Consents are not present for partner ' . $partnerId,
        ]);

        $responseData = $this->storeConsentAndProcess($merchantId, $input, $legalDocumentsInput);

        $documentDetailsInput = $input[DEConstants::DOCUMENTS_DETAIL];

        foreach ($documentDetailsInput as $documentDetailInput)
        {
            $type = $input[Entity::ACTIVATION_FORM_MILESTONE] ? ($input[Entity::ACTIVATION_FORM_MILESTONE] . '_' . $documentDetailInput[DEConstants::TYPE]) : $documentDetailInput[DEConstants::TYPE];

            $merchantConsentDetail = $this->repo->merchant_consents->fetchMerchantConsentDetailsForPartner($merchantId, $type, $partnerId);

            $updateInput = [
                'status'     => ConsentConstant::INITIATED,
                'updated_at' => Carbon::now()->getTimestamp(),
                'request_id' => $responseData['id']
            ];

            (new ConsentCore())->updateConsentDetails($merchantConsentDetail, $updateInput);
        }
    }

    public function storeConsentAndProcess(string $merchantId, array $input, array $legalDocumentsInput) : array
    {
        $this->storeConsents($merchantId, $input);

        $documents_detail = $this->getDocumentsDetails($input);

        $legalDocumentsInput[DEConstants::DOCUMENTS_DETAIL] = $documents_detail;

        $processor = (new ProcessorFactory())->getLegalDocumentProcessor();

        $response = $processor->processLegalDocuments($legalDocumentsInput);

        return $response->getResponseData();
    }

    /**
     * @param $payload
     * @param $keyAlreadyExists
     * @param $value
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\InvalidPermissionException
     */
    private function storeTerminalProcurementBannerStatusForUPI($payload): void
    {
        try
        {
            $merchantId = $payload['merchant_id'];

            $data = (new Store\Core())->fetchValuesFromStore($merchantId,
                                                             StoreConfigKey::ONBOARDING_NAMESPACE,
                                                             [StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER]);

            $existingTerminalBannerStatus = $data[StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER];

            $terminalProcurementStatus = $payload['mir']['status'];

            switch ($terminalProcurementStatus)
            {
                case DEConstants::SUCCESS:
                    if ($payload['payment_method_enabled'] === true)
                    {
                        $bannerStatus = (empty($existingTerminalBannerStatus) === false and
                                         ($existingTerminalBannerStatus === DEConstants::PENDING_SEEN or
                                          $existingTerminalBannerStatus === DEConstants::PENDING_ACK)) ?
                            DEConstants::SUCCESS : DEConstants::NO_BANNER;
                    }
                    else
                    {
                        $bannerStatus = DEConstants::PENDING;
                    }
                    break;
                case DEConstants::FAILED:
                    $bannerStatus = DEConstants::PENDING;
                    break;
                case DEConstants::REJECTED:
                    $bannerStatus = DEConstants::REJECTED;
                    break;
                default:
                    $this->trace->info(TraceCode::INVALID_TERMINAL_PROCUREMENT_STATUS, [
                        'merchant_id'                   => $merchantId,
                        'payment_method'                => $payload['payment_method'],
                        'terminal_procurement_status'   => $terminalProcurementStatus,
                    ]);

                    throw new Exception\InvalidArgumentException(
                        'Not a valid status', ['status' => $terminalProcurementStatus]
                    );
            }

            $this->trace->info(TraceCode::STORE_TERMINAL_BANNER_STATUS, [
                'merchant_id'                   => $merchantId,
                'payment_method'                => $payload['payment_method'],
                'terminal_procurement_status'   => $terminalProcurementStatus,
                'banner_status'                 => $bannerStatus
            ]);

            (new Merchant\Store\Core)->updateMerchantStore($merchantId, [
                StoreConstants::NAMESPACE                              => StoreConfigKey::ONBOARDING_NAMESPACE,
                StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER => $bannerStatus
            ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::STORE_TERMINAL_BANNER_STATUS_FAILURE);
        }
    }
}
