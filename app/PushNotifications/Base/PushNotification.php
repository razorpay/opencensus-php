<?php

namespace RZP\PushNotifications\Base;

use App;
use RZP\Constants\Product;
use RZP\Models\Merchant\Core;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Container\Container;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;

class PushNotification {

    private $app;

    private $clevertapConfig;

    public $mode;

    public $originProduct;

    public $ownerId;

    /**
     * @var string
     */
    public $accountName;

    public $ownerType = 'merchant';

    protected $pnValidator;

    protected $mid;

    /**
     * @var array
     */
    protected $emailList = array();

    /**
     * @var array
     */
    protected $facebookIdList = array();

    /**
     * @var array
     */
    protected $identityList = array();

    /**
     * @var string
     */
    protected $notificationTitle;

    /**
     * @var string
     */
    protected $notificationBody;

    /**
     * @var bool
     */
    protected $sendToAllDevices;

    /**
     * @var array
     */
    protected $platformSpecificField = array();

    /**
     * @var bool
     */
    protected $sendTheCampaignRequest;

    /**
     * @var bool
     */
    protected $sendTheTargetRequest;

    /**
     * @var string
     */
    protected $tagGroup;

    /**
     * @var array
     */
    protected $tags;

    /**
     * @var array
     */
    protected $objectIdList = array();

    /**
     * @var array
     */
    protected $input;

    protected $androidChannelId;

    protected $data;

    /**
     * PushNotification constructor.
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->taskId           = $this->app['request']->getTaskId();
        $this->mode             = $this->app['basicauth']->getMode();
        $this->originProduct    = $this->app['basicauth']->getProduct();
        $this->mid              = $this->app['basicauth']->getMerchantId();
    }

    public function build()
    {
        $this->addAccountName()
            ->addOwnerId()
            ->addOwnerType()
            ->addNotificationTitle()
            ->addNotificationBody()
            ->addFacebookIdList()
            ->addIdentityList()
            ->addEmailList()
            ->addPlatformSpecificField()
            ->addAndroidChannelId()
            ->shouldSendToAllDevices()
            ->shouldSendTheCampaignRequest()
            ->shouldSendTheTargetRequest()
            ->addTagGroup()
            ->addObjectIdList()
            ->addTags()
            ;
    }

    public function send()
    {
        $app = App::getFacadeRoot();
        $trace = $app['trace'];

        try
        {
            Container::getInstance()->call([$this, 'build']);
            $payload = [
                'owner_id' => $this->ownerId,
                'owner_type' => $this->ownerType,
                'context' => json_decode('{}'),
                'push_notification_channels' => []
            ];

            $request = [
                'clevertap_account_name' => $this->accountName,
            ];

            $this->platformSpecificField['android_o_channel_id'] = $this->androidChannelId;
            $this->platformSpecificField['tags'] = $this->tags;

            $clervertapRequest = [
                'content_title' => $this->notificationTitle,
                'content_body' => $this->notificationBody,
                'platform_specific_fields' => $this->platformSpecificField,
                'send_to_all_devices' => $this->sendToAllDevices,
            ];

            if($this->shouldSendTheTargetRequest())
            {
                $clervertapRequest['to_facebook_id'] = $this->facebookIdList;
                $clervertapRequest['to_identity'] = $this->identityList;
                $clervertapRequest['to_email'] = $this->emailList;
                $clervertapRequest['to_object_id'] = $this->objectIdList;
                $clervertapRequest['tag_group'] = $this->tagGroup;

                $request['target_user_campaign_request'] = $clervertapRequest;

                $requestKey = 'push_notification_request';
                $request['push_notification_type'] = 0;
                $request['account_name'] = $this->accountName;
                // In the existing implementation, owner_id is merchant however we are overriding it to type user
                $isExperimentEnabled = $this->checkClevertapMigrationExperimentEnabled($this->ownerId);
                if($isExperimentEnabled)
                {
                    $payload['owner_id'] = $this->tags['userId'];
                    $payload['owner_type'] = 'user';
                }


                array_push($payload['push_notification_channels'], [$requestKey => $request]);
            }

            $res = (new Stork($this->mode, $this->originProduct))->sendPushNotification($payload);
        }
        catch (\Throwable $e)
        {
            $trace->traceException($e,
                Trace::ERROR,
                TraceCode::PUSH_NOTIFICATION_ERROR,
                [
                    'ownerId'    => $this->ownerId,
                    'ownerType'  => $this->ownerType,
                    'notificationTitle' => $this->notificationTitle,
                    'notificationBody'  => $this->notificationBody,
                    'pushNotification' => get_class($this),
                ]);

            if (($e instanceof GuzzleClientException) !== true)
            {
                throw $e;
            }
        }
    }

    protected function addAccountName()
    {
        return $this;
    }

    protected function addOwnerId()
    {
        return $this;
    }

    protected function addOwnerType()
    {
        return $this;
    }

    protected function addNotificationTitle()
    {
        return $this;
    }

    protected function addNotificationBody()
    {
        return $this;
    }

    protected function addFacebookIdList()
    {
        return $this;
    }

    protected function addIdentityList()
    {
        return $this;
    }

    protected function addEmailList()
    {
        return $this;
    }

    protected function addPlatformSpecificField()
    {
        return $this;
    }

    protected function addAndroidChannelId()
    {
        return $this;
    }

    protected function shouldSendToAllDevices()
    {
        return $this;
    }

    protected function shouldSendTheCampaignRequest()
    {
        return $this;
    }

    protected function shouldSendTheTargetRequest()
    {
        return $this;
    }

    protected function addTagGroup()
    {
        $this->setTagGroup($this->input['tagGroup']);

        return $this;
    }

    protected function addObjectIdList()
    {
        return $this;
    }

    protected function addTags()
    {
        return $this;
    }

    /**
     * @param mixed $ownerId
     */
    public function setOwnerId($ownerId): void
    {
        $this->ownerId = $ownerId;
    }

    /**
     * @param string $accountName
     */
    public function setAccountName(string $accountName): void
    {
        $this->accountName = $accountName;
    }

    /**
     * @param string $ownerType
     */
    public function setOwnerType(string $ownerType): void
    {
        $this->ownerType = $ownerType;
    }

    /**
     * @param mixed $pnValidator
     */
    public function setPnValidator($pnValidator): void
    {
        $this->pnValidator = $pnValidator;
    }

    /**
     * @param mixed $mid
     */
    public function setMid($mid): void
    {
        $this->mid = $mid;
    }

    /**
     * @param array $emailList
     */
    public function setEmailList(array $emailList): void
    {
        $this->emailList = $emailList;
    }

    /**
     * @param array $facebookIdList
     */
    public function setFacebookIdList(array $facebookIdList): void
    {
        $this->facebookIdList = $facebookIdList;
    }

    /**
     * @param array $identityList
     */
    public function setIdentityList(array $identityList): void
    {
        $this->identityList = $identityList;
    }

    /**
     * @param string $notificationTitle
     */
    public function setNotificationTitle(string $notificationTitle): void
    {
        $this->notificationTitle = $notificationTitle;
    }

    /**
     * @param string $notificationBody
     */
    public function setNotificationBody(string $notificationBody): void
    {
        $this->notificationBody = $notificationBody;
    }

    /**
     * @param bool $sendToAllDevices
     */
    public function setSendToAllDevices(bool $sendToAllDevices): void
    {
        $this->sendToAllDevices = $sendToAllDevices;
    }

    /**
     * @param array $platformSpecificField
     */
    public function setPlatformSpecificField(array $platformSpecificField): void
    {
        $this->platformSpecificField = $platformSpecificField;
    }

    /**
     * @param bool $sendTheCampaignRequest
     */
    public function setSendTheCampaignRequest(bool $sendTheCampaignRequest): void
    {
        $this->sendTheCampaignRequest = $sendTheCampaignRequest;
    }

    /**
     * @param bool $sendTheTargetRequest
     */
    public function setSendTheTargetRequest(bool $sendTheTargetRequest): void
    {
        $this->sendTheTargetRequest = $sendTheTargetRequest;
    }

    /**
     * @param array $input
     */
    public function setInput(array $input): void
    {
        $this->input = $input;
    }

    /**
     * @param mixed $androidChannelId
     */
    public function setAndroidChannelId($androidChannelId): void
    {
        $this->androidChannelId = $androidChannelId;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @param string $tagGroup
     */
    public function setTagGroup(string $tagGroup): void
    {
        $this->tagGroup = $tagGroup;
    }

    /**
     * @param array $objectIdList
     */
    public function setObjectIdList(array $objectIdList): void
    {
        $this->objectIdList = $objectIdList;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    protected function checkClevertapMigrationExperimentEnabled(string $merchantId) : bool
    {
        $isExperimentEnabled = (new Core())->isSplitzExperimentEnable([
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.clevertap_migration_splitz_experiment_id')
        ], \RZP\Models\User\Constants::ACTIVE,
            TraceCode::CLEVERTAP_MIGRATION_EXPERIMENT_FAILED);

        app()->trace->info(TraceCode::CLEVERTAP_MIGRATION_EXPERIMENT_STATUS, [
            'experiment_status' => $isExperimentEnabled,
            'merchant_id'       => $merchantId,
        ]);

        return $isExperimentEnabled;
    }
}
