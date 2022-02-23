<?php

namespace RZP\Console\Commands;

use RdKafka\Conf;
use RdKafka\Message;
use RdKafka\KafkaConsumer;

use Matrix\Exception;

use Illuminate\Console\Command;

use RZP\Services\KafkaMessageProcessor;

use RZP\Services\KafkaTrait;
use function Matrix\trace;

class DEventsKafkaConsumer extends Command
{
    use KafkaTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consume
                            {mode      : Database & application mode the command will run in (test|live)}
                            {topics*   : KafkaTopics to be consumed   }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * application mode test or live
     *
     * @var
     */

    protected $mode;

    /**
     * message processor for kafka topics
     * @var
     */

    protected $messageProcessor;

    const DEFAULT_CONSUMER_POLL_TIMEOUT_MS = 120000;

    /**
     * Create a new command instance.
     *
     * @param KafkaMessageProcessor $processor
     */
    public function __construct(KafkaMessageProcessor $processor)
    {
        parent::__construct();

        $this->messageProcessor = $processor;
    }

    /**
     * Executes the kafka:consume console command.
     *
     * @return mixed
     * @throws \RdKafka\Exception
     */
    public function handle()
    {
        $this->info("starting kafka consumer");

        $conf = $this->getKafkaConsumerConfig();

        $consumer = new KafkaConsumer($conf);

        $topics = $this->argument('topics');

        $this->mode = $this->argument('mode');

        $consumer->subscribe($topics);

        $consumerPollTimeoutMs = env('QUEUE_KAFKA_CONSUMER_POLL_TIMEOUT',
                            self::DEFAULT_CONSUMER_POLL_TIMEOUT_MS);

        while (true)
        {
            try
            {
                $message = $consumer->consume($consumerPollTimeoutMs);

                switch ($message->err)
                {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:

                        $isProcessedSuccessfully = $this->processMessage($message);

                        $this->handlePostProcessing($isProcessedSuccessfully, $message, $consumer, $topics);

                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        echo "No more messages; will wait for more\n";
                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        break;
                    default:
                        throw new Exception($message->errstr(), $message->err);
                }
            }
            catch (Exception $e)
            {
                $this->error('failed to consume topics from kafka');
            }
        }
    }

    public function getKafkaConsumerConfig() : Conf
    {
        $conf = $this->getConfig();

        // Configure the group.id. All consumer with the same group.id will consume
        // different partitions.
        $conf->set('group.id', env('QUEUE_KAFKA_COSUMER_GROUP'));

        return $conf;

    }

    /**
     * @param bool $isProcessed
     * @param      $message
     * @param      $consumer
     * @param      $topics
     */
    protected function handlePostProcessing(bool $isProcessed, $message, $consumer, $topics): void
    {
        //
        // Commit offsets synchronously if processing is complete
        //
        if ($isProcessed === true)
        {
            $this->info("Message Successfully Processed, Committing Offset - " .
                        $message->offset . " Partition - " . $message->partition);

            $consumer->commit($message);
        }
        else
        {
            $consumer->unsubscribe();

            $consumer->subscribe($topics);

            $this->info("Message processing failed, retrying" . " Offset -" .
                        $message->offset . " Partition - " . $message->partition);
        }
    }

    /**
     * Process Kafka message with kafkaMessageProcessor
     *
     * @param Message $kafkaMessage
     *
     * @return bool TRUE if message is processed,
     * FALSE if there is any system error in processing message.
     *
     * if payload is malformed, it acknowledges (returns true)
     * to the kafka queue that the message is processed.
     */
    protected function processMessage(Message $kafkaMessage): bool
    {
        $payload = $this->decodeKafkaMessage($kafkaMessage);

        // if any invalid payload, acknowledge queue.
        if ($payload === false)
        {
            $this->info('invalid kafka message payload - json malformed');

            return true;
        }
        // Call Processor for processing the message.

        $this->info('processing message from - '.
            $kafkaMessage->topic_name. ' topic with payload - '. $kafkaMessage->payload);

        $appMode = env('APP_MODE', 'prod');

        $topic = str_replace($appMode . '-', '', $kafkaMessage->topic_name);

        $devstack_label = env('DEVSTACK_LABEL', '');

        if ($devstack_label != '')
        {
            $topic = str_replace('-' . $devstack_label, '', $topic);
        }

        $isProcessed = $this->messageProcessor->process($topic, $payload, $this->mode);

        $infoMessage = ($isProcessed === true) ? 'successful' : 'failed';

        $this->info('message processing - '.$infoMessage);

        return $isProcessed;

    }

    /**
     * Decode kafka message
     *
     * @param Message $kafkaMessage
     *
     * @return mixed - array|false
     * array is when json_decode is success,
     * false is return if  $kafkaMessage is not in json format
     */
    protected function decodeKafkaMessage(Message $kafkaMessage)
    {
        $payload = json_decode($kafkaMessage->payload, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            $this->error('invalid payload received from kafka broker');
            return false;
        }
        return $payload;
    }
}
