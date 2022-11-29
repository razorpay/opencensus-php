<?php

namespace RZP\Services\Mock;

class KafkaProducerClient
{
    public function produce($topicName, $message, $key = null)
    {
        return [
            'topicName' => $topicName,
            'message' => $message
        ];
    }
}
