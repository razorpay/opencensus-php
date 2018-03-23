<?php

namespace RZP\Models\FundTransfer\Rbl\Request;

use Config;
use Requests_Hooks;

use RZP\Trace\TraceCode;
use RZP\Exception\RuntimeException;
use RZP\Models\FundTransfer\Base\Initiator\RequestProcessor;
use RZP\Models\FundTransfer\Rbl\Reconciliation\Status;

abstract class Base extends RequestProcessor
{
    const TIMEOUT           = '240';

    const CORP_ID           = 'RZPAYP';

    const MAKER_ID          = 'M001';

    const CHECKER_ID        = 'C001';

    const APPROVER_ID       = 'A001';

    const ACCOUNT_NAME      = 'RAZORPAY SOFTWARE PRIVATE LIMITED';

    protected $baseUrl;

    protected $channel;

    protected $username;

    protected $password;

    protected $clientCreds;

    protected $accountNumber;

    protected $urlIdentifier;

    protected $headers      = [];

    protected $options      = [];

    protected $url          = '';

    protected $method       = 'POST';

    public function __construct()
    {
        parent::__construct();

        $this->config = Config::get('nodal.rbl');

        $this->init();
    }

    protected function init()
    {
        $this->accountNumber = $this->config['account_number'];

        $this->baseUrl       = $this->config['url'];

        $clientCredArray     = [
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_password'],
        ];

        $this->clientCreds   = http_build_query($clientCredArray);
    }

    public function requestUrl(): string
    {
        return $this->baseUrl . $this->urlIdentifier . $this->clientCreds;
    }

    public function requestMethod(): string
    {
        return $this->method;
    }

    public function requestHeaders(): array
    {
        return [
            'Content-Type' => 'application/json'
        ];
    }

    public function requestOptions(): array
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlSslOpts']);

        $options = [
            'hooks'   => $hooks,
            'timeout' => self::TIMEOUT,
            'auth'    => [
                $this->config['username'],
                $this->config['password'],
            ],
            'idn'     => false,
        ];

        return $options;
    }

    public function setCurlSslOpts($curl)
    {
        curl_setopt($curl, CURLOPT_SSLCERT, $this->getClientCertificate());

        curl_setopt($curl, CURLOPT_SSLKEY, $this->getClientCertificateKey());
    }

    protected function getClientCertificate(): string
    {
        $certPath = $this->getGatewayCertDirPath();

        $certFile = $certPath . '/' . $this->getClientCertificateName();

        // Download cert file from vault if already not present and store locally
        if (file_exists($certFile) === false)
        {
            $cert = $this->config['client_certificate'];

            $cert = str_replace('\n', PHP_EOL, $cert);

            file_put_contents($certFile, $cert);
        }

        return $certFile;
    }

    protected function getClientCertificateKey(): string
    {
        $certPath = $this->getGatewayCertDirPath();

        $certFile = $certPath . '/' . $this->getClientCertificateKeyName();

        // Download cert key file from vault if already not present and store locally
        if (file_exists($certFile) === false)
        {
            $key = $this->config['client_certificate_key'];

            $key = str_replace('\n', PHP_EOL, $key);

            file_put_contents($certFile, $key);
        }

        return $certFile;
    }

    protected function getClientCertificateName(): string
    {
        return $this->config['certificate_name'];
    }

    protected function getGatewayCertDirPath(): string
    {
        return $this->config['certificate_path'];
    }

    protected function getClientCertificateKeyName(): string
    {
        return $this->config['certificate_key_name'];
    }

    /**
     * Parses the response of current request and checks if the response of valid
     *
     * @param \Requests_Response $response
     *
     * @return array
     *
     * @throws RuntimeException
     */
    public function processResponse(\Requests_Response $response): array
    {
        $responseBody = json_decode($response->body, true);

        if (($response->status_code !== 200) or
            (isset($responseBody['Single_Payment_Corp_Resp']) === false))
        {
            throw new RuntimeException('Invalid response from api', $response);
        }

        $responseContent = $responseBody['Single_Payment_Corp_Resp'];

        if ((empty($responseContent['Header']['Status']) === false) and
            ($responseContent['Header']['Status'] === Status::FAILURE))
        {
            $this->trace->error(TraceCode::RBL_NODAL_FAILED_RESPONSE, $response);
        }

        return $responseBody;
    }
}
