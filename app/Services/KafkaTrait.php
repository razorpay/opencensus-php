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
        if(str_contains(env('QUEUE_KAFKA_CONSUMER_BROKERS'),'devserve-kafka-msk.np.razorpay.vpc:9094')){
            return $this->getConfigForMSK();
        }

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

    protected function getConfigForMSK(){
        $conf = new Conf();

        //set client as api-kafka
        $conf->set('client.id', 'api-kafka');

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', env('QUEUE_KAFKA_CONSUMER_BROKERS'));

        $tlsEnabled = env('QUEUE_KAFKA_CONSUMER_TLS_ENABLED', 'false');

        $sslCertificationVerification = ($tlsEnabled === true) ? 'true' : 'false';

        $kafkaUserCertString = trim(str_replace('\n', "\n",
                                                env('QUEUE_KAFKA_MSK_CONSUMER_USER_CERTIFICATE', '')));

        $kafkaUserKeyString = trim(str_replace('\n', "\n",
                                               env('QUEUE_KAFKA_MSK_CONSUMER_USER_KEY', '')));

        $kafkaCaCertString = trim(str_replace('\n', "\n",
                                              env('QUEUE_KAFKA_MSK_CONSUMER_CA_CERT', '')));
        $conf->set('enable.ssl.certificate.verification', $sslCertificationVerification);

        //Set Security Protocol to ssl, needs ca-cert for ssl handle-shake
        $conf->set('security.protocol', 'ssl');

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
                $this->mergeCaCerts($kafkaCaCertFilePath);
            }

            $conf->set('ssl.ca.location', $kafkaCaCertFilePath);

            $kafkaUserCertFilePath = $certsPath . '/' . $kafkaUserCertFileName;

            if (file_exists($kafkaUserCertFilePath) === false)
            {
                $isUserCertExportSuccess = file_put_contents($kafkaUserCertFilePath, $kafkaUserCertString);

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

    protected function mergeCaCerts($kafkaCaCertFilePath)
    {
        $additionalCaCertPaths = [
            '/etc/ssl/certs/ca-cert-Amazon_Root_CA_1.pem',
            '/etc/ssl/certs/ca-cert-Amazon_Root_CA_2.pem',
            '/etc/ssl/certs/ca-cert-Amazon_Root_CA_3.pem',
            '/etc/ssl/certs/ca-cert-Amazon_Root_CA_4.pem',
        ];

        // Read the contents of the Kafka CA certificate file
        $kafkaCaCert = file_get_contents($kafkaCaCertFilePath);

        // Initialize combined certificate content with Kafka CA certificate
        $combinedCaCert = $kafkaCaCert;

        // Read and combine additional CA certificates
        foreach ($additionalCaCertPaths as $caCertPath) {
            $caCert = file_get_contents($caCertPath);

            if ($caCert === false) {

                $this->error('failed to export amazon ca-cert into file path');

                return;
            }

            $combinedCaCert .= PHP_EOL . $caCert;
        }

        // Write the combined certificates back to the Kafka CA certificate file
        if (file_put_contents($kafkaCaCertFilePath, $combinedCaCert) === false)
        {
            $this->error('failed to export amazon combined ca-cert into file path');
        }
    }
}
