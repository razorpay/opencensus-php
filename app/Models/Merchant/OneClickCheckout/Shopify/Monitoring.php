<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Metric;
use RZP\Models\Base;

class Monitoring extends Base\Core
{

  public function addTraceCount(string $metric, array $dimensions = []): void
  {
      $baseDimensions = [
          'mode' => $this->mode,
      ];

      $this->trace->count(
          $metric,
          array_merge($baseDimensions, $dimensions));
  }

  public function traceResponseTime(string $metric, int $startTime, array $dimensions = []): void
  {
      $duration = millitime() - $startTime;

      $baseDimensions = [
          'mode' => $this->mode,
      ];

      $this->trace->histogram(
          $metric,
          $duration,
          array_merge($baseDimensions, $dimensions));
  }
}
