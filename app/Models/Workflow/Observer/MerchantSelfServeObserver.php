<?php


namespace RZP\Models\Workflow\Observer;

use App;
use Illuminate\Support\Facades\Mail;
use RZP\Mail\Merchant as MerchantMail;
use RZP\Models\State\Name as StateName;
use RZP\Models\Workflow\Action\Differ\Entity;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Admin\Permission\Name as PermissionName;

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

            $messageBody          = $rejectionReason[Constants::MESSAGE_BODY];

            $messageSubject       = $rejectionReason[Constants::MESSAGE_SUBJECT];

            $merchant             = $this->getMerchant();

            $merchantPrimaryOwner = $merchant->primaryOwner()->toArrayPublic();

            $mailClassInstance    = (new MerchantMail\RejectionReasonNotification($merchantPrimaryOwner, $messageSubject, $messageBody));

            Mail::queue($mailClassInstance);

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
