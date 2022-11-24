<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;

use Config;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\Base as BaseModel;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt\Type;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\FundTransfer\Base\Initiator\ApiProcessor;

abstract class Base extends ApiProcessor
{
    const TIMEOUT = 30;

    //Beneficiary default name,min and max length
    const BENE_MIN_LEN           = 5;
    const BENE_MAX_LEN           = 35;
    const BENE_BANK_MAX_LEN      = 128;
    const BENE_DEFAULT_NAME      = 'Not Available';
    const BENE_DEFAULT_BANK_NAME = 'bank';

    protected $appId;

    protected $version;

    protected $baseUrl;

    protected $customerId;

    protected $urlIdentifier;

    protected $accountNumber;

    protected $entity = null;

    protected $entityType;

    protected $requestIdentifier;

    protected $responseIdentifier;

    protected $isRequestFailure = false;

    const FAILED_RESPONSE                              = 'failed_response';
    const FAILED_RESPONSE_INSUFFICIENT_FUNDS           = 'failed_response_insufficient_funds';
    const FAILED_RESPONSE_BENEFICIARY_NOT_ACCEPTED     = 'failed_response_beneficiary_not_accepted';
    const FAILED_RESP_BENEFICIARY_DETAILS_INVALID      = 'failed_resp_beneficiary_details_invalid';

    const MOCK_FAILURE_RESPONSE_TYPE =
        [
            self::FAILED_RESPONSE,
            self::FAILED_RESPONSE_INSUFFICIENT_FUNDS,
            self::FAILED_RESPONSE_BENEFICIARY_NOT_ACCEPTED,
            self::FAILED_RESP_BENEFICIARY_DETAILS_INVALID
        ];

    public function __construct(string $type = null)
    {
        parent::__construct();

        $this->channel = Channel::YESBANK;

        $this->config = $this->loadNodalConfig($type);

        $this->appId = $this->config['app_id'];

        $this->accountNumber = $this->config['account_number'];

        $this->baseUrl = $this->config['url'];

        $this->appId = $this->config['app_id'];

        $this->customerId = $this->config['customer_id'];

        $this->method = 'POST';

        $this->version = '1';

        $this->init();
    }

    /**
     * Loads config based on the type specified.
     * If no type is provided then it load default configuration
     * which is async nodal config
     *
     * @param string $type
     * @return array
     */
    protected function loadNodalConfig(string $type): array
    {
        $this->trace->debug(
            TraceCode::NODAL_BEN_REGISTRATION_YESBANK_DEBUG,
            [
                'type'  => $type,
            ]
        );

        switch ($type)
        {
            case Type::PRIMARY:
                return Config::get('nodal.yesbank.primary');

            case Type::BANKING:
                return Config::get('nodal.yesbank.banking');

            case Type::SYNC:
                return Config::get('nodal.yesbank.sync');

            default:
                return Config::get('nodal.yesbank.primary');
        }
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
            'X-IBM-Client-Id'     => $this->config['client_id'],
            'X-IBM-Client-Secret' => $this->config['client_password'],
            'Content-Type'        => $this->getContentType()
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
            'timeout'   => $this->config['timeout'] ?? self::TIMEOUT,
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
     * {{@inheritdoc}}
     */
    public function processResponse($response): array
    {
        $responseBody = json_decode($response->body, true);

        $additionalInfo =  [
            'fund_transfer_attempt_id' => $this->entity->getId(),
            'settlement_id'            => $this->entity->getSourceId()
        ];

        if ($response->status_code !== 200)
        {
            $this->isRequestFailure = true;

            throw new LogicException(
                'Invalid response from api',
                null,
                [
                    'response' => $response
                ] + $additionalInfo);
        }

        if (isset($responseBody[Constants::FAULT_RESPONSE_IDENTIFIER]) === true)
        {
            $this->isRequestFailure = true;

            return $this->extractFailedData($responseBody[Constants::FAULT_RESPONSE_IDENTIFIER]);
        }
        else if (isset($responseBody[$this->responseIdentifier]) === true)
        {
            return $this->extractSuccessfulData($responseBody[$this->responseIdentifier]);
        }

        $this->isRequestFailure = true;

        throw new LogicException(
            'Invalid response from api',
            null,
            [
                'response' => $response
            ] + $additionalInfo);
    }

    public function processGatewayResponse(array $response): array
    {
        return $this->extractGatewayData($response);
    }

    /**
     * Gives the content type for the request
     *
     * @return string
     */
    protected function getContentType(): string
    {
        return 'application/json';
    }

    /**
     * Return null when the value is empty
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
     * Sets the entity on which operation has to be performed
     *
     * @param BaseModel\Entity $entity
     * @return $this
     */
    public function setEntity(BaseModel\Entity $entity)
    {
        $this->entity = $entity;

        $this->ftaId = $entity->getId();

        return $this;
    }

    /**
     * Sets Entity type
     *
     * @param $entityType
     * @return $this
     */
    public function setEntityType($entityType)
    {
        $this->entityType = $entityType;

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
        // This is to mock failed response for test cases of beneficiary for fund account
        if ((array_key_exists('contact_id', $input) === true) and
            ($input['contact_id'] === 'cont_invalidcontact'))
        {
            return $this->mockGenerateFailedResponse();
        }

        if ((isset($input[self::FAILED_RESPONSE]) === true) and
            ($input[self::FAILED_RESPONSE] === '1'))
        {
            return $this->mockGenerateFailedResponse();
        }

        if ((isset($input[self::FAILED_RESPONSE]) === true) and
            ($input[self::FAILED_RESPONSE] === 'merchant_error'))
        {
            return $this->mockGenerateFailedResponse($input[self::FAILED_RESPONSE]);
        }

        $possibleFailureReceipts = self::MOCK_FAILURE_RESPONSE_TYPE;

        if (($this->entity->source instanceof Entity) and
            (in_array($this->entity->source->getReceipt(), $possibleFailureReceipts) === true))
        {
            return $this->mockGenerateFailedResponse();
        }

        return $this->mockGenerateSuccessResponse();
    }

    protected function mockResponseGeneratorForGateway(array $input): array
    {
        if ((isset($input[self::FAILED_RESPONSE]) === true) and
            ($input[self::FAILED_RESPONSE] === '1'))
        {
            return $this->mockGenerateFailedResponseForGateway();
        }

        return $this->mockGenerateSuccessResponseForGateway();
    }

    /**
     * Generates successful response for given request
     *
     * @param string $failure
     * @return string
     */
    protected abstract function mockGenerateFailedResponse(string $failure = ''): string;

    /**
     * Generates failed response for given request
     *
     * @return string
     */
    protected abstract function mockGenerateSuccessResponse(): string;

    /**
     * Generates successful response for given request
     *
     * @return array
     */
    protected abstract function mockGenerateFailedResponseForGateway(): array;

    /**
     * Generates failed response for given request
     *
     * @return array
     */
    protected abstract function mockGenerateSuccessResponseForGateway(): array;

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

    protected abstract function extractGatewayData(array $response): array;

    /**
     * Normalizes beneficiary name should have length between 5 - 35
     *
     * @param $string
     *
     * @return string
     */
    protected function normalizeBeneficiaryName($string): string
    {
        if (empty($string) === true)
        {
            return self::BENE_DEFAULT_NAME;
        }

        $normalizedString =  preg_replace("/[^a-zA-Z]/", '', $string);

        $length = strlen($normalizedString);

        if ($length < self::BENE_MIN_LEN)
        {
            return self::BENE_DEFAULT_NAME;
        }
        else
        {
            $normalizedString = substr($normalizedString, 0, self::BENE_MAX_LEN);
        }

        return $normalizedString;
    }

    /**
     * Normalizes beneficiary bank name should have max length between 128
     *
     * @param $string
     *
     * @return string
     */
    protected function normalizeBeneficiaryBankName($string): string
    {
         if (empty($string) === true)
        {
            return self::BENE_DEFAULT_BANK_NAME;
        }

        $normalizedString =  preg_replace("/[^a-zA-Z]/", ' ', $string);

        return substr($normalizedString, 0, self::BENE_BANK_MAX_LEN);
    }

}
