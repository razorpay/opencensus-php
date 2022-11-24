<?php

namespace RZP\Models\FundTransfer\Rbl\Request;

use Config;
use \WpOrg\Requests\Hooks as Requests_Hooks;

use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Initiator\ApiProcessor;
use RZP\Models\FundTransfer\Rbl\Reconciliation\Status;

abstract class Base extends ApiProcessor
{
    const TIMEOUT           = '240';

    const MAKER_ID          = 'M001';

    const CHECKER_ID        = 'C001';

    const APPROVER_ID       = 'A001';

    const ACCOUNT_NAME      = 'RAZORPAY SOFTWARE PRIVATE LIMITED';

    // Identifiers used store the response data
    const PAYMENT_REF_NO        = 'payment_ref_no';
    const UTR                   = 'utr';
    const BANK_STATUS_CODE      = 'bank_status_code';
    const PAYMENT_DATE          = 'payment_date';
    const RRN                   = 'rrn';
    const REFERENCE_NUMBER      = 'reference_number';
    const REMARKS               = 'remarks';
    const PUBLIC_FAILURE_REASON = 'public_failure_reason';

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

    public function __construct(string $purpose = null)
    {
        parent::__construct($purpose);

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

        //
        // Intentionally setting verify to null, so Requests does not use its default
        // cacert (which is outdated), and curl ends up using the OS cacert by default.
        //
        // Ref:
        // [1] Requests::get_default_options
        // [2] Requests_Transport_cURL -> requesst
        //

        $options = [
            'hooks'     => $hooks,
            'timeout'   => self::TIMEOUT,
            'auth'      => [
                $this->config['username'],
                $this->config['password'],
            ],
            'idn'       => false,
            'verify'    => null,
        ];

        return $options;
    }

    public function setCurlSslOpts($curl)
    {
        curl_setopt($curl, CURLOPT_SSLCERT, $this->getClientCertificate());

        curl_setopt($curl, CURLOPT_SSLKEY, $this->getClientCertificateKey());
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function processResponse($response): array
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

        $isSuccessResponse = $this->isValidSuccessResponse($response);

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

    public function processGatewayResponse(array $response): array
    {
        throw new LogicException("haven't implemented this yet. shouldn't have been called");
    }

    public function getRequestInputForGateway(): array
    {
        throw new LogicException("haven't implemented this yet. shouldn't have been called");
    }

    public function getActionForGateway(): string
    {
        throw new LogicException("haven't implemented this yet. shouldn't have been called");
    }

    /**
     * Validates if the current request was executed successfully or not
     *
     * @param \WpOrg\Requests\Response $response
     *
     * @return bool
     */
    public function isValidSuccessResponse($response): bool
    {
        $response = json_decode($response->body, true);

        $responseBody = $response[$this->responseIdentifier];

        $failedStatus = Status::getFailureStatus();

        if ((isset($responseBody['Header']['Status']) === false) or
            (Status::inStatus($failedStatus, $responseBody['Header']['Status']) === true))
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
    protected function mockResponseGenerator(array $input): string
    {
        // Currently code wont go in this block.
        if ((isset($input['failed_response']) === true) and
            ($input['failed_response'] === '1'))
        {
            $content = $this->mockGenerateFailedResponse();
        }
        else
        {
            $content = $this->mockGenerateSuccessResponse();
        }

        return $content;
    }

    protected function mockGenerateSuccessResponseForGateway(): array
    {
        throw new LogicException("haven't implemented this yet. shouldn't have been called");
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
     * @return string
     */
    protected abstract function mockGenerateFailedResponse(): string;

    /**
     * Generates failed response for given request
     *
     * @return string
     */
    protected abstract function mockGenerateSuccessResponse(): string;

    protected function mockGenerateFailedResponseForGateway(): array
    {
        throw new LogicException("haven't implemented this yet. shouldn't have been called");
    }

    protected function mockResponseGeneratorForGateway(array $input): array
    {
        throw new LogicException("haven't implemented this yet. shouldn't have been called");
    }
}
