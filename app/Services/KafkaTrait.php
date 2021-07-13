<?php

namespace RZP\Services;

use RdKafka\Conf;

trait KafkaTrait
{
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

        $conf->set('enable.auto.commit', 'false');

        // export pem format cert to kafka_ca_cert.cer, pass the file path to ssl.ca.location
        // ca-cert is used verify the broker key.
        if ((empty($kafkaCaCertString) === false) and
            (empty($kafkaUserCertString) === false) and (empty($kafkaUserKeyString) === false))
        {
            $kafkaCaCertFileName = 'kafka_ca_cert.pem';

            $kafkaUserCertFileName = 'kafka_user_cert.crt';

            $kafkaUserKeyFileName = 'kafka_user_key.key';

            $certsPath = env('QUEUE_KAFKA_CONSUMER_CERTS_PATH');

            $kafkaCaCertFilePath = $certsPath . '/' . $kafkaCaCertFileName;

            if (file_exists($kafkaCaCertFilePath) === false)
            {
                $isCaCertExportSuccess = openssl_x509_export_to_file($kafkaCaCertString, $kafkaCaCertFilePath);

                if ($isCaCertExportSuccess === false)
                {
                    $this->error('failed to export ca-cert into file path');
                }
            }

            $conf->set('ssl.ca.location', $kafkaCaCertFilePath);

            $kafkaUserCertFilePath = $certsPath . '/' . $kafkaUserCertFileName;

            if (file_exists($kafkaUserCertFilePath) === false)
            {
                $isUserCertExportSuccess = openssl_x509_export_to_file($kafkaUserCertString, $kafkaUserCertFilePath);

                if ($isUserCertExportSuccess === false)
                {
                    $this->error('failed to export user cert into file path');
                }
            }

            $conf->set('ssl.certificate.location', $kafkaUserCertFilePath);

            $kafkaUserKeyFilePath = $certsPath . '/' . $kafkaUserKeyFileName;

            if (file_exists($kafkaUserKeyFilePath) === false)
            {
                $isUserCertExportSuccess = openssl_pkey_export_to_file($kafkaUserKeyString, $kafkaUserKeyFilePath);

                if ($isUserCertExportSuccess === false)
                {
                    $this->error('failed to export user key into file path');
                }
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
