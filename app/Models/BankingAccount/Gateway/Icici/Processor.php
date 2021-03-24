<?php

namespace RZP\Models\BankingAccount\Gateway\Icici;

use RZP\Services\Mozart;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\BankingAccount\Gateway\Processor as BaseProcessor;

class Processor extends BaseProcessor
{
    const FETCH_GATEWAY_BALANCE_TIMEOUT         = 10;
    const FETCH_GATEWAY_BALANCE_CONNECT_TIMEOUT = 5;

    const GATEWAY_ERROR_PREFIX = 'ICICI Gateway Error: ';

    const MAX_MOZART_RETRIES = 1;

    // if mozart error code is one of the below, do a retry
    protected $mozartRetryCode = [
        TraceCode::MOZART_SERVICE_REQUEST_FAILED,
        TraceCode::MOZART_SERVICE_REQUEST_TIMEOUT,
        ErrorCode::SERVER_ERROR_MOZART_SERVICE_TIMEOUT,
        ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR,
        ErrorCode::SERVER_ERROR_MOZART_INTEGRATION_ERROR,
    ];

    protected function formatDataForMozartBalanceFetchApi(BankingAccount\Entity $bankingAccount)
    {
        //AGGR_ID, AGGR_NAME, BENEFICIARY_API_KEY are common for all merchants . so fetching these from credstash

        $data = [
            Fields::SOURCE_ACCOUNT => [
                Fields::SOURCE_ACCOUNT_NUMBER => $bankingAccount->getAccountNumber(),
                Fields::CREDENTIALS           => [
                    Fields::CORP_ID             => $bankingAccount->getReference1(),
                    Fields::CORP_USER           => $bankingAccount->getDetailsDataUsingKey(Fields::USER_ID),
                    Fields::AGGR_ID             => $this->config['banking_account']['icici'][Fields::AGGR_ID_CONFIG],
                    Fields::AGGR_NAME           => $this->config['banking_account']['icici'][Fields::AGGR_NAME_CONFIG],
                    Fields::URN                 => $bankingAccount->getDetailsDataUsingKey(Fields::URN_DB),
                    Fields::BENEFICIARY_API_KEY => $this->config['banking_account']['icici'][Fields::BENEFICIARY_API_KEY_CONFIG],
                ],
            ]
        ];

        return $data;
    }

    protected function shouldRetryMozartRequest(string $errorCode): bool
    {
        if (in_array($errorCode, $this->mozartRetryCode, true) === true)
        {
            return true;
        }

        return false;
    }

    protected function verifyCredentials(BankingAccount\Entity $bankingAccount)
    {
        $request = $this->formatDataForMozartBalanceFetchApi($bankingAccount);

        $retryCount = 0;

        $response = [];

        do
        {
            try
            {
                $response = $this->app->mozart->sendMozartRequest('fts',
                                                                  BankingAccount\Channel::ICICI,
                                                                  Action::ACCOUNT_BALANCE,
                                                                  $request,
                                                                  Action::V2,
                                                                  false,
                                                                  self::FETCH_GATEWAY_BALANCE_TIMEOUT,
                                                                  self::FETCH_GATEWAY_BALANCE_CONNECT_TIMEOUT
                );

                return $response;
            }
            catch (GatewayErrorException $ex)
            {
                $traceRequest = $this->unsetSensitiveDetails($request);

                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::MOZART_SERVICE_REQUEST_FAILED,
                    [
                        'request' => $traceRequest,
                        'channel' => BankingAccount\Channel::ICICI,
                    ]);

                // throwing a generic error with which includes the gateway error description from Mozart
                $errorDescription = $this->getIciciGatewayDescriptionFromException($ex);

                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED,
                    null,
                    [
                        'data'    => $ex->getData(),
                        'channel' => BankingAccount\Channel::ICICI
                    ],
                    $errorDescription);
            }
            catch (\Throwable $exception)
            {
                $traceRequest = $this->unsetSensitiveDetails($request);

                $this->trace->traceException(
                    $exception,
                    Trace::CRITICAL,
                    TraceCode::MOZART_SERVICE_REQUEST_FAILED,
                    [
                        'request' => $traceRequest,
                        'channel' => BankingAccount\Channel::ICICI
                    ]);

                $errorCode = $exception->getCode();

                $shouldRetry = $this->shouldRetryMozartRequest($errorCode);

                if (($shouldRetry === true) and
                    ($retryCount < self::MAX_MOZART_RETRIES))
                {
                    $this->trace->info(
                        TraceCode::MOZART_SERVICE_RETRY,
                        [
                            'message' => $exception->getMessage(),
                            'data'    => $exception->getData(),
                        ]);

                    $retryCount++;
                }
                else
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED
                    );
                }
            }
        }while($retryCount <= self::MAX_MOZART_RETRIES);
    }

    protected function getIciciGatewayDescriptionFromException(GatewayErrorException $ex)
    {
        $gatewayErrorDesc = $ex->getGatewayErrorDesc();

        if ($gatewayErrorDesc === Mozart::NO_ERROR_MAPPING_DESCRIPTION)
        {
            $gatewayErrorDesc = "Unknown";
        }

        if (empty($gatewayErrorDesc) === false)
        {
            return self::GATEWAY_ERROR_PREFIX . $gatewayErrorDesc;
        }

        return self::GATEWAY_ERROR_PREFIX . "Unknown";
    }

    protected function getFormattedAmount($amount)
    {
        return intval(number_format($amount * 100, 0, '.', ''));
    }

    protected function fetchBalanceFromMozartResponse(array $response)
    {
        $balance = $response[Fields::DATA][Fields::BALANCE];

        return $this->getFormattedAmount($balance);
    }

    /**
     * @param Entity $bankingAccount
     *
     * @return int
     *
     * @throws BadRequestException
     */
    public function fetchGatewayBalance(BankingAccount\Entity $bankingAccount): int
    {
        $response = $this->verifyCredentials($bankingAccount);

        $balance = $this->fetchBalanceFromMozartResponse($response);

        return $balance;
    }

    public function formatAccountDetails(array $input)
    {
        // TODO: Implement formatAccountDetails() method.
    }

    protected function validateBeforeActivation(Entity $bankingAccount, array $input)
    {
        // TODO: Implement validateBeforeActivation() method.
    }

    protected function processActivation(Entity $bankingAccount, array $input): Entity
    {
        // TODO: Implement processActivation() method.
    }

    protected function validateInputForAccountCreation(array $input)
    {
        // TODO: Implement validateInputForAccountCreation() method.
    }

    protected function preProcessInputForAccountCreation(array $input)
    {
        // TODO: Implement preProcessInputForAccountCreation() method.
    }

    protected function generateRequestForSourceAccount(Entity $bankingAccount)
    {
        // TODO: Implement generateRequestForSourceAccount() method.
    }

    protected function unsetSensitiveDetails(array $request)
    {
        if (isset($request[Fields::SOURCE_ACCOUNT][Fields::CREDENTIALS]) === true)
        {
            unset($request[Fields::SOURCE_ACCOUNT][Fields::CREDENTIALS]);
        }

        return $request;
    }
}
