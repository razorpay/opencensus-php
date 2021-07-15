<?php

namespace RZP\Services\Kafka\Utils;

use RZP\Constants\Mode;
use RZP\Services\Kafka\Exceptions as kafkaException;
use RZP\Services\Kafka\Consumers\Base\Consumer as BaseConsumer;

class CommandValidator
{
    public function validateOptions(array $options): void
    {
        $this->validateConsumer($options[Constants::CONSUMER]);
        $this->validateMode($options[Constants::MODE]);
    }

    private function validateConsumer(?string $consumer): void
    {
        if (! class_exists($consumer) ||
            !is_subclass_of($consumer, BaseConsumer::class)) {
            throw new kafkaException\InvalidConsumerException($consumer);
        }
    }

    private function validateMode(?string $mode): void
    {
        if (Mode::exists($mode) === false) {
            throw new kafkaException\InvalidModeException();
        }
    }
}
