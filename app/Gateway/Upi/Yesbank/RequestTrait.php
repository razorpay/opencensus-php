<?php

namespace RZP\Gateway\Upi\Yesbank;

use Request;
use Requests_Hooks;
use RZP\Trace\TraceCode;

trait RequestTrait
{
    protected function sendGatewayRequest($request)
    {
        $username = $this->getGatewayUsername();
        $password = $this->getGatewayPassword();

        $request['options'] = $this->getRequestOptions();

        $request['options']['auth'] = [$username, $password];

        $request['headers'] = [
            'X-IBM-Client-Id'       => $this->getClientId(),
            'X-IBM-Client-Secret'   => $this->getClientSecret()
        ];

        return parent::sendGatewayRequest($request);
    }

    protected function getRequestOptions()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        $options = [
            'hooks' => $hooks
        ];

        return $options;
    }

    public function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_SSLCERT, $this->getClientCertificate());

        curl_setopt($curl, CURLOPT_SSLKEY, $this->getClientSslKey());
    }

    protected function getClientCertificate()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        $clientCertPath = $gatewayCertPath . '/' .
            $this->getClientCertificateName();

        if (file_exists($clientCertPath) === false)
        {
            $cert = $this->config['client_cert'];

            $cert = str_replace('\n', "\n", $cert);

            file_put_contents($clientCertPath, $cert);

            $this->trace->info(
                TraceCode::CLIENT_CERTIFICATE_FILE_GENERATED,
                [
                    'gateway'        => $this->gateway,
                    'clientCertPath' => $clientCertPath
                ]);
        }

        return $clientCertPath;
    }

    protected function getClientSslKey()
    {
        $gatewayCertPath = $this->getGatewayCertDirPath();

        $clientCertPath = $gatewayCertPath . '/' .
            $this->getClientSslKeyName();

        if (file_exists($clientCertPath) === false)
        {
            $cert = $this->config['cert_key'];

            $cert = str_replace('\n', "\n", $cert);

            file_put_contents($clientCertPath, $cert);

            $this->trace->info(
                TraceCode::CLIENT_CERTIFICATE_FILE_GENERATED,
                [
                    'gateway'        => $this->gateway,
                    'clientCertPath' => $clientCertPath
                ]);
        }

        return $clientCertPath;
    }

    public function getClientCertificateName()
    {
        return 'client_cert_v2.crt';
    }

    public function getClientSslKeyName()
    {
        return 'client_cert_v1.key';
    }

    protected function getGatewayCertDirName()
    {
        return $this->config[self::CERTIFICATE_DIRECTORY_NAME];
    }

    protected function getGatewayMerchantId()
    {
        return 'UPI000000000001';
    }

    protected function getGatewayUsername(): string
    {
        if ($this->isTestMode() === true)
        {
            return $this->config['test_username'];
        }

        return $this->config['live_username'];
    }

    protected function getGatewayPassword(): string
    {
        if ($this->isTestMode() === true)
        {
            return $this->config['test_terminal_password'];
        }

        // This is set on all environments, we will be using this regardless of auth
        return $this->config['gateway_terminal_password'];
    }

    protected function getClientId()
    {
        if ($this->isTestMode() === true)
        {
            return $this->config['test_client_id'];
        }

        // This is set on all environments, we will be using this regardless of auth
        return $this->config['live_client_id'];
    }

    protected function getClientSecret()
    {
        if ($this->isTestMode() === true)
        {
            return $this->config['test_client_secret'];
        }

        // This is set on all environments, we will be using this regardless of auth
        return $this->config['live_client_secret'];
    }
}
