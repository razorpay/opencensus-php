<?php

namespace RZP\Models\FundTransfer\Base\Initiator;

use App;
use Request;
use RZP\Http\Request\Requests;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Gateway;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Attempt\Constants as FundTransferConstants;

abstract class ApiProcessor extends NodalAccount
{
    /**
     * Holds request method
     *
     * @var string
     */
    protected $method;

    /**
     * Holds the request URL
     *
     * @var string
     */
    protected $url;

    /**
     * Holds the request headers to be sent
     *
     * @var array
     */
    protected $headers = [];

    protected $config;

    /**
     * Holds the request body
     *
     * @var string
     */
    protected $body = null;

    /**
     * Holds the options data for request
     *
     * @var array
     */
    protected $options = [];

    /**
     * Holds the response object
     *
     * @var \WpOrg\Requests\Response
     */
    protected $response = null;

    /**
     * Response trace will be recorded if this variable is set
     * and trace is recorded against this trace code
     *
     * @var string
     */
    protected $responseTraceCode = TraceCode::SETTLEMENT_API_RESPONSE;

    /**
     * Request trace will be recorded if this variable is set
     * and trace is recorded against this trace code
     *
     * @var string
     */
    protected $requestTraceCode = TraceCode::SETTLEMENT_API_REQUEST;

    /**
     * @var string
     */
    protected $maskedResponseBody = null;

    /**
     * @var bool
     */
    protected $useLogging = true;

    public $requestTrace;

    public $ftaId = null;

    public function method(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function options(array $option): self
    {
        $this->options = array_merge($this->options, $option);

        return $this;
    }

    public function headers(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function responseTraceCode(string $traceCode): self
    {
        $this->responseTraceCode = $traceCode;

        return $this;
    }

    public function requestTraceCode(string $traceCode): self
    {
        $this->requestTraceCode = $traceCode;

        return $this;
    }

    public function makeRequest(bool $gateway = false): array
    {
        // We are using API processor only for gateway
        // call also because currently only one type of
        // processor can be used for a given gateway.

        $parsedResponse = [];

        try
        {
            if ($gateway === true)
            {
                $parsedResponse = $this->makeRequestOnGateway();
            }
            else
            {
                $parsedResponse = $this->makeRequestOnNodal();
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::NODAL_REQUEST_FAILED,
                [
                    'request'  => $this->requestTrace,
                ]);
        }

        return $parsedResponse;
    }

    public function captureBankStatusMetric(
        string $channel,
        string $product,
        bool $isFailure,
        bool $isSuccess,
        $mode,
        $statusCode,
        $bankSubStatus)
    {
        $status = Metric::CATEGORY_PENDING;

        switch (true)
        {
            case $isSuccess === true:
                $status = Metric::CATEGORY_SUCCESS;
                break;

            case $isFailure === true:
                $status = Metric::CATEGORY_FAILED;
                break;

            default:
                $status = Metric::CATEGORY_PENDING;
        }

        $this->trace->count(Metric::NODAL_RESPONSE_COUNT, [
            Metric::MODE               => $mode,
            Metric::CHANNEL            => $channel,
            Metric::PRODUCT            => $product,
            Metric::STATUS_CODE        => $statusCode,
            Metric::BANK_FAILURE_CODE  => $bankSubStatus,
            Metric::STATUS             => $status,
        ]);
    }

    /**
     * @return array
     */
    protected function makeRequestOnNodal(): array
    {
        $response = null;

        $this->collectRequestData();

        $this->traceRequest();

        $startTime = millitime();

        if (($this->config['mock'] === true) or ($this->mode === Mode::TEST))
        {
            $response = $this->sendMockRequest();
        }
        else
        {
            // Putting this in try-catch so that we can run
            // processResponse properly and return back an
            // actual processed response but with null values.
            // This will ensure that that the request failures
            // are handled more gracefully.

            try
            {
                $response = Requests::request(
                    $this->url,
                    $this->headers,
                    $this->body,
                    $this->method,
                    $this->options);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::NODAL_REQUEST_FAILED,
                    [
                        'request' => $this->requestTrace,
                    ]);
            }
        }

        $response = $this->handleEmptyResponse($response);

        $this->traceResponseTime($response->status_code, $startTime);

        $this->traceResponse($response);

        return $this->processResponse($response);
    }

    protected function handleEmptyResponse($response)
    {
        if (empty($response) === true)
        {
            return new \WpOrg\Requests\Response();
        }

        return $response;
    }

    protected function makeRequestOnGateway(): array
    {
        $response = [];

        $startTime = millitime();

        if ($this->config['mock'] === true)
        {
            $response = $this->sendMockRequestForGateway();
        }
        else
        {
            $requestInput = $this->getRequestInputForGateway();
            $action = $this->getActionForGateway();

            try
            {
                // Putting this in try-catch so that we can run
                // processResponse properly and return back an
                // actual processed response but with null values.
                // This will ensure that that the request failures
                // are handled more gracefully.

                $response = $this->app['gateway']->call(
                    Gateway::UPI_YESBANK,
                    $action,
                    $requestInput,
                    $this->mode);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::NODAL_REQUEST_FAILED,
                    [
                        'request' => $this->requestTrace,
                    ]);
            }
        }

        $this->traceResponseTime($response[Metric::STATUS_CODE] ?? null, $startTime);

        $this->traceGatewayResponse($response);

        return $this->processGatewayResponse($response);
    }

    /**
     * Collects all the essential params for current request
     */
    private function collectRequestData()
    {
        $this->url($this->requestUrl())
             ->body($this->requestBody())
             ->method($this->requestMethod())
             ->headers($this->requestHeaders())
             ->options($this->requestOptions());
    }

    /**
     * Trace response if `responseTraceCode` is set
     * Response will be traced against the `responseTraceCode` set
     *
     * @param \WpOrg\Requests\Response $response
     */
    protected function traceResponse($response)
    {
        $this->maskedResponseBody = $this->getMaskedResponseBody($response->body);

        if (empty($this->maskedResponseBody) === true)
        {
            $this->maskedResponseBody = $response->body;
        }

        $this->trace->info(
            $this->responseTraceCode,
            [
                'fta_id'        => $this->ftaId,
                'channel'       => $this->channel,
                'response_body' => $this->maskedResponseBody,
                'status_code'   => $response->status_code,
            ]);

        $this->trace->count(Metric::NODAL_RESPONSE_STATUS_CODE, [
            'code'        => $this->responseTraceCode,
            'channel'     => $this->channel,
            'status_code' => $response->status_code,
        ]);
    }

    protected function getMaskedResponseBody($responseBody)
    {
        return $responseBody;
    }

    private function traceResponseTime($status_code, int $startTime)
    {
        $duration = millitime() - $startTime;

        $dimensions = [
            Metric::CHANNEL            => $this->channel,
            Metric::STATUS_CODE        => $status_code,
            Metric::REQUEST_TRACE_CODE => $this->requestTraceCode,
            Metric::MODE               => $this->mode,
        ];

        $this->trace->histogram(Metric::NODAL_RESPONSE_TIME, $duration, $dimensions);
    }

    private function traceGatewayResponse(array $response)
    {
        $this->trace->info(
            $this->responseTraceCode,
            [
                'channel'   => $this->channel,
                'response'  => $response,
            ]);
    }

    /**
     * Trace request if `requestTraceCode` is set
     * Request will be traced against the `requestTraceCode` set
     */
    private function traceRequest()
    {
        $this->trace->info(
            $this->requestTraceCode,
            [
                'channel' => $this->channel,
                'method'  => $this->method,
                'request' => $this->requestTrace,
            ]);
    }

    /**
     * Creates dummy response from the array received from `responseGenerator`
     *
     * @return \WpOrg\Requests\Response
     */
    private function sendMockRequest()
    {
        $input = Request::all();

        if ((empty($input['amount']) === false) and
            (($input['amount'] === '3470') or
             ($input['amount'] === 3470)))
        {
            $input['failed_response'] = '1';
        }

        $content = $this->mockResponseGenerator($input);

        $response = new \WpOrg\Requests\Response();

        $response->body = $content;

        $response->status_code = 200;

        $response->url = $this->url;

        return $response;
    }

    private function sendMockRequestForGateway(): array
    {
        $input = Request::all();

        return $this->mockResponseGeneratorForGateway($input);
    }

    /**
     * Gets the certificate file location
     * if the file doesn't exist then create a client certificate file based on configuration provided
     *
     * @return string
     */
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

    /**
    * Give the certificate key file location
    * if the file doesnt exist then create a client key file based on configuration provided
    *
    * @return string
    */
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

    /**
    * give the client certificate file name
    *
    * @return string
    */
    protected function getClientCertificateName(): string
    {
        return $this->config['certificate_name'];
    }

    /**
     * Gives the certificate directory
     *
     * @return string
     */
    protected function getGatewayCertDirPath(): string
    {
        if (file_exists($this->config['certificate_path']) === false)
        {
            mkdir($this->config['certificate_path'], 0755, true);
        }

        return $this->config['certificate_path'];
    }

    /**
    * Gives the certificate key file name
    *
    * @return string
    */
    protected function getClientCertificateKeyName(): string
    {
        return $this->config['certificate_key_name'];
    }


    /**
     *
     * Should be implimented in the chiled class and should return the URL to be requested
     *
     * @return string
     */
    public abstract function requestUrl(): string;

    /**
     * Should give the request body for the current request class
     *
     * @return string
     */
    public abstract function requestBody(): string;

    public abstract function getRequestInputForGateway(): array;

    public abstract function getActionForGateway(): string;

    /**
     * Should give the request method for current request
     *
     * @return string
     */
    public abstract function requestMethod(): string;

    /**
     * Should give the request header for current request
     *
     * @return array
     */
    public abstract function requestHeaders(): array;

    /**
     * Should give the options for current request
     *
     * @return array
     */
    public abstract function requestOptions(): array;

    /**
     * Generate the mock response for the given class.
     *
     * @param array $input config params
     *
     * @return string
     */
    protected abstract function mockResponseGenerator(array $input): string;

    protected abstract function mockResponseGeneratorForGateway(array $input): array;

    /**
     * Should be implemented in the clild class to process the response of current request
     * Processing should have the status check and other required validations
     *
     * @param \WpOrg\Requests\Response $response
     *
     * @return array
     */
    public abstract function processResponse($response): array;

    public abstract function processGatewayResponse(array $response): array;

    /**
     * Sets the flag for logging data
     */
    public function enableLogs()
    {
        $this->useLogging = true;
    }

    /**
     * Unset the flag for logging data
     */
    public function disableLogs()
    {
        $this->useLogging = false;
    }

    /**
     * Checks logging status
     *
     * @return bool
     */
    public function isLogEnabled(): bool
    {
        return $this->useLogging;
    }

    /**
     * Gets ifsc code for card issuer using BANK_IFSC constant array.
     *
     * @param CardEntity $cardObj
     * @return mixed
     */
    protected function getIfscCodeUsingCardInfo(CardEntity $cardObj)
    {
        $cardIssuer = trim($cardObj->getIssuer());

        $cardNetworkCode = trim($cardObj->getNetworkCode());

        $issuerList = array_keys(FundTransferConstants::BANK_IFSC);

        if (in_array($cardIssuer, $issuerList, true) === true)
        {
            if (array_key_exists($cardNetworkCode, array_keys(FundTransferConstants::BANK_IFSC[$cardIssuer])) === true)
            {
                return FundTransferConstants::BANK_IFSC[$cardIssuer][$cardNetworkCode];
            }

            return FundTransferConstants::BANK_IFSC[$cardIssuer][FundTransferConstants::DEFAULT_NETWORK];
        }
        else
        {
            (new SlackNotification)->send('Card payout not supported for issuer',
                [
                    'id'     => $this->entity->getId(),
                    'issuer' => $cardIssuer,
                ], null, 1);

            new LogicException('IFSC code does not exist for this card issuer');
        }
    }

    /**
     * If card is used for the 1st time on a RZP gateway then a vault token is generated in card entity.
     * If vault has been already encountered then vault token is null and a global card id is present.
     * This contains the vault token generated.
     * If no vault token is present then null is returned to mark fta as failed.
     *
     * @param CardEntity $card
     * @return mixed
     * @throws \Exception
     */
    protected function getCardVaultToken(CardEntity $card)
    {
        $token = $card->getCardVaultToken();

        if ($token === null)
        {
            $this->trace->error(
                TraceCode::CARD_TOKEN_IS_NOT_AVAILABLE,
                [
                    'card_id' => $card->getId()
                ]);

            (new SlackNotification())->send(
                'Vault token missing',
                [
                    'card_id' => $card->getId()
                ],
                null, 1);
        }

        return $token;
    }
}
