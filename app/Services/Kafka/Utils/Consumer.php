<?php

namespace RZP\Services\Kafka\Utils;

use RdKafka\Message;
use RdKafka\Conf;

use App;
use RZP\Error\ErrorCode;
use RZP\Exception\ServerErrorException;

class Consumer
{
    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    public function getConsumerPollTimeoutMs()
    {
        return config('kafka_consumer.consumer_poll_timeout_ms');
    }

    public function decodeKafkaMessage(Message $kafkaMessage)
    {
        $payload = json_decode($kafkaMessage->payload, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            $this->trace->error('invalid payload received from kafka broker');
            return false;
        }
        return $payload;
    }

    public function getNewConf(string $cluster): Conf
    {
        switch ($cluster)
        {
            case Constants::SHARED_CLUSTER :
                return $this->getNewConfForSharedCluster();
                break;

            case Constants::RX_CLUSTER :
                return $this->getNewConfForRXCluster();
                break;

            default:
                throw new ServerErrorException(
                    'Failed to get kafka cluster conf',
                    ErrorCode::SERVER_ERROR);
        }
    }

    public function getNewConfForSharedCluster()
    {
        $conf = new Conf();

        //set client as api-kafka
        $conf->set('client.id', config('kafka_consumer.client_id'));

        $conf->set('enable.auto.commit', 'false');

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

        //export pem format cert to kafka_ca_cert.cer, pass the file path to ssl.ca.location
        //ca-cert is used verify the broker key.
        if ((empty($kafkaCaCertString) === false) and
            (empty($kafkaUserCertString) === false) and
            (empty($kafkaUserKeyString) === false))
        {
            $kafkaCaCertFileName = 'kafka_ca_cert.pem';

            $kafkaUserCertFileName = 'kafka_user_cert.crt';

            $kafkaUserKeyFileName = 'kafka_user_key.key';

            $kafkaCaCertFilePath = $certsPath . '/' . $kafkaCaCertFileName;

            $isCaCertExportSuccess = openssl_x509_export_to_file($kafkaCaCertString, $kafkaCaCertFilePath);

            if ($isCaCertExportSuccess === false)
            {
                $this->trace->error('failed to export ca-cert into file path');
            }

            $conf->set('ssl.ca.location', $kafkaCaCertFilePath);

            $kafkaUserCertFilePath = $certsPath . '/' . $kafkaUserCertFileName;

            $isUserCertExportSuccess = openssl_x509_export_to_file($kafkaUserCertString, $kafkaUserCertFilePath);

            if ($isUserCertExportSuccess === false)
            {
                $this->trace->error('failed to export user cert into file path');
            }

            $conf->set('ssl.certificate.location', $kafkaUserCertFilePath);

            $kafkaUserKeyFilePath = $certsPath . '/' . $kafkaUserKeyFileName;

            $isUserCertExportSuccess = openssl_pkey_export_to_file($kafkaUserKeyString, $kafkaUserKeyFilePath);

            if ($isUserCertExportSuccess === false)
            {
                $this->trace->error('failed to export user key into file path');
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

    public function getNewConfForRXCluster()
    {
        $conf = new Conf();

        //set client as api-kafka
        $conf->set('client.id', config('kafka_consumer.client_id'));

        $conf->set('enable.auto.commit', 'false');

        $certsPath = env('QUEUE_KAFKA_CONSUMER_CERTS_PATH');

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', env('QUEUE_KAFKA_CONSUMER_BROKERS_RX_CLUSTER'));

        $tlsEnabled = env('QUEUE_KAFKA_CONSUMER_TLS_ENABLED', 'false');

        $sslCertificationVerification = ($tlsEnabled === true) ? 'true' : 'false';

        $conf->set('enable.ssl.certificate.verification', $sslCertificationVerification);

        //Set Security Protocol to ssl, needs ca-cert for ssl handle-shake
        $conf->set('security.protocol', 'ssl');

        $kafkaUserCertString = trim(str_replace('\n', "\n",
                                                env('QUEUE_KAFKA_CONSUMER_USER_CERTIFICATE_RX_CLUSTER', '')));

        $kafkaUserKeyString = trim(str_replace('\n', "\n",
                                               env('QUEUE_KAFKA_CONSUMER_USER_KEY_RX_CLUSTER', '')));

        $kafkaCaCertString = trim(str_replace('\n', "\n",
                                              env('QUEUE_KAFKA_CONSUMER_CA_CERT_RX_CLUSTER', '')));

        //export pem format cert to kafka_ca_cert.cer, pass the file path to ssl.ca.location
        //ca-cert is used verify the broker key.
        if ((empty($kafkaCaCertString) === false) and
            (empty($kafkaUserCertString) === false) and
            (empty($kafkaUserKeyString) === false))
        {
            $kafkaCaCertFileName = 'kafka_ca_cert.pem';

            $kafkaUserCertFileName = 'kafka_user_cert.crt';

            $kafkaUserKeyFileName = 'kafka_user_key.key';

            $kafkaCaCertFilePath = $certsPath . '/' . $kafkaCaCertFileName;

            $isCaCertExportSuccess = openssl_x509_export_to_file($kafkaCaCertString, $kafkaCaCertFilePath);

            if ($isCaCertExportSuccess === false)
            {
                $this->trace->error('failed to export ca-cert into file path');
            }

            $conf->set('ssl.ca.location', $kafkaCaCertFilePath);

            $kafkaUserCertFilePath = $certsPath . '/' . $kafkaUserCertFileName;

            $isUserCertExportSuccess = openssl_x509_export_to_file($kafkaUserCertString, $kafkaUserCertFilePath);

            if ($isUserCertExportSuccess === false)
            {
                $this->trace->error('failed to export user cert into file path');
            }

            $conf->set('ssl.certificate.location', $kafkaUserCertFilePath);

            $kafkaUserKeyFilePath = $certsPath . '/' . $kafkaUserKeyFileName;

            $isUserCertExportSuccess = openssl_pkey_export_to_file($kafkaUserKeyString, $kafkaUserKeyFilePath);

            if ($isUserCertExportSuccess === false)
            {
                $this->trace->error('failed to export user key into file path');
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
}
