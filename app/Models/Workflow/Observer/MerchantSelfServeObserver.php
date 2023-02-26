<?php


namespace RZP\Models\Workflow\Observer;

use App;
use Carbon\Carbon;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\State\Name as StateName;
use RZP\Models\Workflow\Action\Differ\Entity;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Settlement\Service as SettlementService;
use RZP\Models\Typeform\Constants as TypeformConstants;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Notifications\Dashboard\Events as DashboardEvents;
use RZP\Notifications\Dashboard\Constants as DashboardConstants;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;


class MerchantSelfServeObserver implements WorkflowObserverInterface
{
    protected $repo;

    protected $segmentAnalytics;

    protected $entityId;

    protected $permissionName;

    protected $entityName;

    protected $payload;

    const PERMISSION_VS_SEGMENTS = [

        PermissionName::UPDATE_MERCHANT_WEBSITE       => SegmentEvent::WEBSITE_SELF_SERVE_WORKFLOW,

        PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL  => SegmentEvent::WEBSITE_SELF_SERVE_WORKFLOW,

        PermissionName::INCREASE_TRANSACTION_LIMIT    => SegmentEvent::TRANSACTION_LIMIT_SELF_SERVE_WORKFLOW,

        PermissionName::EDIT_MERCHANT_BANK_DETAIL     => SegmentEvent::BANK_ACCOUNT_UPDATE_WORKFLOW,

        PermissionName::EDIT_MERCHANT_GSTIN_DETAIL    => SegmentEvent::ADD_GSTIN_WORKFLOW_STATUS,

        PermissionName::UPDATE_MERCHANT_GSTIN_DETAIL  => SegmentEvent::EDIT_GSTIN_WORKFLOW_STATUS

    ];

    const PERMISSION_VS_EVENTS = [

        PermissionName::UPDATE_MERCHANT_WEBSITE        => DashboardEvents::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON,

        PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL   => DashboardEvents::BUSINESS_WEBSITE_ADD_REJECTION_REASON,

        PermissionName::INCREASE_TRANSACTION_LIMIT     => DashboardEvents::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON,

        PermissionName::INCREASE_INTERNATIONAL_TRANSACTION_LIMIT => DashboardEvents::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON,

        PermissionName::UPDATE_MERCHANT_GSTIN_DETAIL   => DashboardEvents::GSTIN_UPDATE_REJECTION_REASON,

        PermissionName::EDIT_MERCHANT_GSTIN_DETAIL     => DashboardEvents::GSTIN_ADD_REJECTION_REASON,

        PermissionName::EDIT_MERCHANT_BANK_DETAIL      => DashboardEvents::BANK_ACCOUNT_CHANGE_REJECTION_REASON,

        PermissionName::ADD_ADDITIONAL_WEBSITE         => DashboardEvents::ADD_ADDITIONAL_WEBSITE_REJECTION_REASON,
    ];

    const PERMISSION_FOR_NEED_CLARIFICATION_SEGMENT_ACTION_NAME = [

        PermissionName::ADD_ADDITIONAL_WEBSITE         => Constants::ADDITIONAL_WEBSITE . ' '.  Constants::NEEDS_CLARIFICATION_TRIGERRED,

        PermissionName::UPDATE_MERCHANT_WEBSITE        => Constants::WEBSITE . ' '.  Constants::NEEDS_CLARIFICATION_TRIGERRED,

        PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL   => Constants::WEBSITE . ' '.  Constants::NEEDS_CLARIFICATION_TRIGERRED,

        PermissionName::INCREASE_TRANSACTION_LIMIT     => Constants::TRANSACTION_LIMIT . ' '.  Constants::NEEDS_CLARIFICATION_TRIGERRED,

        PermissionName::EDIT_MERCHANT_BANK_DETAIL      => Constants::BANK_ACCOUNT . ' '.  Constants::NEEDS_CLARIFICATION_TRIGERRED,

        PermissionName::EDIT_MERCHANT_GSTIN_DETAIL     => Constants::GSTIN . ' '.  Constants::NEEDS_CLARIFICATION_TRIGERRED,

        PermissionName::UPDATE_MERCHANT_GSTIN_DETAIL   => Constants::GSTIN . ' '.  Constants::NEEDS_CLARIFICATION_TRIGERRED,
    ];

    const PERMISSION_FOR_NEW_SELF_SERVE_COMMUNICATIONS = [

        PermissionName::EDIT_MERCHANT_BANK_DETAIL,

        PermissionName::TOGGLE_INTERNATIONAL_REVAMPED,
    ];

    const IE_REJECTION_REASON_VS_EVENT = [

        TypeformConstants::REJECT_REASON_MERCHANT_CLARIFICATION_NOT_PROVIDED    => DashboardEvents::IE_REJECTED_CLARIFICATION_NOT_PROVIDED,

        TypeformConstants::REJECT_REASON_MERCHANT_WEBSITE_DETAIL_INCOMPLETE     => DashboardEvents::IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE,

        TypeformConstants::REJECT_REASON_MERCHANT_BUSINESS_MODEL_MISMATCH       => DashboardEvents::IE_REJECTED_BUSINESS_MODEL_MISMATCH,

        TypeformConstants::REJECT_REASON_MERCHANT_INVALID_DOCUMENTS             => DashboardEvents::IE_REJECTED_INVALID_DOCUMENTS,

        TypeformConstants::REJECT_REASON_MERCHANT_RISK_REJECTION                => DashboardEvents::IE_REJECTED_RISK_REJECTION,

        TypeformConstants::REJECT_REASON_MERCHANT_HIGH_CHARGEBACK_FRAUD_PRESENT => DashboardEvents::IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD,

        TypeformConstants::REJECT_REASON_MERCHANT_DORMANT_MERCHANT              => DashboardEvents::IE_REJECTED_DORMANT_MERCHANT,

        TypeformConstants::REJECT_REASON_MERCHANT_RESTRICTED_BUSINESS           => DashboardEvents::IE_REJECTED_RESTRICTED_BUSINESS,
    ];

    public function __construct($input)
    {
        $app                    = App::getFacadeRoot();

        $this->repo             = $app['repo'];

        $this->segmentAnalytics = $app['segment-analytics'];

        $this->entityId         = $input[Entity::ENTITY_ID];

        $this->entityName       = $input[Entity::ENTITY_NAME];

        $this->payload          = $input[Entity::PAYLOAD];

        $this->permissionName   = $input[Entity::PERMISSION];
    }

    public function onApprove(array $observerData)
    {
        $this->trackSelfServeEvent(StateName::APPROVED);
    }

    public function onClose(array $observerData)
    {
        $this->trackSelfServeEvent(StateName::CLOSED);
    }

    public function onReject(array $observerData)
    {
        $segmentProperties = [];

        if (key_exists(Constants::REJECTION_REASON, $observerData) === true)
        {
            $rejectionReason      = json_decode($observerData[Constants::REJECTION_REASON], true);

            $merchant             = $this->getMerchant();

            $event = self::PERMISSION_VS_EVENTS[$this->permissionName];

            if (in_array($this->permissionName, self::PERMISSION_FOR_NEW_SELF_SERVE_COMMUNICATIONS, true) === true)
            {
                $this->sendNewSelfServeNotification($this->permissionName, $rejectionReason, $merchant);

                return;
            }

            $args = [
                Merchant\Constants::MERCHANT     => $merchant,
                DashboardEvents::EVENT           => $event,
                Merchant\Constants::PARAMS       => [
                    DashboardConstants::MERCHANT_NAME     => $merchant[Merchant\Entity::NAME],
                    DashboardConstants::MESSAGE_BODY      => $rejectionReason[Constants::MESSAGE_BODY],
                    DashboardConstants::MESSAGE_SUBJECT   => $rejectionReason[Constants::MESSAGE_SUBJECT],
                ]
            ];

            if (array_key_exists($event, DashboardConstants::CTA_TEMPLATES_VS_BUTTON_URL) === true)
            {
                $args[DashboardConstants::IS_CTA_TEMPLATE]  = true;

                $args[DashboardConstants::BUTTON_URL_PARAM] = DashboardConstants::CTA_TEMPLATES_VS_BUTTON_URL[$event];
            }

            (new DashboardNotificationHandler($args))->send();

            $segmentProperties[Constants::REJECTION_REASON] = $rejectionReason[Constants::MESSAGE_BODY];
        }

        $this->trackSelfServeEvent(StateName::REJECTED, $segmentProperties);
    }

    public function onCreate(array $observerData)
    {

    }

    public function onExecute(array $observerData)
    {

    }

    public function getMerchantId()
    {
        if (($this->entityName !== 'merchant') and
            ($this->entityName !== 'merchant_detail') and
            (empty($this->payload['merchant_id']) === false))
        {
            return $this->payload['merchant_id'];
        }

        return $this->entityId;
    }

    public function getMerchant()
    {
        $merchant = $this->repo->merchant->findOrFailPublic($this->getMerchantId());

        return $merchant;
    }

    protected function getWebsiteSelfServeFlowName()
    {
        return $this->permissionName == PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL ? 'website add' : 'website edit';
    }

    protected function getSegmentPropertiesForPermission()
    {
        $segmentProperties = [];

        if (in_array($this->permissionName , [PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL, PermissionName::UPDATE_MERCHANT_WEBSITE ]))
        {
            $segmentProperties['flow'] = $this->getWebsiteSelfServeFlowName();
        }

        return $segmentProperties;
    }

    protected function trackSelfServeEvent(string $workflowState, array $additionalProperties = [])
    {
        if(key_exists($this->permissionName, self::PERMISSION_VS_SEGMENTS))
        {
            $merchant             = $this->getMerchant();

            $segmentProperties    = array_merge(
                ['status' => $workflowState],
                $this->getSegmentPropertiesForPermission(),
                $additionalProperties
            );

            $this->segmentAnalytics->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, self::PERMISSION_VS_SEGMENTS[$this->permissionName]);
        }
    }

    protected function sendNewSelfServeNotification($permissionName, $rejectionReason, $merchant)
    {
        if (($permissionName === PermissionName::EDIT_MERCHANT_BANK_DETAIL) and
            ($merchant->getOrgId() === OrgEntity::RAZORPAY_ORG_ID))
        {
            $this->sendNotificationForBankAccountUpdate($merchant);

            return;
        }

        if ($permissionName === PermissionName::TOGGLE_INTERNATIONAL_REVAMPED)
        {
            $this->sendNotificationForToggleInternationalRevamped($merchant, $rejectionReason);

            return;
        }
    }

    protected function sendNotificationForBankAccountUpdate($merchant, $event = DashboardEvents::BANK_ACCOUNT_UPDATE_REJECTED)
    {
        $merchantBankAccount = $this->repo->bank_account->getBankAccount($merchant);

        $bankAccountNumber = $merchantBankAccount->getAccountNumber();

        $last_3 = substr($bankAccountNumber, -3);

        $merchantConfig = [];

        if ($merchant->isFeatureEnabled(Feature\Constants::NEW_SETTLEMENT_SERVICE) === true)
        {
            $input[Merchant\Constants::MERCHANT_ID] = $merchant->getMerchantId();

            $merchantConfig = (new SettlementService)->merchantConfigGet($input);
        }

        $isMerchantSettlementsOnHold = (new BankAccount\Core)->isMerchantSettlementsOnHold($merchantConfig);

        if ($isMerchantSettlementsOnHold === true)
        {
            $event = DashboardEvents::BANK_ACCOUNT_UPDATE_SOH_REJECTED;
        }

        $args = [
            Merchant\Constants::MERCHANT     => $merchant,
            DashboardEvents::EVENT           => $event,
            Merchant\Constants::PARAMS       => [
                DashboardConstants::MERCHANT_NAME => $merchant[Merchant\Entity::NAME],
                DashboardConstants::LAST_3        => '**' . $last_3,
            ]
        ];

        (new DashboardNotificationHandler($args))->send();
    }

    protected function sendNotificationForToggleInternationalRevamped($merchant, $rejectionReason)
    {
        $rejectionReason = $rejectionReason[Constants::MESSAGE_BODY];

        $rejectionRetryAfterDate = Carbon::now()->addDays(DashboardConstants::IE_REJECTION_RETRY_AFTER_DAYS)->format('M d,Y');

        $event = self::IE_REJECTION_REASON_VS_EVENT[$rejectionReason];

        $args = [
            Merchant\Constants::MERCHANT     => $merchant,
            DashboardEvents::EVENT           => $event,
            Merchant\Constants::PARAMS       => [
                DashboardConstants::MERCHANT_NAME => $merchant[Merchant\Entity::NAME],
                DashboardConstants::UPDATE_DATE   => $rejectionRetryAfterDate,
            ]
        ];

        (new DashboardNotificationHandler($args))->send();
    }
}
