<?php

namespace RZP\Modules\Migrate;

use Throwable;
use Razorpay\Trace\Logger;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\AccessMap\MigrateImpersonationSource as MigrateImpersonationSource;

/**
 * Migrate
 * A small module to help with migration.
 *
 * Notice! The module itself does not guarantee atomicity of record when
 * migrating where it involves read from two sources which follows updates.
 * It is left to source and target implementations to take care of case by case.
 */
class Migrate
{
    /** @var Source */
    protected $source;

    /** @var Target */
    protected $target;

    /** @var Logger */
    protected $trace;

    /** @var string */
    protected $mode;

    /**
     * @param Source $source
     * @param Target $target
     */
    public function __construct(Source $source, Target $target)
    {
        $this->source = $source;
        $this->target = $target;

        $this->trace  = app('trace');
        $this->mode   = app('rzp.mode');
    }

    /**
     * @param array   $sourceOpts
     * @param array   $targetOpts
     * @param boolean $dryRun
     * @return array See $summary
     */
    public function migrateAsync(array $sourceOpts, array $targetOpts, bool $dryRun): array
    {
        $this->trace->info(TraceCode::MIGRATE_ASYNC_REQUEST, $this->getTraceInfo($sourceOpts, $targetOpts));

        $numOfJobsDispatched = 0;
        foreach ($this->source->getParallelOpts($sourceOpts) as $opts)
        {
            MigrateJob::dispatch($this->mode, $this->source, $opts, $this->target, [], $dryRun);
            $numOfJobsDispatched++;
        }
        foreach ($this->target->getParallelOpts($targetOpts) as $opts)
        {
            MigrateJob::dispatch($this->mode, $this->source, [], $this->target, $opts, $dryRun);
            $numOfJobsDispatched++;
        }

        $summary = $this->getTraceInfo($sourceOpts, $targetOpts);
        $summary['num_of_jobs_dispatched'] = $numOfJobsDispatched;
        $this->trace->info(TraceCode::MIGRATE_ASYNC_SUMMARY, $summary);
        return $summary;
    }

    /**
     * @param array   $sourceOpts
     * @param array   $targetOpts
     * @param boolean $dryRun
     * @return array See $summary
     */
    public function migrate(array $sourceOpts, array $targetOpts, bool $dryRun): array
    {
        $this->trace->info(TraceCode::MIGRATE_REQUEST, $this->getTraceInfo($sourceOpts, $targetOpts));

        $summary = [
            'dry_run'                    => $dryRun,
            // Corresponds to 1st iteration.
            'source_iter_total'          => null,
            'target_created_total'       => null,
            'target_updated_total'       => null,
            'target_upserted_total'      => null,
            'target_action_failed_total' => null,
            // Corresponds to 2nd iteration.
            'target_iter_total'          => null,
            'target_deleted_total'       => null,
        ];

        // Iteration 1: Iterates over source and if needed then either creates/updates corresponding record in/of target.
        foreach ($this->source->iterate($sourceOpts) as $sourceRecord)
        {
            $summary['source_iter_total']++;
            try
            {
                // check if instance is MigrateImpersonationSource
                if ($this->source instanceof MigrateImpersonationSource) {
                    // allow to delete target record only if source record is deleted or expired
                    if ($this->source->getAction($sourceRecord) == 'delete') {
                        $this->target->delete($sourceRecord);
                        $summary['target_deleted_total']++;
                        continue;
                    }
                }
                $resp = $this->target->migrate($sourceRecord, $dryRun);
                $summary["target_{$resp->action}_total"]++;
            }
            catch (Throwable $e)
            {
                $summary['target_action_failed_total']++;
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::MIGRATE_TARGET_ACTION_FAILED,
                    $this->getRecordTraceInfo($sourceRecord)
                );
            }
        }

        // Iteration 2: Iterates over target and deletes record not existing in source.
        foreach ($this->target->iterate($targetOpts) as $targetRecord)
        {
            $summary['target_iter_total']++;
            try
            {
                $exists = $this->source->find($targetRecord) !== null;
                if ($exists === true)
                {
                    if ($dryRun === false)
                    {
                        $this->target->delete($targetRecord);
                    }
                    $summary['target_deleted_total']++;
                }
            }
            catch (Throwable $e)
            {
                $summary['target_action_failed_total']++;
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::MIGRATE_TARGET_ACTION_FAILED,
                    $this->getRecordTraceInfo(null, $targetRecord)
                );
            }
        }

        $this->trace->info(TraceCode::MIGRATE_SUMMARY, $summary);
        return $summary;
    }

    /**
     * @param array $sourceOpts
     * @param array $targetOpts
     * @return array
     */
    public function getTraceInfo(array $sourceOpts, array $targetOpts): array
    {
        return [
            'source_name' => get_class($this->source),
            'source_opts' => $sourceOpts,
            'target_name' => get_class($this->target),
            'target_opts' => $targetOpts,
        ];
    }

    /**
     * @param Record $sourceRecord
     * @param Record $targetRecord
     * @return array
     */
    protected function getRecordTraceInfo(Record $sourceRecord = null, Record $targetRecord = null): array
    {
        return [
            'source_name'       => get_class($this->source),
            'source_record_key' => optional($sourceRecord)->key,
            'target_name'       => get_class($this->target),
            'target_record_key' => optional($targetRecord)->key,
        ];
    }
}
