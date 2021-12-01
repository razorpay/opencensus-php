<?php
namespace RZP\Models\Merchant\M2MReferral\FriendBuy;

class EventResponse
{
    protected $eventId;

    protected $createdOn;

    protected $error;

    public function __construct(array $response)
    {
        if (empty($response[Constants::ERROR]) === false)
        {
            $this->error = new Error($response);
        }

        $this->eventId   = $response[Constants::EVENT_ID] ?? '';
        $this->createdOn = $response[Constants::CREATED_ON] ?? '';
    }

    public function isSuccess()
    {
        return empty($this->error);
    }

    public function getEventId()
    {
        return $this->eventId;
    }
}
