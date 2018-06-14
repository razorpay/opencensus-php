<?php

namespace RZP\Models\FundTransfer\Rbl\Request;

use Config;
use Requests_Hooks;

use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Initiator\RequestProcessor;
use RZP\Models\FundTransfer\Rbl\Reconciliation\Status;

abstract class Base extends RequestProcessor
{
    const TIMEOUT           = '240';

    const MAKER_ID          = 'M001';

    const CHECKER_ID        = 'C001';

    const APPROVER_ID       = 'A001';

    const ACCOUNT_NAME      = 'RAZORPAY SOFTWARE PRIVATE LIMITED';

    // Identifiers used store the response data
    const PAYMENT_REF_NO    = 'payment_ref_no';
    const UTR               = 'utr';
    const BANK_STATUS_CODE  = 'bank_status_code';
    const PAYMENT_DATE      = 'payment_date';
    const RRN               = 'rrn';
    const REFERENCE_NUMBER  = 'reference_number';
    const REMARK            = 'remark';

    protected $baseUrl;

    protected $corpId;

    protected $channel;

    protected $username;

    protected $password;

    protected $clientCreds;

    protected $accountNumber;

    protected $urlIdentifier;

    protected $responseIdentifier;

    protected $headers = [];

    protected $options = [];

    protected $url = '';

    protected $method = 'POST';

    public function __construct()
    {
        parent::__construct();

        $this->channel = Channel::RBL;

        $this->config = Config::get('nodal.rbl');

        $this->init();
    }

    protected function init()
    {
        $this->response = null;

        $this->corpId = $this->config['username'];

        $this->accountNumber = $this->config['account_number'];

        $this->baseUrl = $this->config['url'];

        $clientCredArray = [
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_password'],
        ];

        $this->clientCreds = http_build_query($clientCredArray);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function requestUrl(): string
    {
        return $this->baseUrl . $this->urlIdentifier . $this->clientCreds;
    }

    /**
     * {@inheritdoc}
     */
    public function requestMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function requestHeaders(): array
    {
        return [
            'Content-Type' => 'application/json'
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
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function processResponse(\Requests_Response $response): array
    {
        $responseBody = json_decode($response->body, true);

        if (($response->status_code !== 200) or
            (isset($responseBody[$this->responseIdentifier]) === false))
        {
            throw new LogicException('Invalid response from api', null, $response);
        }

        $responseContent = $responseBody[$this->responseIdentifier];

        // Check if response has valid data keys which is required for the processing
        if (isset($responseContent['Header']) !== true)
        {
            throw new LogicException('Invalid response from api', null, $response);
        }

        $isSuccessResponse = $this->isValidSuccessResponse();

        //
        // For failed response there wont be body defined.
        // Checking for existence of `Body` because in case bank introduces new status code
        //
        if (($isSuccessResponse === true) and
            (isset($responseContent['Body']) === true))
        {
            return $this->extractSuccessfulData($responseContent);
        }
        else
        {
            return $this->extractFailedData($responseContent);
        }
    }

    /**
     * Validates if the current request was executed successfully or not
     *
     * @return bool
     */
    public function isValidSuccessResponse(): bool
    {
        $response = json_decode($this->response->body, true);

        $responseBody = $response[$this->responseIdentifier];

        $failedStatus = Status::getFailureStatus();

        if ((isset($responseBody['Header']['Status']) === false) or
            (in_array($responseBody['Header']['Status'], $failedStatus, true) === true))
        {
            return false;
        }

        return true;
    }

    /**
     * Return null when the value is empry
     *
     * @param $value
     *
     * @return null
     */
    protected function getNullOnEmpty($value)
    {
        return (empty($value) === true) ? null : $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function mockResponseGenerator(array $input): array
    {
        // Currently code wont go in this block.
        if ((isset($input['failed_response']) === true) and
            ($input['failed_response'] === '1'))
        {
            return $this->mockGenerateFailedResponse();
        }

        return $this->mockGenerateSuccessResponse();
    }

    /**
     * Extracts data from response when response received is a valid success response.
     * For success response `Body` attribute will be present and header.status wont we a failure status
     *
     * @param array $response
     *
     * @return array
     *
     * sample response :
     * [
     *  'payment_ref_no'   => 'some reference',
     *  'bank_status_code' => 'bank status code',
     *  'payment_date'     => null,
     *  'reference_number' => null,
     *  'utr'              => null,
     *  'remark'           => 'failure reason'
     * ]
     */
    protected abstract function extractSuccessfulData(array $response): array;

    /**
     *
     * Extracts data from response when response received is a failure response.
     * Failure response are response without `Body` attribute and header.status will be any of failure status
     *
     * @param array $response
     *
     * @return array
     *
     * sample response :
     * [
     *  'payment_ref_no'   => 'some reference',
     *  'bank_status_code' => 'bank status code',
     *  'payment_date'     => null,
     *  'reference_number' => null,
     *  'utr'              => null,
     *  'remark'           => 'failure reason'
     * ]
     */
    protected abstract function extractFailedData(array $response): array;

    /**
     * Sets the entity for which the request has to be made
     *
     * @param Attempt\Entity $entity
     *
     * @return mixed
     */
    public abstract function setEntity(Attempt\Entity $entity);

    /**
     * Generates successful response for given request
     *
     * @return array
     */
    protected abstract function mockGenerateFailedResponse(): array;

    /**
     * Generates failed response for given request
     *
     * @return array
     */
    protected abstract function mockGenerateSuccessResponse(): array;
}
