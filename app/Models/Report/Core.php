<?php

namespace RZP\Models\Report;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Jobs\ReportsJob;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    /**
     * builds the report entity, given params
     *
     * @param   $input    array
     *          Expected params in $input are :
     *          start_time, end_time, entity, day, month, year
     * @param   $merchant Merchant\Entity
     *
     * @return  $report Entity
     */
    public function buildEntity(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::REPORT_CREATE_REQUEST,
            $input
        );

        $params = [
            Entity::DAY             => $input['day'] ?? null,
            Entity::MONTH           => $input['month'],
            Entity::YEAR            => $input['year'],
            Entity::START_TIME      => $input['from'],
            Entity::END_TIME        => $input['to'],
            Entity::TYPE            => $input['entity'],
            Entity::GENERATED_BY    => $this->getInternalUsernameOrEmail(),
        ];

        $report = (new Entity)->build($params);

        $report->merchant()->associate($merchant);

        return $report;
    }

    /**
     * Validates the input
     * Validates the entity
     * Queues report generation for merchant
     *
     * @param $input array
     *        expected : 'day', 'month', 'year'
     * @param $entity string
     * @return void
     */
    public function queueGenerateReport(array $input, string $entity)
    {
        try
        {
            (new Validator)->validateInput('report_queue', array_merge($input, [Entity::TYPE => $entity]));

            ReportsJob::dispatch($input, $entity, $this->merchant->getId(), $this->mode);
        }
        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::REPORT_QUEUE_JOB_FAILED);
        }
    }
}
