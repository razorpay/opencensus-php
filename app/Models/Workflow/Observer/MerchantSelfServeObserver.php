<?php


namespace RZP\Models\Workflow\Observer;

use App;
use RZP\Models\Merchant;
use RZP\Models\State\Name as StateName;
use RZP\Models\Workflow\Action\Differ\Entity;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Notifications\Dashboard\Events as DashboardEvents;
use RZP\Notifications\Dashboard\Constants as DashboardConstants;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;


class MerchantSelfServeObserver implements WorkflowObserverInterface
{
    protected $repo;

    protected $segmentAnalatyics;

    protected $entityId;

    protected $permissionName;

    const PERMISSION_VS_SEGMENTS = [

        PermissionName::UPDATE_MERCHANT_WEBSITE       => SegmentEvent::WEBSITE_SELF_SERVE_WORKFLOW,

        PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL  => SegmentEvent::WEBSITE_SELF_SERVE_WORKFLOW,

        PermissionName::INCREASE_TRANSACTION_LIMIT    => SegmentEvent::TRANSACTION_LIMIT_SELF_SERVE_WORKFLOW
    ];

    const PERMISSION_VS_EVENTS = [

        PermissionName::UPDATE_MERCHANT_WEBSITE       => DashboardEvents::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON,

        PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL  => DashboardEvents::BUSINESS_WEBSITE_ADD_REJECTION_REASON,

        PermissionName::INCREASE_TRANSACTION_LIMIT    => DashboardEvents::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON,

        PermissionName::EDIT_MERCHANT_GSTIN_DETAIL    => DashboardEvents::REJECTION_REASON_NOTIFICATION,
    ];

    public function __construct($input)
    {
        $app                    = App::getFacadeRoot();

        $this->repo             = $app['repo'];

        $this->segmentAnalytics = $app['segment-analytics'];

        $this->entityId         = $input[Entity::ENTITY_ID];

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

            $args = [
                Merchant\Constants::MERCHANT     => $merchant,
                DashboardEvents::EVENT           => self::PERMISSION_VS_EVENTS[$this->permissionName],
                Merchant\Constants::PARAMS       => [
                    DashboardConstants::MERCHANT_NAME     => $merchant[Merchant\Entity::NAME],
                    DashboardConstants::MESSAGE_BODY      => $rejectionReason[Constants::MESSAGE_BODY],
                    DashboardConstants::MESSAGE_SUBJECT   => $rejectionReason[Constants::MESSAGE_SUBJECT],
                ]
            ];

            (new DashboardNotificationHandler($args))->send();

            $segmentProperties[Constants::REJECTION_REASON] = $rejectionReason[Constants::MESSAGE_BODY];
        }

        $this->trackSelfServeEvent(StateName::REJECTED, $segmentProperties);
    }

    public function onCreate(array $observerData)
    {

    }

    public function getMerchantId()
    {
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
}
