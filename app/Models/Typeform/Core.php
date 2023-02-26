<?php

namespace RZP\Models\Typeform;

use App;
use Mail;
use View;
use Request;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\lib\DataParser;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Services\Stork;
use Illuminate\Support\Str;
use RZP\Models\Workflow\Action;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Models\Payment\Method as PaymentMethod;
use RZP\Models\Merchant\RiskMobileSignupHelper;
use RZP\Notifications\Dashboard\Events as DashboardEvents;
use RZP\Notifications\Dashboard\Constants as DashboardConstants;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FreshdeskConstants;
use RZP\Models\Merchant\ProductInternational\ProductInternationalField;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;
use RZP\Models\Dispute;
use RZP\Models\Merchant\InternationalEnablement\Detail as IEDetail;
use RZP\Models\Workflow\Action\Differ\Entity as WorkflowDifferEntity;
use RZP\Models\Workflow\Action\Differ\Service as WorkflowDifferService;

class Core extends Base\Core
{
    private $freshdeskConfig;

    const EXCLUDE_EMAIL_FROM_CC_ON_RISK_EMAIL = "businessops@razorpay.com";

    public function __construct()
    {
        parent::__construct();

        $this->freshdeskConfig = $this->app['config']->get('applications.freshdesk');
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function processTypeformWebhook(array $input)
    {
        $merchant = $this->fetchMerchant($input);

        $this->app['basicauth']->setMerchant($merchant);

        $typeformParser = DataParser\Factory::getDataParserImpl(DataParser\Base::TYPEFORM, $input);

        $this->trace->info(TraceCode::TYPEFORM_RAW_DATA, ['mid' => $merchant->getId()]);

        $typeformWorkflowData = $typeformParser->parseWebhookData();

        $this->trace->info(TraceCode::TYPEFORM_PARSED_DATA,
                           ['mid'        => $merchant->getId(),
                            'parsedData' => $typeformWorkflowData]);

        $this->createInternationalWorkflow($merchant, $typeformWorkflowData, $input);

        return ['success' => true];
    }

    public function processInHouseQuestionnaire(Merchant $merchant, array $workflowData, array $input, $version = 'v1')
    {
        $this->trace->info(TraceCode::INTERNATIONAL_ENABLEMENT_WORKFLOW_TRIGGERED, [
            'mid'           => $merchant->getId(),
            'workflow_data' => $workflowData,
        ]);

        $this->createInternationalWorkflow($merchant, $workflowData, $input, $version);
    }

    /**
     * @param array $input
     *
     * @return mixed
     * @throws Exception\BadRequestException
     */
    private function fetchMerchant(array $input)
    {
        if ((array_key_exists('hidden', $input['form_response'])) and
            (array_key_exists('mid', $input['form_response']['hidden'])))
        {
            $merchantId = $input['form_response']['hidden']['mid'];

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            return $merchant;
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_PRESENT,
                null,
                ['data' => $input['event_id']]
            );
        }
    }

    /**
     * @param Merchant $merchant
     * @param array    $typeformWorkflowData
     * @param array    $input
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    private function createInternationalWorkflow(Merchant $merchant, array $typeformWorkflowData, array $input, $version = 'v1')
    {
        $productCategoriesRequested = [];
        //add a check if workflow should be created - for blacklist??
        $productInternationalField = new ProductInternationalField($merchant);
        //To be removed (post final testing)
        $this->trace->info(TraceCode::TYPEFORM_WORKFLOW_TRIGGERED, ['input' => $input]);

        if (key_exists('permission', $input))
        {
            $this->executeApproval($input['permission'], $merchant, $version);

            $this->processWorkflowRequestApproval($input['permission'], $merchant, $version);
        }
        else
        {
            foreach (ProductInternationalMapper::LIVE_PRODUCTS as $productName)
            {
                if ($productInternationalField->isRequestedEnablement($productName) === true)
                {
                    $productInternationalField->setProductStatus(
                        $productName, ProductInternationalMapper::DISABLED);

                    array_push($productCategoriesRequested,
                               ProductInternationalMapper::fetchProductCategory($productName));
                }
            }

            $productCategoriesRequested = array_unique($productCategoriesRequested);

            $this->repo->merchant->saveOrFail($merchant);

            $this->createMerchantWorkflow($productCategoriesRequested, $merchant, $typeformWorkflowData, $version);
        }
    }

    /**
     * @param array    $productCategoriesRequested
     * @param Merchant $merchant
     * @param array    $typeformWorkflowData
     */
    public function createMerchantWorkflow(array $productCategoriesRequested,
                                           Merchant $merchant, array $typeformWorkflowData, $version = 'v1')
    {
        //To be removed (post final testing)
        $this->trace->info(TraceCode::TYPEFORM_WORKFLOW_TRIGGERED, ['method' => 'createInternationalWorkflow']);

        $internationalEnablementRequestId = Constants::INTERNATIONAL_ENABLEMENT_REQUEST_ID_PREFIX . $this->app['request']->getId();

        $workflowRequestTags = [$internationalEnablementRequestId];

        if (count($productCategoriesRequested) > 1)
        {
            $workflowRequestTags[] = Constants::INTERNATIONAL_ENABLEMENT_REQUEST_HAS_SIBLINGS;
        }

        if ($version === 'v2')
        {
            $input = Request::all();

            unset($input[IEDetail\Entity::DOCUMENTS]);

            $this->app['workflow']
                ->setEntityAndId($merchant->getEntity(), $merchant->getId())
                ->setPermission(Name::TOGGLE_INTERNATIONAL_REVAMPED)
                ->setInput($input)
                ->setTags($workflowRequestTags)
                ->handle(null, $typeformWorkflowData, true);
            // added true in handle to continue the flow.

            $this->trace->info(TraceCode::TOGGLE_INTERNATIONAL_REVAMPED_WORKFLOW_TRIGGERED, []);

            $this->sendNotificationForInReviewState($merchant);
        }
        else
        {
            foreach ($productCategoriesRequested as $index => $productCategoryRequested)
            {
                $nextWorkflowPresent = false ? ($index === count($productCategoryRequested) - 1) : true;

                $permission = ProductInternationalMapper::PRODUCT_PERMISSION[$productCategoryRequested];

                $this->trace->info(TraceCode::TYPEFORM_WORKFLOW_TRIGGERED, ['permission1' => $permission]);

                $this->app['workflow']
                    ->setEntityAndId($merchant->getEntity(), $merchant->getId())
                    ->setPermission($permission)
                    ->setTags($workflowRequestTags)
                    ->handle(null, $typeformWorkflowData, $nextWorkflowPresent);
            }
        }
        //To be removed (post final testing)
        $this->trace->info(TraceCode::TYPEFORM_WORKFLOW_TRIGGERED, ['approval action' => 'reached to createMerchantWorkflow']);
    }
    
    /**
     * @throws Exception\BadRequestException
     */
    private function getInternationalEnablementDetailId(array $data) : string
    {
        if ((isset($data[Constants::NEW]) === false) or
            (isset($data[Constants::NEW][Constants::DETAIL_URL]) === false))
        {
            throw new Exception\BadRequestException(
              'International Enablement Detail Not Present'
            );
        }
        
        $detailUrl = $data[Constants::NEW][Constants::DETAIL_URL];
        
        $pieces = explode('/', $detailUrl);

        return $pieces[count($pieces)-1];
    }
    
    
    /**
     * @throws Exception\BadRequestException
     */
    public function getProductNamesFromActionEntityData(Action\Entity $action): array
    {
        $actionId = $action->getId();

        $data = (new WorkflowDifferService())->fetchRequest($actionId);

        $internationalEnablementDetailId = $this->getInternationalEnablementDetailId($data[WorkflowDifferEntity::DIFF]);

        $products = (new IEDetail\Repository())->getProductsFromEntityId($internationalEnablementDetailId);

        $this->trace->info(TraceCode::TOGGLE_INTERNATIONAL_REVAMPED_PRODUCT_REQUESTED, [
            'products' => $products,
        ]);

        return $products;
    }

    private function sendNotificationForInReviewState($merchant)
    {
        $tatDaysLater = Carbon::now()->addDays(DashboardConstants::IE_TAT_DAYS)->format('M d,Y');

        $args = [
            DashboardConstants::MERCHANT => $merchant,
            DashboardEvents::EVENT       => DashboardEvents::IE_UNDER_REVIEW,
            DashboardConstants::PARAMS   => [
                DashboardConstants::MERCHANT_NAME => $merchant->getName(),
                DashboardConstants::UPDATE_DATE   => $tatDaysLater,
            ]
        ];

        (new DashboardNotificationHandler($args))->send();
    }

    /**
     * @param string   $permission
     * @param Merchant $merchant
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    private function executeApproval(string $permission, Merchant $merchant, $version = 'v1')
    {
        if ($version === 'v2')
        {
            $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
                $merchant->getId(), Constants::MERCHANT_KEY, Name::TOGGLE_INTERNATIONAL_REVAMPED);
    
            if (is_null($workflowActions) === false)
            {
                // Ideally should have only one workflow action
                $action = $workflowActions->first();
    
                $productNames = $this->getProductNamesFromActionEntityData($action);
            }
        }
        else
        {
            // sync between old international enabling flows with product based international flows.
            // For old international enabling flows(which are not closed before product based international goes live),
            // all the products are enabled if workflow is approved
            
            if (($permission === Name::EDIT_MERCHANT_INTERNATIONAL_NEW or
                $permission === Name::EDIT_MERCHANT_INTERNATIONAL) === true)
            {
                $productNames = ProductInternationalMapper::LIVE_PRODUCTS;
            }
            else
            {
                $permissionProductCategories = array_flip(ProductInternationalMapper::PRODUCT_PERMISSION);
        
                $permissionProductCategory = $permissionProductCategories[$permission];
        
                if (array_key_exists($permission, $permissionProductCategories) === false)
                {
                    throw new Exception\BadRequestException(
                      ErrorCode::BAD_REQUEST_INVALID_PERMISSION,
                      null,
                      ['data' => $permission]
                    );
                }
        
                $productNames = ProductInternationalMapper::PRODUCT_CATEGORIES[$permissionProductCategory];
            }
        }

        $this->checkWebsiteValidity($merchant);

        $this->trace->info(
            TraceCode::PRODUCT_INTERNATIONAL_APPROVED,
            ['productNames' => $productNames]);

        $productInternationalField = new ProductInternationalField($merchant);

        foreach ($productNames as $productName)
        {
            $productInternationalField->updateProductStatus($productName, ProductInternationalMapper::ENABLED);
        }
    }

    /**
     * @param Merchant $merchant
     *
     * @throws Exception\BadRequestValidationFailureException
     *
     * This check is required to avoid the situation when a workflow is executed and website of the Merchant is not
     * valid, the workflow gets closed without activating international as website validation is base requirement
     * for international enabling.
     *
     */
    private function checkWebsiteValidity(Merchant $merchant)
    {
        $merchantCore = new MerchantCore();

        $websiteValid =
            $merchantCore->validateWebsiteCheckForInternationalActivation($merchant, $merchant->merchantDetail);

        if ($websiteValid === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Workflow can't be approved as Merchant Website is not Valid"
            );
        }
    }

    public function processWorkflowRequestApproval(string $permissionName, Merchant $merchant, $version = 'v1')
    {
        $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
            $merchant->getId(), Constants::MERCHANT_KEY, $permissionName);

        // multiple open state workflows are not allowed
        // here exactly one open state workflow should be present
        $action = $workflowActions->first();

        $this->notifyMerchantIfApplicable($action, true, $version);
    }

    public function processWorkflowRequestRejection(Action\Entity $action, array $extraData)
    {
        $version  = 'v1';
        
        $actionPermission = $action->permission->getName();
        
        if ($actionPermission === Name::TOGGLE_INTERNATIONAL_REVAMPED)
        {
            $version = 'v2';
        }

        $rejectionReason = ($version === 'v2') ? $this->extractRejectionReasonV2FromPayload($extraData) :
                                $this->extractRejectionReasonFromPayload($extraData);
        
        if ($version !== 'v2')
        {
            $rejectionTags = $this->extractRejectionTagsFromPayload($extraData);
        }

        // form the final tag list
        $rejectionTags[] = $rejectionReason;

        $action->tag($rejectionTags);

        $this->repo->workflow_action->saveOrFail($action);

        $this->notifyMerchantIfApplicable($action, false, $version);
    }

    private function extractRejectionReasonV2FromPayload(array $extraData): string
    {
        $rejectionReason = $extraData[Constants::REJECTION_REASON_KEY] ?? Constants::REJECT_REASON_MERCHANT_RISK_REJECTION;

        if ((is_string($rejectionReason) === false) || (Constants::isValidRejectionReasonV2($rejectionReason) === false))
        {
            $rejectionReason = Constants::REJECT_REASON_MERCHANT_RISK_REJECTION;
        }

        return Constants::REJECTION_REASON_PREFIX . $rejectionReason;
    }
    
    private function extractRejectionReasonFromPayload(array $extraData)
    {
        $rejectionReason = $extraData[Constants::REJECTION_REASON_KEY] ?? Constants::REJECT_REASON_MERCHANT_LOOKS_RISKY;

        if ((is_string($rejectionReason) === false) || (Constants::isValidRejectionReason($rejectionReason) === false))
        {
            $rejectionReason = Constants::REJECT_REASON_MERCHANT_LOOKS_RISKY;
        }

        return Constants::REJECTION_REASON_PREFIX . $rejectionReason;
    }

    private function extractRejectionTagsFromPayload(array $extraData)
    {
        $validRejectionTags = [];

        $rejectionTagsCsv = $extraData[Constants::REJECTION_TAGS_KEY] ?? '';

        if ((empty($rejectionTagsCsv) === true) || (is_string($rejectionTagsCsv) === false))
        {
            return $validRejectionTags;
        }

        $rejectionTags = explode(Constants::CSV_SEPERATOR, $rejectionTagsCsv);

        foreach ($rejectionTags as $rejectionTag)
        {
            if (Constants::isValidRejectionTag($rejectionTag) === true)
            {
                $validRejectionTags[] = Constants::REJECTION_TAG_PREFIX . $rejectionTag;
            }
        }

        return array_unique($validRejectionTags);
    }

    /**
     * @throws Exception\BadRequestException
     */
    private function notifyMerchantInternationalEnablementApprovalV2(Action\Entity $action)
    {
        $approvedProductCount = 0;

        $merchantId = $action->getEntityId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $productInternational = $merchant->getProductInternational();

        foreach (ProductInternationalMapper::LIVE_PRODUCTS as $productName)
        {
            $status = (new ProductInternationalField($merchant))->getProductStatus($productName, $productInternational);

            $approvedProductCount = $approvedProductCount + 1;
        }

        $event = Constants::APPROVED_PRODUCT_COUNT_VS_IE_SUCCESS_EVENT[$approvedProductCount];

        $args = [
            DashboardConstants::MERCHANT => $merchant,
            DashboardEvents::EVENT       => $event,
            DashboardConstants::PARAMS   => [
                DashboardConstants::MERCHANT_NAME => $merchant['name'],
            ]
        ];

        (new DashboardNotificationHandler($args))->send();
    }


    /**
     * @throws Exception\BadRequestException
     */
    private function notifyMerchantIfApplicable(Action\Entity $action, bool $calledOnRequestApproval, $version = 'v1')
    {
        $requestEnablementTag = $this->getInternationalEnablementRequestTag($action);

        // For legacy support
        if (empty($requestEnablementTag) === true)
        {
            $this->trace->info(TraceCode::INTERNATIONAL_ENABLEMENT_NOTIFICATION_LEGACY_FLOW, [
                'workflow_action_id' => $action->getId(),
            ]);

            return;
        }

        if ($this->isNotificationEnabled($action, $requestEnablementTag) === true)
        {
            $action->tag(Constants::AUTO_MERCHANT_NOTIFICATION_ENABLED);

            $this->repo->workflow_action->saveOrFail($action);
        }
        else
        {
            $action->tag(Constants::AUTO_MERCHANT_NOTIFICATION_DISABLED);

            $this->repo->workflow_action->saveOrFail($action);

            return;
        }

        if (($version === 'v2') and
            ($calledOnRequestApproval === true))
        {
            // In case of workflow rejection, communication is being handled from
            // Models/Workflow/Observer/MerchantSelfServeObserver.php file

            $this->notifyMerchantInternationalEnablementApprovalV2($action);
            
            return;
        }

        $siblingActions = $this->getSiblingWorkflowActions($action, $requestEnablementTag);

        $siblingAction = NULL;

        if ($siblingActions->isEmpty() === false)
        {
            $siblingAction = $siblingActions->first();

            if ($siblingAction->isOpen() === true)
            {
                return;
            }
        }

        $notificationData = [];

        // fill action sprefic data
        $actionPermission = $action->permission->getName();

        $notificationData[$actionPermission] = [
            'approved' => $calledOnRequestApproval,
        ];

        if ($calledOnRequestApproval === false)
        {
            $rejectionReason = $this->getRejectionReasonForAction($action);

            $notificationData[$actionPermission]['rejection_reason'] = $rejectionReason;
        }

        // fill sibling action specific data
        if (is_null($siblingAction) === false)
        {
            $siblingActionPermission = $siblingAction->permission->getName();

            $siblingActionApproved = $siblingAction->isExecuted();

            $notificationData[$siblingActionPermission] = [
                'approved' => $siblingActionApproved,
            ];

            if ($siblingActionApproved === false)
            {
                $rejectionReason = $this->getRejectionReasonForAction($siblingAction);

                $notificationData[$siblingActionPermission]['rejection_reason'] = $rejectionReason;
            }
        }

        $merchantId = $action->getEntityId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->sendEnablementRequestClosureNotifications($merchant, $notificationData);
    }

    private function isNotificationEnabled(Action\Entity $action, string $treatmentId)
    {
        $this->trace->info(TraceCode::INTERNATIONAL_ENABLEMENT_NOTIFICATION_RAZORX_VARIANT, [
            'workflow_action_id' => $action->getId(),
            'treatment_id'       => $treatmentId,
            'razorx_variant'     => 'notify',
        ]);

        return true;
    }

    private function getInternationalEnablementRequestTag(Action\Entity $action)
    {
        $tagNames = $action->tagNames();

        $internationalEnablementRequestIdPrefix = $this->formatTagName(Constants::INTERNATIONAL_ENABLEMENT_REQUEST_ID_PREFIX);

        foreach ($tagNames as $tagName)
        {
            if (Str::startsWith($tagName, $internationalEnablementRequestIdPrefix) === true)
            {
                return $tagName;
            }
        }

        return '';
    }

    private function getSiblingWorkflowActions(Action\Entity $action, $searchTag = null)
    {
        if (empty($searchTag) === true)
        {
            $searchTag = $this->getInternationalEnablementRequestTag($action);
        }

        if (empty($searchTag) === true)
        {
            return collect([]);
        }

        $actionsWithSearchTag = Action\Entity::withAllTags([$searchTag])->get();

        $siblingActions = $actionsWithSearchTag->filter(function(Action\Entity $actionWithSearchTag) use ($action)
        {
            return $actionWithSearchTag->getId() !== $action->getId();
        });

        return $siblingActions;
    }

    private function getRejectionReasonForAction(Action\Entity $action)
    {
        $rejectionReason = '';

        if ($action->isRejected() === false)
        {
            return $rejectionReason;
        }

        $rejectionReasonPrefix = $this->formatTagName(Constants::REJECTION_REASON_PREFIX);

        $tagNames = $action->tagNames();

        foreach ($tagNames as $tagName)
        {
            if (Str::startsWith($tagName, $rejectionReasonPrefix) === true)
            {
                $rejectionReason = Str::substr($tagName, strlen($rejectionReasonPrefix));
            }
        }

        return $rejectionReason;
    }

    private function formatTagName(string $tagName)
    {
        $tagDisplayer = config('tagging.displayer');

        return call_user_func($tagDisplayer, $tagName);
    }

    private function sendEnablementRequestClosureNotifications(Merchant $merchant, array $data)
    {
        if (RiskMobileSignupHelper::isEligibleForMobileSignUp($merchant) === true)
        {
            try
            {
                list($mailViewTpl, $mailData, $tags) = $this->getEnablementMailTemplateAndData($merchant, $data);

                $mailData['merchant_email'] = 'test@razorpay.com';

                $requestParams = [
                    'type'          =>  'Question',
                    'tags'          =>  $tags,
                    'status'        =>  5,
                    'subCategory'   =>  'International Enablement',
                ];

                $fdTicket = (new RiskMobileSignupHelper())->createFdTicket($merchant, $mailViewTpl, Constants::IE_MAIL_SUBJECT, $mailData, $requestParams);

                $supportTicketLink = (new RiskMobileSignupHelper())->getSupportTicketLink($fdTicket, $merchant);

                $this->sendEnablementClosureSms($merchant, $data, $supportTicketLink);

                $this->sendEnablementClosureWhatsappMessage($merchant, $data, $supportTicketLink);
            }
            catch (\Throwable $e)
            {
                $this->app['trace']->traceException($e,
                                                    Trace::CRITICAL,
                                                    TraceCode::INTERNATIONAL_ENABLEMENT_MOBILE_SIGNUP_NOTIF_FAILED,
                                                    [
                                                        'merchant_id' => $merchant->getId(),
                                                    ]
                );
            }
        }
        else
        {
            $this->sendEnablementClosureSms($merchant, $data);

            $this->sendEnablementClosureWhatsappMessage($merchant, $data);

            $this->sendEnablementClosureEmail($merchant, $data);
        }
    }

    private function sendEnablementClosureSms(Merchant $merchant, array $permissionsData, $supportTicketLink = null)
    {
        $mode = $this->app['rzp.mode'];

        $signUpMethod = (isset($supportTicketLink) === true) ? Constants::MOBILE_SIGNUP : Constants::EMAIL_SIGNUP;

        $receiver = $merchant->merchantDetail->getContactMobile();

        if (empty($receiver) === true)
        {
            return;
        }

        $template = Constants::SMS_INTERNATIONAL_ENABLEMENT_REJECTED_TPL[$signUpMethod];

        foreach ($permissionsData as $permissionData)
        {
            if ($permissionData['approved'] === true)
            {
                $template = Constants::SMS_INTERNATIONAL_ENABLEMENT_APPROVED_TPL[$signUpMethod];
            }
        }

        $payload = [
            'receiver' => $receiver,
            'template' => $template,
            'source'   => 'api.' . $mode .'.international_enablement',
            'params'   => [
                'merchant_id'   => $merchant->getId(),
                'merchantId'    => $merchant->getId(),
                'merchantName'  => $merchant->getName(),
                'business_name' => $merchant->merchantDetail->getBusinessName(),
            ],
            'stork' => [
                'context' => [
                    'org_id' => $merchant->getOrgId(),
                ],
            ],
        ];

        if (isset($supportTicketLink) === true)
        {
            $payload['params']['supportTicketLink'] = $supportTicketLink;
        }

        try
        {
            $this->app['raven']->sendSms($payload);

            $this->app['trace']->info(
                TraceCode::INTERNATIONAL_ENABLEMENT_SMS_SENT,
                [
                    'merchant_id' => $merchant->getId(),
                ]);
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->traceException($e,
                Trace::CRITICAL,
                TraceCode::INTERNATIONAL_ENABLEMENT_SMS_FAILED,
                [
                    'merchant_id' => $merchant->getId(),
                ]
            );
        }
    }

    private function sendEnablementClosureWhatsappMessage(Merchant $merchant, array $permissionsData, $supportTicketLink = null)
    {
        $signUpMethod = (isset($supportTicketLink) === true) ? Constants::MOBILE_SIGNUP : Constants::EMAIL_SIGNUP;

        $mode = $this->app['rzp.mode'];

        $receiver = $merchant->merchantDetail->getContactMobile();

        $whatsAppPayload = [
            'ownerId'   => $merchant->getId(),
            'ownerType' => 'merchant',
            'params'    => [
                'merchant_id'   => $merchant->getId(),
                'merchantId'    => $merchant->getId(),
                'merchantName'  => $merchant->getName(),
                'business_name' => $merchant->merchantDetail->getBusinessName(),
            ]
        ];

        if (isset($supportTicketLink) === true)
        {
            $whatsAppPayload['params']['supportTicketLink'] = $supportTicketLink;
        }

        $template = Constants::WHATSAPP_INTERNATIONAL_ENABLEMENT_REJECTED_TPL[$signUpMethod];
        $templateName = Constants::WHATSAPP_INTERNATIONAL_ENABLEMENT_REJECTED_TPL_NAME[$signUpMethod];

        foreach ($permissionsData as $permissionData)
        {
            if ($permissionData['approved'] === true)
            {
                $template = Constants::WHATSAPP_INTERNATIONAL_ENABLEMENT_APPROVED_TPL[$signUpMethod];
                $templateName = Constants::WHATSAPP_INTERNATIONAL_ENABLEMENT_APPROVED_TPL_NAME[$signUpMethod];
            }
        }

        $whatsAppPayload['template_name'] = $templateName;

        (new Stork)->sendWhatsappMessage(
            $mode,
            $template,
            $receiver,
            $whatsAppPayload
        );
    }

    private function sendEnablementClosureEmail(Merchant $merchant, array $permissionsData)
    {
        $merchantEmail = $merchant->merchantDetail->getContactEmail();

        $ccEmails = (new Dispute\Service)->getCCEmailsWithSalesPOC($merchant->getId());

        list($mailViewTpl, $mailData, $tags) = $this->getEnablementMailTemplateAndData($merchant, $permissionsData);

        $mailData['merchant_email'] = $merchant->merchantDetail->getContactMobile();;

        $mailBody = View::make($mailViewTpl, $mailData)->render();

        try
        {
            $fdOutboundEmailRequest = [
                'subject'         => Constants::IE_MAIL_SUBJECT,
                'description'     => $mailBody,
                'status'          => 5,
                'type'            => 'Question',
                'priority'        => 1,
                'email'           => $merchantEmail,
                'tags'            => $tags,
                'group_id'        => (int) $this->freshdeskConfig['group_ids']['rzpind']['merchant_risk'],
                'email_config_id' => (int) $this->freshdeskConfig['email_config_ids']['rzpind']['risk_notification'],
                'custom_fields' => [
                    'cf_ticket_queue' => 'Merchant',
                    'cf_category'     => 'Risk Report_Merchant',
                    'cf_subcategory'  => 'International Enablement',
                    'cf_product'      => 'Payment Gateway',
                ],
            ];

            if (empty($ccEmails) === false)
            {
                $fdOutboundEmailRequest['cc_emails'] = $ccEmails;
            }

            $this->app['freshdesk_client']->sendOutboundEmail(
                $fdOutboundEmailRequest, FreshdeskConstants::URLIND);

            $this->app['trace']->info(
                TraceCode::INTERNATIONAL_ENABLEMENT_EMAIL_SENT,
                [
                    'merchant_id' => $merchant->getId(),
                ]);
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->traceException($e,
                Trace::CRITICAL,
                TraceCode::INTERNATIONAL_ENABLEMENT_EMAIL_FAILED,
                [
                    'merchant_id' => $merchant->getId(),
                ]
            );
        }
    }

    private function getEnablementMailTemplateAndData(Merchant $merchant, array $permissionsData)
    {
        $data = [
            'merchant_id'   => $merchant->getId(),
            'business_name' => $merchant->merchantDetail->getBusinessName(),
        ];

        $approvedPermList = [];
        $rejectedPermList = [];
        $rejectionReasons = [];
        $mailViewTpl      = NULL;

        $tags[] = Constants::FD_TAG_IE_AUTO_MAILER;

        foreach ($permissionsData as $permissionName => $permissionData)
        {
            if ($permissionData['approved'] === true)
            {
                $approvedPermList[] = $permissionName;
            }
            else
            {
                $rejectedPermList[] = $permissionName;

                $rejectionReason = $permissionData['rejection_reason'];

                if (empty($rejectionReason) === true)
                {
                    $rejectionReason = Constants::REJECT_REASON_MERCHANT_LOOKS_SAFE;
                }

                $rejectionReasons[$rejectionReason] = Constants::REJECTION_REASON_PRIORITY[$rejectionReason];
            }
        }

        if (count($rejectedPermList) == 2)
        {
            asort($rejectionReasons);

            $rejectionReason = key($rejectionReasons);

            $mailViewTpl = Constants::REJECTED_MAIL_VIEW_TPL[$rejectionReason];

            $tags[] = Constants::FD_TAG_IE_REJECTED;

            $tags[] = Constants::FD_TAG_IE_SIBLING;

            $tags[] = Constants::FD_IE_REJECTION_TAGS[$rejectionReason];
        }
        else if (count($approvedPermList) == 2)
        {
            $mailViewTpl = Constants::ACCEPTED_MAIL_VIEW_TPL;

            $this->addAdditionalApprovalEmailData($merchant, $data);

            $tags[] = Constants::FD_TAG_IE_APPROVED;

            $tags[] = Constants::FD_TAG_IE_SIBLING;
        }
        else if (count($approvedPermList) > 0 && count($rejectedPermList) > 0)
        {
            $data['approved_products'] = Constants::PERMISSION_PRODUCT_MAPPING[$approvedPermList[0]];

            $data['rejected_products'] = Constants::PERMISSION_PRODUCT_MAPPING[$rejectedPermList[0]];

            $mailViewTpl = Constants::ACCEPTED_MAIL_VIEW_TPL;

            $this->addAdditionalApprovalEmailData($merchant, $data);

            $tags[] = Constants::FD_TAG_IE_APPROVED;

            $tags[] = Constants::FD_TAG_IE_APPROVED_REJECTED;

            $tags[] = Constants::FD_TAG_IE_SIBLING;
        }
        else if (count($rejectedPermList) == 1)
        {
            $rejectionReason = key($rejectionReasons);

            $mailViewTpl = Constants::REJECTED_MAIL_VIEW_TPL[$rejectionReason];

            $tags[] = Constants::FD_TAG_IE_REJECTED;

            $tags[] = Constants::FD_IE_REJECTION_TAGS[$rejectionReason];
        }
        else
        {
            $mailViewTpl = Constants::ACCEPTED_MAIL_VIEW_TPL;

            $this->addAdditionalApprovalEmailData($merchant, $data);

            $tags[] = Constants::FD_TAG_IE_APPROVED;
        }

        return [$mailViewTpl, $data, $tags];
    }

    private function addAdditionalApprovalEmailData(Merchant $merchant, array & $emailData)
    {
        $emailData['max_txn_amount_inr'] = $merchant->getMaxPaymentAmount() / 100;

        $domesticDelay = Merchant::INTERNATIONAL_SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

        $internationalDelay = Merchant::INTERNATIONAL_SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

        $scheduleTaskCore = new ScheduleTask\Core;

        $domesticScheduleTask = $scheduleTaskCore->getMerchantSettlementSchedule($merchant, PaymentMethod::CARD, false);

        if(is_null($domesticScheduleTask) === false)
        {
            $domesticDelay = $domesticScheduleTask->schedule->getDelay();
        }

        $internationalScheduleTask = $scheduleTaskCore->getMerchantSettlementSchedule($merchant, PaymentMethod::CARD, true);

        if (is_null($internationalScheduleTask) === false)
        {
            $internationalDelay = $internationalScheduleTask->schedule->getDelay();
        }

        $emailData['domestic_settlement_cycle'] = $domesticDelay;

        $emailData['international_settlement_cycle'] = $internationalDelay;
    }

}
