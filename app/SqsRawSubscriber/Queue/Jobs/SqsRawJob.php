<?php

namespace RZP\SqsRawSubscriber\Queue\Jobs;

use App;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Str;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Container\Container;
use Illuminate\Queue\CallQueuedHandler;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\RazorxTreatment;

class SqsRawJob extends SqsJob
{
    /**
     * Create a new job instance.
     *
     * @param Container $container
     * @param SqsClient $sqs
     * @param string $queue
     * @param array $job
     * @param string $connectionName
     * @param string $prefix
     * @return void
     */
    public function __construct(
        Container $container,
        SqsClient $sqs,
        array $job,
        $connectionName,
        $queue,
        string $prefix)
    {
        parent::__construct($container, $sqs, $job, $connectionName, $queue);

        $this->job = $this->resolveQueueSubscription($this->job, $prefix);
    }

    /**
     * Resolves raw queue messages
     *
     * @param array $job
     * @param string $prefix
     * @param array $routes
     * @return array
     */
    protected function resolveQueueSubscription(array $job, string $prefix)
    {
        $body = $this->payload();

        $commandName = null;

        $queueName = $this->getQueueName($prefix);

        $commandName = $this->getJobClass($queueName);

        if ($commandName !== null)
        {
            // If there is a command available, we resolve the job instance for it from
            // the service container, passing in the payload of the sqs message.

            $jobInstance = $this->makeCommand($commandName, $body);

            // The instance for the job will then be serialized and the body of
            // the job is reconstructed.

            $command = serialize($jobInstance);

            $jobBodySet = false;

            if ($commandName == 'RZP\\Jobs\\ArtReconProcess')
            {
                try
                {
                    $app = App::getFacadeRoot();

                    $variant = $app->razorx->getTreatment(
                        'ArtReconProcess',
                        RazorxTreatment::ADD_TIMEOUT_FOR_SQS_RAW,
                        Mode::LIVE
                    );

                    app('trace')->info(TraceCode::ART_RECON_SQS_RAW_VARIANT, [
                        'key'         => 'ArtReconProcess',
                        'variant'     => $variant,
                        'timeout_set' => $jobInstance->timeout ?? null
                    ]);

                    /**
                     * While initialising timeout for the message, we either set the timeout from the
                     * payload or from the options we set while executing the command queue:work.
                     *
                     * Passing timeout in the payload, so that it doesn't default back to 60s
                     */
                    if ($variant === RazorxTreatment::RAZORX_VARIANT_ON)
                    {
                        $job['Body'] = json_encode(
                            [
                                'displayName' => $commandName,
                                'uuid'        => (string) Str::uuid(),
                                'timeout'     => $jobInstance->timeout ?? null,
                                'job'         => CallQueuedHandler::class . '@call',
                                'data'        => compact('commandName', 'command'),
                            ]);

                        $jobBodySet = true;
                    }
                }
                catch (\Throwable $exception)
                {
                    app('trace')->traceException($exception, Trace::ERROR, TraceCode::ART_RECON_SQS_RAW_VARIANT);
                }
            }

            if ($jobBodySet === false)
            {
                $job['Body'] = json_encode(
                    [
                        'displayName' => $commandName,
                        'job'         => CallQueuedHandler::class . '@call',
                        'data'        => compact('commandName', 'command'),
                    ]);
            }
        }

        return $job;
    }

    /**
     * Make the serialized command.
     *
     * @param string $commandName
     * @param array $body
     */
    protected function makeCommand(string $commandName, array $body)
    {
        if($commandName == 'RZP\\Jobs\\MerchantFirsDocuments' or
            $commandName == 'RZP\\Jobs\\ArtReconProcess')
        {
            $payload = $body;
        }
        else{
            $payload = json_decode($body['Message'], true);
        }

        $data = [
            'payload' => $payload
        ];

        return $this->container->make($commandName, $data);
    }

    /**
     * Get the the name of queue from which the message has been received.
     *
     * @param string $prefix
     * @return string
     */
    protected function getQueueName(string $prefix): string
    {
        $queueName = str_replace($prefix, '', $this->queue);
        $queueName = ltrim($queueName, '/');

        return $queueName;
    }

    /**
     * Get the underlying raw SQS job.
     *
     * @return array
     */
    protected function getSqsRawJob(): array
    {
        return $this->job;
    }

    /**
     * Get the job class from queue.
     *
     * @param string $queueName
     * @return string
     */
    protected function getJobClass(string $queueName): string
    {
        $key = "queue.raw_sqs_mappings.{$queueName}";

        return config($key);
    }
}
