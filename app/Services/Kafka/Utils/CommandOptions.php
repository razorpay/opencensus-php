<?php

namespace RZP\Services\Kafka\Utils;

use RZP\Constants\Mode;

class CommandOptions
{
    private $topics;
    private $consumer;
    private $groupId;
    private $mode;

    public function __construct(array $options)
    {
        if (is_string($options[Constants::TOPICS]) === true) {
            $options[Constants::TOPICS] = [$options[Constants::TOPICS]];
        }

        $this->topics   = $options[Constants::TOPICS];
        $this->consumer = $options[Constants::CONSUMER];
        $this->groupId  = $options[Constants::GROUP_ID];
        $this->mode     = $options[Constants::MODE];

        return $this;
    }

    public function getTopics(): array
    {
        return (is_array($this->topics) && !empty($this->topics)) ? $this->topics : [];
    }

    public function getConsumer(): ?string
    {
        return $this->consumer;
    }

    public function getGroupId(): string
    {
        return (is_string($this->groupId) && strlen($this->groupId) > 1) ? $this->groupId : 'default-group';
    }


    public function getMode(): string
    {
        return $this->mode;
    }
}
