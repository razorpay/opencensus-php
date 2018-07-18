<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;

use Config;
use Requests_Hooks;

use RZP\Models\Base as BaseModel;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Base\Initiator\RequestProcessor;

abstract class Base extends RequestProcessor
{
    const TIMEOUT = 30;

    protected $appId;

    protected $version;

    protected $baseUrl;

    protected $customerId;

    protected $accountNumber;

    protected $entity = null;

    public function __construct()
    {
        parent::__construct();

        $this->channel = Channel::YESBANK;

        $this->config = Config::get('nodal.yesbank');

        $this->appId = $this->config['app_id'];

        $this->accountNumber = $this->config['account_number'];

        $this->baseUrl = $this->config['url'];

        $this->accountNumber = $this->config['account_number'];

        $this->appId = $this->config['app_id'];

        $this->customerId = $this->config['customer_id'];

        $this->method = 'POST';

        $this->version = '1';

        $this->init();
    }

    protected function init()
    {
        $this->response = null;

        return $this;
    }

    public function requestUrl(): string
    {
        return $this->baseUrl . $this->urlIdentifier;
    }

    public function requestMethod(): string
    {
        return $this->method;
    }

    public function requestHeaders(): array
    {
        return [
            'Content-Type'        => 'application/xml',
            'X-IBM-Client-Id'     => $this->config['client_id'],
            'X-IBM-Client-Secret' => $this->config['client_password'],
        ];
    }

    /**
     * {@inheritdoc}
     */
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

    public function setCurlSslOpts($curl)
    {
        curl_setopt($curl, CURLOPT_SSLCERT, $this->getClientCertificate());

        curl_setopt($curl, CURLOPT_SSLKEY, $this->getClientCertificateKey());
    }

    public function processResponse(\Requests_Response $response): array
    {
        // TODO: fund transfer and status request process will go here
        return [];
    }

    public function setEntity(BaseModel\Entity $entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Generate the mock response for the given class.
     *
     * @param array $input config params
     *
     * @return string
     */
    protected function mockResponseGenerator(array $input): string
    {
        if ((isset($input['failed_response']) === true) and
            ($input['failed_response'] === '1'))
        {
            return $this->mockGenerateFailedResponse();
        }

        return $this->mockGenerateSuccessResponse();
    }

    /**
     * Generates successful response for given request
     *
     * @return string
     */
    protected abstract function mockGenerateFailedResponse(): string;

    /**
     * Generates failed response for given request
     *
     * @return string
     */
    protected abstract function mockGenerateSuccessResponse(): string;
}
