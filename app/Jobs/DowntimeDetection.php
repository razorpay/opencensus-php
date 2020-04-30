<?php

namespace RZP\Jobs;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\VirtualAccount;
use RZP\Exception\LogicException;

/**
 * Class DowntimeDetection
 *
 * @package RZP\Jobs
 */
class DowntimeDetection extends Job
{
    public $timeout = 5;

    protected $queueConfigKey = 'downtime';

    private $type;

    private $method;

    private $key;

    private $value;

    private $to;

    public function __construct(
        string $mode,
        string $type,
        string $method,
        string $key,
        string $value,
        Carbon $to)
    {
        parent::__construct($mode);

        $this->type = $type;

        $this->method = $method;

        $this->key = $key;

        $this->value   = $value;

        $this->to   = $to;
    }

    public function handle()
    {
        parent::handle();

        $tracePayload = [
            'to'        => $this->to,
            'type'      => $this->type,
            'method'    => $this->method,
            'key'       => $this->key,
            'value'     => $this->value,
        ];

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_V2_JOB_TRIGGERED, $tracePayload);

        try
        {
            (new \RZP\Models\Gateway\Downtime\DowntimeDetection())->createDowntimeIfNecessary($this->type, $this->method, $this->key, $this->value, $this->to);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::GATEWAY_DOWNTIME_DETECTION_V2_JOB_FAILED, $tracePayload);
        }
        finally
        {
            $this->trace->info(TraceCode::GATEWAY_DOWNTIME_DETECTION_V2_JOB_FINISHED, $tracePayload);

            $this->delete();
        }
    }
}
