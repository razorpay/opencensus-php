<?php


namespace RZP\Jobs\Kafka;

use App;
use Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RZP\Base\Database\Connectors\MySqlConnector;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Metric;
use Razorpay\Trace\Logger;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Service;
use RZP\Exception\BadRequestException;
use RZP\Exception\ExtraFieldsException;
use RZP\Exception\BadRequestValidationFailureException;
use Illuminate\Database\UniqueConstraintViolationException;
use \PDOException;
use RZP\Exception\DbQueryException;

class PgosCdcEventsJob extends Job
{

    const ERROR = "ERROR";
    const WARNING = "WARNING";

    const IGNORED_ERRORS = [
        "The validation id field is required."
    ];


    const PGOS_CDC_EVENTS_PROCESSING_ATTEMPT_COUNT              = 'pgos_cdc_event_processing_attempt_count';
    const PGOS_CDC_EVENTS_PROCESSING_ATTEMPT_COUNT_TTL_IN_SEC   = 1800;

    /**
     * @throws \Exception
     * @throws \Throwable
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

        $pgosCdcEventsProcessingAttempt = 0;

        try
        {
            $redisKey = $merchantId . "_" . $this->payload['ts'] . "_" . $this->payload['xid'];

            $pgosCdcEventsProcessingAttempt = $this->incrementKafkaMessageProcessingAttempt($redisKey, self::PGOS_CDC_EVENTS_PROCESSING_ATTEMPT_COUNT);

            (new Service)->savePGOSDataToAPI($this->payload);
        }
        catch (ExtraFieldsException|BadRequestValidationFailureException|BadRequestException|UniqueConstraintViolationException $e)
        {
            //Not propagating the error post this, we don't want to re-attempt here
            if (!in_array($e->getMessage(), self::IGNORED_ERRORS)) {
                $this->trace->warning(TraceCode::PGOS_DUAL_WRITE_CONSUMER_WARNING, [
                    'code'      => $e->getCode(),
                    'message'   => $e->getMessage(),
                    'payload'   => $this->payload
                ]);
            }

            if ($isMetricExperimentEnabled and !in_array($e->getMessage(), self::IGNORED_ERRORS)) {
                $this->trace->count(Metric::PGOS_DUAL_WRITE_CONSUMER_ERROR, [
                    'level'           => self::WARNING,
                    'code'            => $e->getCode(),
                    'attempt'         => $this->attempts(),
                    'retry'           => false
                ]);
            }

        }
        catch (QueryException| PDOException| DbQueryException $e)
        {
            //Continue to retry the same message in this case, increasing the consumer lag to trigger an alert
            $this->trace->error(TraceCode::PGOS_DUAL_WRITE_CONSUMER_ERROR, [
                'code'      => $e->getCode(),
                'message'   => $e->getMessage(),
                'payload'   => $this->payload,
                'attempt'   => $pgosCdcEventsProcessingAttempt,
                'retry'     => true,
            ]);

            if ($isMetricExperimentEnabled) {
                $this->trace->count(Metric::PGOS_DUAL_WRITE_CONSUMER_ERROR, [
                    'level'           => self::ERROR,
                    'code'            => $e->getCode(),
                    'attempt'         => $pgosCdcEventsProcessingAttempt,
                    'retry'           => true
                ]);
            }

            $sleepDuration = $this->getSleepDuration($pgosCdcEventsProcessingAttempt);
            sleep($sleepDuration);

            $connections = [Mode::LIVE, Connection::ASV_WRITER, Mode::TEST];
            $causedByLostConnectionAtleastOnce = false;

            foreach ($connections as $connection)
            {
                $causedByLostConnection = (new MySqlConnector($app))->checkAndReloadDBIfCausedByLostConnection($e, $connection);

                if ($causedByLostConnection) {
                    $causedByLostConnectionAtleastOnce = true;
                }

            }

            if ($causedByLostConnectionAtleastOnce) {
                $this->trace->error(TraceCode::EXCEPTION_CAUSED_BY_LOST_DB_CONNECTION, [
                    'code'      => $e->getCode(),
                    'message'   => $e->getMessage(),
                    'caused_by_lost_connection'  => true,
                    'reloaded_connections'       => array_keys(DB::getConnections())
                ]);
                throw $e;
            }
        }
        catch (\Throwable $e)
        {
            //For unknown errors, we want to reattempt until successful, or else lag increases to trigger an alert.
            //We are re-attempting for Query exception now, will re-attempt for unknown errors in the next phase.
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
                    'attempt'         => $this->attempts(),
                    'retry'           => false
                ]);
            }
        }
    }


    /**
     * Increment the retry count.
     *
     * @param string $redisKey
     * @param string $attribute
     * @return int
     */
    public function incrementKafkaMessageProcessingAttempt(string $redisKey, string $attribute): int
    {
        $pgosCdcEventsProcessingAttemptKey = $attribute . $redisKey;

        $pgosCdcEventsProcessingAttempt = $this->cache->get($pgosCdcEventsProcessingAttemptKey) ?? 0;

        $this->cache->put($pgosCdcEventsProcessingAttemptKey, $pgosCdcEventsProcessingAttempt + 1, self::PGOS_CDC_EVENTS_PROCESSING_ATTEMPT_COUNT_TTL_IN_SEC);

        return $pgosCdcEventsProcessingAttempt + 1;
    }


    // Function to determine sleep time based on attempts
    private function getSleepDuration($attempt)
    {
        $shorterDelays = [2, 5, 10]; // in seconds

        // Use shorter delays for initial attempts, then increase linearly
        if ($attempt <= count($shorterDelays)) {
            return $shorterDelays[$attempt - 1];
        }


        // Cap the sleep duration at 30 seconds for larger attempts
        return min(($attempt - count($shorterDelays)) * 60, 30);
    }

}
