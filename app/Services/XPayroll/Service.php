<?php

namespace RZP\Services\XPayroll;

use Razorpay\Trace\Logger as Trace;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;
use RZP\Http\Request\Requests;
use RZP\Http\Response\StatusCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\BankTransfer\PayerBankAccount;
use RZP\Models\BankTransfer\Entity as BankTransferEntity;
use RZP\Models\PayoutsStatusDetails as PayoutsStatusDetails;

/**
 * This class will be the main file that will talk to
 * XPayroll Micro Service and relay all the responses.
 */
class Service
{
    // All routes
    const ROUTE_STATUS_UPDATE = '/v2/api/merchant-payout-status';

    const ROUTE_TPV_VALIDATION = '/v2/api/validate-source-account';

    // All methods
    const METHOD_POST = 'POST';
    const METHOD_GET  = 'GET';

    public function __construct($app)
    {
        $this->app     = $app;
        $this->trace   = $app['trace'];
        $this->config  = $app['config']['applications.xpayroll'];
        $this->repo    = $app['repo'];
        $this->baseUrl = $this->config['baseUrl'];
        $this->secret  = $this->config['secret'];
    }

    /**
     * Status update upon receiving webhook from payout
     *
     * @param array  $input
     * @param string $mode
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function sendStatusUpdate(array $input, string $mode): array
    {
        return $this->makeRequest(self::ROUTE_STATUS_UPDATE, static::METHOD_POST, $input, $mode, []);
    }

    public function pushPayoutStatusUpdate(PayoutEntity $payout, string $mode)
    {
        $dataToSend = $this->getDataFromPayout($payout);

        $this->sendStatusUpdate($dataToSend, $mode);
    }

    protected function getDataFromPayout(PayoutEntity $payout): array
    {
        return [
            'id'                => $payout->getId(),
            'entity'            => $payout->getEntity(),
            'fund_account_id'   => $payout->getFundAccountId(),
            'amount'            => $payout->getAmount(),
            'currency'          => $payout->getCurrency(),
            'notes'             => $payout->getNotes(),
            'fees'              => $payout->getFees(),
            'tax'               => $payout->getTax(),
            'status'            => $payout->getStatus(),
            'purpose'           => $payout->getPurpose(),
            'utr'               => $payout->getUtr(),
            'mode'              => $payout->getMode(),
            'channel'           => $payout->getChannel(),
            'remark'            => $payout->getRemarks(),
            'reference_id'      => $payout->getReferenceId(),
            'narration'         => $payout->getNarration(),
            'batch_id'          => $payout->getBatchId(),
            'failure_reason'    => $payout->getFailureReason(),
            'created_at'        => $payout->getCreatedAt(),
            'status_details_id' => $payout->getStatusDetailsId(),
            'status_details'    => $this->getStatusDetailsFromPayout($payout),
        ];
    }

    /**
     * @param string $endpoint
     * @param array  $data
     * @param string $service
     * @param string $mode
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function makeRequest(string $endpoint, string $method, array $data, $mode = MODE::LIVE, array $headers = []): array
    {

        // XPayroll does not support Test mode
        if (($this->app->environment() === Environment::PRODUCTION)
            and ($mode !== Mode::LIVE))
        {
            return null;
        }

        $headers = array_merge($headers, [
            'secret'       => $this->secret,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json'
        ]);

        $headers['X-Task-ID'] = $this->app['request']->getId() ?? null;

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, "/");

        $this->trace->info(TraceCode::XPAYROLL_PAYOUT_REQUEST, [
            'url'  => $url,
            'data' => $data,
        ]);

        $options = [];

        try
        {
            $response = Requests::$method(
                $url,
                $headers,
                json_encode($data, JSON_FORCE_OBJECT),
                $options);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::XPAYROLL_PAYOUT_REQUEST_ERROR,
                [
                    'message'   => $e->getMessage(),
                    'url'       => $url,
                    'data'      => $data,
                    'hassecret' => empty($this->secret) ? 'NO' : 'YES',
                    'X-Task-Id' => $headers['X-Task-ID']
                ]
            );

            throw $e;
        }

        $responseBody = json_decode($response->body, true);

        $this->trace->info(TraceCode::XPAYROLL_PAYOUT_REQUEST_RESPONSE, [
            'response' => $response->body,
            'json'     => $responseBody
        ]);

        if ((empty($responseBody) === true) or ($response->status_code !== StatusCode::SUCCESS))
        {
            $description = 'Invalid response from XPayroll';

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_TO_XPAYROLL_SERVICE,
                null,
                null,
                $description);
        }

        return $responseBody;
    }

    public function getStatusDetailsFromPayout(PayoutEntity $payout)
    {
        if ($payout->getStatusDetailsId() === null)
        {
            $statusDetailsArray =
                [
                    'reason'        => null,
                    'description'   => null,
                    'source'        => null,
                ];
        }

        else
        {
            $statusDetails = (new PayoutsStatusDetails\Repository())->fetchStatusDetailsFromStatusDetailsId($payout->getStatusDetailsId());

            if ($statusDetails !== null)
            {
                $source = $payout->getSourceForStatusDetails($statusDetails);
            }
            else
            {
                $source = null;
            }

            $statusDetailsArray =
                [
                    'reason'        => $statusDetails['reason'],
                    'description'   => $statusDetails['description'],
                    'source'        => $source,
                ];
        }

        return $statusDetailsArray;
    }


    public function sendPayrollTpvRequestAndGetResponse(BankTransferEntity $bankTransfer, string $mode, $retryLimit = 3)
    {
        $data = [
            'payee_account'     => $bankTransfer->getPayeeAccount(),
            'payee_ifsc'        => $bankTransfer->getPayeeIfsc(),
            'payer_name'        => $bankTransfer->getPayerName(),
            'payer_account'     => PayerBankAccount::getPayerAccount($bankTransfer),
            'payer_ifsc'        => $bankTransfer->getPayerIfsc(),
            'mode'              => $bankTransfer->getMode(),
            'utr'               => $bankTransfer->getUtr(),
            'time'              => $bankTransfer->getTransactionTime(),
            'amount'            => $bankTransfer->getAmount(),
            'description'       => $bankTransfer->getDescription(),
            'narration'         => $bankTransfer->getNarration()
        ];

        return $this->makePayrollValidationRequest(
            self::ROUTE_TPV_VALIDATION,
            static::METHOD_POST,
            $data,
            $retryLimit,
            $mode);
    }

    public function makePayrollValidationRequest(
        string $endpoint,
        string $method,
        array $data,
        $retryLimit,
        $mode = MODE::LIVE,
        array $headers = [])
    {
        // XPayroll does not support Test mode
        if (($this->app->environment() === Environment::PRODUCTION) && ($mode !== Mode::LIVE))
        {
            $this->trace->info(TraceCode::XPAYROLL_TPV_TEST_MODE_UNSUPPORTED, []);

            return [];
        }

        $headers = array_merge($headers, [
            'secret'       => $this->secret,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json'
        ]);

        $headers['X-Task-ID'] = $this->app['request']->getId() ?? null;

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, "/");

        $this->trace->info(
            TraceCode::XPAYROLL_TPV_REQUEST, [
            'url'  => $url,
            'data' => $data,
        ]);

        $retryCount = 0;
        $options = [];
        $response = '';

        while ($retryCount < $retryLimit)
        {
            try
            {
                $response = Requests::$method(
                    $url,
                    $headers,
                    json_encode($data, JSON_FORCE_OBJECT),
                    $options);

                // Retry if status code is 503, 504
                if (!empty($response) && in_array($response->status_code, [ StatusCode::SERVICE_UNAVAILABLE, StatusCode::GATEWAY_TIMEOUT])){
                    throw new \Exception("Server error: {$response->status_code}");
                }

                break;
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::XPAYROLL_TPV_REQUEST_ERROR,
                    [
                        'message'     => $e->getMessage(),
                        'retry count' => $retryCount,
                        'url'         => $url,
                        'data'        => $data,
                        'hasSecret'   => empty($this->secret) ? 'NO' : 'YES',
                        'X-Task-Id'   => $headers['X-Task-ID']
                    ]
                );

                $retryCount++;
            }
        }

        $responseBody = json_decode($response->body, true);

        $this->trace->info(TraceCode::XPAYROLL_TPV_REQUEST_RESPONSE, [
            'response' => $response->body,
            'json'     => $responseBody
        ]);

        if ((empty($responseBody) === true) or ($response->status_code !== StatusCode::SUCCESS))
        {
            $this->trace->info(TraceCode::XPAYROLL_TPV_INVALID_RESPONSE, [
                'response' => $response->body,
            ]);

            // if there is no positive response from payroll after maximum retry, we return validation as false
            return [
                'is_valid' => false
            ];
        }

        return $responseBody;
    }
}
