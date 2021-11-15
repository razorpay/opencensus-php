<?php

namespace RZP\Services;

use App;

use RZP\Services\ThirdWatchKafkaProducer;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Address\Entity;
use RZP\Models\Address;
use RZP\Trace\TraceCode;
use Illuminate\Support\Str;

/**
 * Used by 1cc
 * checks completeness of an address and
 * likelihood of rto for cod orders
 */
class ThirdWatchClient
{
    const TW_KAFKA_TOPIC = 'address-events';

    private $trace;

    public function __construct()
    {
        $this->trace = App::getFacadeRoot()['trace'];
    }

    public function sendAddressToKafka($key, $address)
    {
      $kafkaStart = $this->getCurrentTimeInMillis();

      $kafkaResult = (new ThirdWatchKafkaProducer(self::TW_KAFKA_TOPIC, stringify($address), $key))->Produce();

      if ($kafkaResult !== 0)
      {
          // push to kafka failed
          $this->trace->error(
              TraceCode::TW_ADDRESS_COD_VALIDITY_KAFKA_FAILED_COUNT,
              ['kafka_code' => $kafkaResult, 'time_taken' => ($this->getCurrentTimeInMillis() - $kafkaStart)]
          );

          return false;
      }

      $this->trace->histogram(
          TraceCode::TW_ADDRESS_COD_VALIDITY_KAFKA_PUSH_DURATION,
          $this->getCurrentTimeInMillis() - $kafkaStart
      );

      return true;
    }

    protected function getCurrentTimeInMillis()
    {
        return round(microtime(true) * 1000);
    }
}
