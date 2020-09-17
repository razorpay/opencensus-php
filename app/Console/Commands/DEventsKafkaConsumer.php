<?php

namespace RZP\Console\Commands;

use RdKafka\Conf;
use RdKafka\Message;
use RdKafka\KafkaConsumer;

use Matrix\Exception;

use Illuminate\Console\Command;

use RZP\Services\KafkaMessageProcessor;

use function Matrix\trace;

class DEventsKafkaConsumer extends Command
{
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

        $consumer = new KafkaConsumer($this->getConfig());

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
                        $isProcessed = $this->processMessage($message);
                        // Commit offsets asynchronously
                        if ($isProcessed === true)
                        {
                            $consumer->commitAsync($message);
                        }
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

    /**
     * Get kafka consumer config for the cluster
     *
     * Exports the cert to /opt/razorpay/certs path from env variables
     *
     * @return Conf
     */
    protected function getConfig(): Conf
    {
        $conf = new Conf();

        //set client as api-kafka
        $conf->set('client.id', 'api-kafka');

        // Configure the group.id. All consumer with the same group.id will consume
        // different partitions.
        $conf->set('group.id', env('QUEUE_KAFKA_COSUMER_GROUP'));

        $certsPath = env('QUEUE_KAFKA_CONSUMER_CERTS_PATH');

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', env('QUEUE_KAFKA_CONSUMER_BROKERS'));


        $tlsEnabled = env('QUEUE_KAFKA_CONSUMER_TLS_ENABLED', 'false');

        $sslCertificationVerification = ($tlsEnabled === true) ? 'true' : 'false';

        $conf->set('enable.ssl.certificate.verification', $sslCertificationVerification);

        //Set Security Protocol to ssl, needs ca-cert for ssl handle-shake
        $conf->set('security.protocol', 'ssl');

        $kafkaUserCertString = trim(str_replace('\n', "\n",
                                            env('QUEUE_KAFKA_CONSUMER_USER_CERTIFICATE', '')));


        $kafkaUserKeyString = trim(str_replace('\n', "\n",
                                    env('QUEUE_KAFKA_CONSUMER_USER_KEY', '')));


        $kafkaCaCertString = trim(str_replace('\n', "\n",
            env('QUEUE_KAFKA_CONSUMER_CA_CERT', '')));


        // export pem format cert to kafka_ca_cert.cer, pass the file path to ssl.ca.location
        // ca-cert is used verify the broker key.
        if ((empty($kafkaCaCertString) === false) and
            (empty($kafkaUserCertString) === false) and (empty($kafkaUserKeyString) === false))
        {
            $kafkaCaCertFileName = 'kafka_ca_cert.pem';

            $kafkaUserCertFileName = 'kafka_user_cert.crt';

            $kafkaUserKeyFileName = 'kafka_user_key.key';

            $kafkaCaCertFilePath = $certsPath . '/' . $kafkaCaCertFileName;

            $isCaCertExportSuccess = openssl_x509_export_to_file($kafkaCaCertString, $kafkaCaCertFilePath);

            if ($isCaCertExportSuccess === false)
            {
                $this->error('failed to export ca-cert into file path');
            }

            $conf->set('ssl.ca.location', $kafkaCaCertFilePath);

            $kafkaUserCertFilePath = $certsPath . '/' . $kafkaUserCertFileName;

            $isUserCertExportSuccess = openssl_x509_export_to_file($kafkaUserCertString, $kafkaUserCertFilePath);

            if ($isUserCertExportSuccess === false)
            {
                $this->error('failed to export user cert into file path');
            }

            $conf->set('ssl.certificate.location', $kafkaUserCertFilePath);

            $kafkaUserKeyFilePath = $certsPath . '/' . $kafkaUserKeyFileName;

            $isUserCertExportSuccess = openssl_pkey_export_to_file($kafkaUserKeyString, $kafkaUserKeyFilePath);

            if ($isUserCertExportSuccess === false)
            {
                $this->error('failed to export user key into file path');
            }

            $conf->set('ssl.key.location', $kafkaUserKeyFilePath);

        }

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
        $conf->set('auto.offset.reset', 'smallest');

        $isDebugModeEnable = env('QUEUE_KAFKA_ENABLE_DEBUG_MODE', 'false');

        if ($isDebugModeEnable === true)
        {
            $conf->set('debug', 'consumer,broker');
        }

        return $conf;
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
        //Call Processor for processing the message.

        $this->info('processing message from - '.
            $kafkaMessage->topic_name. ' topic with payload - '. $kafkaMessage->payload);

        $appEnv = env('APP_ENV', 'production');

        $topic = str_replace($appEnv . '-', '', $kafkaMessage->topic_name);

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
