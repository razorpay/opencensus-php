<?php


namespace RZP\Jobs\Kafka;

use App;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Metric;
use Razorpay\Trace\Logger;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Service;
use RZP\Exception\BadRequestException;
use RZP\Exception\ExtraFieldsException;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Exception\BadRequestValidationFailureException;
use Illuminate\Database\UniqueConstraintViolationException;

class PgosCdcEventsJob extends Job
{

    const ERROR = "ERROR";
    const WARNING = "WARNING";
    /**
     * @throws \Exception
     */
    public function handle()
    {
        $taskId = gen_uuid();

        $this->setTaskId($taskId);

        parent::handle();

        $app = App::getFacadeRoot();

        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode'         => $this->mode,
            'payload'      => $this->getPayload(),
            'task_id'      => $taskId,
            'job'          => 'PgosCdcEventsJob'
        ];

        $this->trace->info(TraceCode::PGOS_DUAL_WRITE_CONSUMER_PAYLOAD, $tracePayload);

        $merchantId = $this->payload['data']['merchant_id'] ?? $this->payload['data']['id'];

        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $app['config']->get('app.emit_pgos_consumer_metric_experiment'),
        ];

        $isMetricExperimentEnabled =  (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');

        try
        {
            (new Service)->savePGOSDataToAPI($this->payload);
        }
        catch (ExtraFieldsException|BadRequestValidationFailureException|BadRequestException|UniqueConstraintViolationException $e)
        {
            //Not propagating the error post this, we don't want to re-attempt here
            $this->trace->warning(TraceCode::PGOS_DUAL_WRITE_CONSUMER_WARNING, [
                'code'      => $e->getCode(),
                'message'   => $e->getMessage(),
                'payload'   => $this->payload
            ]);

            if ($isMetricExperimentEnabled) {
                $this->trace->count(Metric::PGOS_DUAL_WRITE_CONSUMER_ERROR, [
                    'level'           => self::WARNING,
                    'code'            => $e->getCode(),
                    'description'     => $this->replaceSpaceWithUnderscore($e->getMessage()),
                    'attempt'         => $this->attempts(),
                    'retry'           => false
                ]);
            }

        }
        catch (DbQueryException $e)
        {
            //Continue to retry the same message in this case, increasing the consumer lag to trigger an alert
            $this->trace->error(TraceCode::PGOS_DUAL_WRITE_CONSUMER_ERROR, [
                'code'      => $e->getCode(),
                'message'   => $e->getMessage(),
                'payload'   => $this->payload,
                'attempt'   => $this->attempts()
            ]);

            if ($isMetricExperimentEnabled) {
                $this->trace->count(Metric::PGOS_DUAL_WRITE_CONSUMER_ERROR, [
                    'level'           => self::ERROR,
                    'code'            => $e->getCode(),
                    'description'     => $this->replaceSpaceWithUnderscore($e->getMessage()),
                    'attempt'         => $this->attempts(),
                    'retry'           => true
                ]);
            }
        }
        catch (\Throwable $e)
        {
            //For unknown errors, we want to reattempt until successful, or else lag increases to trigger an alert.

            $payload = ['payload' => $this->payload,
                        'error_block' => "GENERIC_ERROR",
                        'attempt'   => $this->attempts()];

            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::PGOS_DUAL_WRITE_CONSUMER_ERROR,
                $payload);

            if ($isMetricExperimentEnabled) {
                $this->trace->count(Metric::PGOS_DUAL_WRITE_CONSUMER_ERROR, [
                    'level'           => self::ERROR,
                    'code'            => $e->getCode(),
                    'description'     => $this->replaceSpaceWithUnderscore($e->getMessage()),
                    'attempt'         => $this->attempts(),
                    'retry'           => false
                ]);
            }
        }

    }

    private function replaceSpaceWithUnderscore($inputString) {
        return str_replace(' ', '__', $inputString);
    }
}
