<?php

namespace RZP\Modules\Migrate;

use Throwable;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;

class MigrateJob extends Job
{
    /** {@inheritDoc} */
    protected $queueConfigKey = 'batch';

    /** {@inheritDoc} */
    public $timeout = 3600; // I.e. 1 hour.

    /** @var Source */
    protected $source;

    /** @var array */
    protected $sourceOpts;

    /** @var Target */
    protected $target;

    /** @var array */
    protected $targetOpts;

    /** @var bool */
    protected $dryrun;

    public function __construct(
        string $mode,
        Source $source,
        array $sourceOpts,
        Target $target,
        array $targetOpts,
        bool $dryrun)
    {
        parent::__construct($mode);

        $this->source     = $source;
        $this->sourceOpts = $sourceOpts;
        $this->target     = $target;
        $this->targetOpts = $targetOpts;
        $this->dryrun     = $dryrun;
    }

    public function handle()
    {
        parent::handle();

        // Deletes job right away to ensure at-most-once gaurantee.
        $this->delete();

        $migrate = new Migrate($this->source, $this->target);

        $traceParams = $migrate->getTraceInfo($this->sourceOpts, $this->targetOpts);
        $this->trace->debug(TraceCode::MIGRATE_JOB_RECEIVED, $traceParams);

        try
        {
            $migrate->migrate($this->sourceOpts, $this->targetOpts, $this->dryrun);

            $this->trace->debug(TraceCode::MIGRATE_JOB_HANDLED, $traceParams);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::MIGRATE_JOB_FAILED, $traceParams);
        }
    }
}
