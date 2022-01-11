<?php

namespace RZP\PushNotifications\Payout;

use RZP\PushNotifications\Constants;
use RZP\PushNotifications\Base\PushNotification;

class PendingApprovals extends PushNotification
{

    const NOTIFICATION_TITLE = "Approve Pending Payouts";
    const NOTIFICATION_BODY = "__COUNT__ payouts worth ₹__AMOUNT__ pending your approval";


    public function __construct(array $input)
    {
        parent::__construct();

        $this->input = $input;
    }

    protected function addAccountName()
    {
        $this->setAccountName(Constants::RAZORPAYX_CLEVERTAP);
        return $this;
    }

    protected function addOwnerId()
    {
        $this->setOwnerId($this->input['ownerId']);
        return $this;
    }

    protected function addOwnerType()
    {
        $this->setOwnerType($this->input['ownerType']);
        return $this;
    }

    protected function addNotificationTitle()
    {
        $this->setNotificationTitle(self::NOTIFICATION_TITLE);
        return $this;
    }

    protected function addNotificationBody()
    {
        $body = self::NOTIFICATION_BODY;
        $body = preg_replace('/__COUNT__/', $this->input['count'], $body);
        $body = preg_replace('/__AMOUNT__/', $this->input['amount'], $body);
        $this->setNotificationBody($body);
        return $this;
    }

    protected function addIdentityList()
    {
        $this->setIdentityList($this->input['identityList']);
        return $this;
    }

    protected function shouldSendToAllDevices()
    {
        $this->setSendToAllDevices(true);
        return $this;
    }

    protected function shouldSendTheCampaignRequest()
    {
        $this->setSendTheCampaignRequest(false);
        return $this;
    }

    protected function shouldSendTheTargetRequest()
    {
        $this->setSendTheTargetRequest(true);
        return $this;
    }

    protected function addTags()
    {
        $this->setTags($this->input['tags']);
        return $this;
    }

    protected function addAndroidChannelId()
    {
        $this->setAndroidChannelId(Constants::TRANSACTION_NOTIFICATION);
        return $this;
    }
}
