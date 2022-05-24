<?php

namespace RZP\PushNotifications\CurrentAccount;

use RZP\PushNotifications\Constants;
use RZP\PushNotifications\Base\PushNotification;

class StatusUpdate extends PushNotification
{

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
        $this->setNotificationTitle($this->input['title']);
        return $this;
    }

    protected function addNotificationBody()
    {
        $this->setNotificationBody($this->input['body']);
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
        $this->setAndroidChannelId(Constants::GENERIC_NOTIFICATION);
        return $this;
    }
}
