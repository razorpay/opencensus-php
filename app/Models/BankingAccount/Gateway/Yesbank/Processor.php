<?php

namespace RZP\Models\BankingAccount\Gateway\Yesbank;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\GatewayErrorException;
use RZP\Models\BankingAccount\Gateway\Fields as BaseFields;
use RZP\Models\BankingAccount\Gateway\Processor as BaseProcessor;

class Processor extends BaseProcessor
{
    const FETCH_GATEWAY_BALANCE_TIMEOUT         = 10;
    const FETCH_GATEWAY_BALANCE_CONNECT_TIMEOUT = 5;

    const GATEWAY_ERROR_PREFIX = 'YESBANK Gateway Error: ';

    const MAX_MOZART_RETRIES = 1;

    // if mozart error code is one of the below, do a retry
    protected $mozartRetryCode = [
        TraceCode::MOZART_SERVICE_REQUEST_FAILED,
        TraceCode::MOZART_SERVICE_REQUEST_TIMEOUT,
        ErrorCode::SERVER_ERROR_MOZART_SERVICE_TIMEOUT,
        ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR,
        ErrorCode::SERVER_ERROR_MOZART_INTEGRATION_ERROR,
    ];

    protected $accountCredentials;

    protected $accountNumber;

    public function __construct(array $input = [])
    {
        parent::__construct();

        $this->setUpForBalanceFetch($input);

        (new Validator)->setStrictFalse()->validateInput('yesbank_credentials', $this->basResponse);

        $this->accountCredentials = $this->extractBankingAccountCredsFromBASResponse($this->basResponse);
    }

    public function fetchGatewayBalance(): int
    {
        $response = $this->verifyCredentials();

        return $this->fetchBalanceFromMozartResponse($response);
    }

    protected function verifyCredentials()
    {
        $request = $this->formatDataForMozartBalanceFetchApi();

        $retryCount = 0;

        $response = [];

        do
        {
            try
            {
                $response = $this->app->mozart->sendMozartRequest('fts',
                                                                  BankingAccount\Channel::YESBANK,
                                                                  Action::ACCOUNT_BALANCE,
                                                                  $request,
                                                                  Action::V1,
                                                                  false,
                                                                  self::FETCH_GATEWAY_BALANCE_TIMEOUT,
                                                                  self::FETCH_GATEWAY_BALANCE_CONNECT_TIMEOUT
                );

                return $response;
            }
            catch (GatewayErrorException $exception)
            {
                $this->handleGatewayErrorExceptionFromMozart($request,
                                                             $exception,
                                                             BankingAccount\Channel::YESBANK);
            }
            catch (\Throwable $exception)
            {
                $this->handleThrowableErrorExceptionFromMozart($request,
                                                               $exception,
                                                               BankingAccount\Channel::YESBANK,
                                                               $retryCount);
            }
        }while($retryCount <= self::MAX_MOZART_RETRIES);
    }

    protected function formatDataForMozartBalanceFetchApi()
    {
        return [
            BaseFields::SOURCE_ACCOUNT => [
                BaseFields::SOURCE_ACCOUNT_NUMBER => $this->accountNumber,
                BaseFields::CREDENTIALS           => [
                    Fields::CUSTOMER_ID   => $this->accountCredentials[Fields::CUSTOMER_ID],
                    Fields::APP_ID        => $this->accountCredentials[Fields::APP_ID],
                    Fields::AUTH_USERNAME => $this->accountCredentials[Fields::AUTH_USERNAME],
                    Fields::AUTH_PASSWORD => $this->accountCredentials[Fields::AUTH_PASSWORD],
                    Fields::CLIENT_ID     => $this->accountCredentials[Fields::CLIENT_ID],
                    Fields::CLIENT_SECRET => $this->accountCredentials[Fields::CLIENT_SECRET],
                ],
            ],
        ];
    }

    protected function fetchBalanceFromMozartResponse(array $response)
    {
        $balance = $response[Fields::DATA][Fields::ACCOUNT_BALANCE_AMOUNT];

        return $this->getFormattedAmount($balance);
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

    protected function extractBankingAccountCredsFromBASResponse($response)
    {
        return [
            Fields::CUSTOMER_ID   => $response[BaseFields::CREDENTIALS][Fields::CUSTOMER_ID],
            Fields::APP_ID        => $response[BaseFields::CREDENTIALS][Fields::APP_ID],
            Fields::AUTH_PASSWORD => $response[BaseFields::CREDENTIALS][Fields::AUTH_PASSWORD],
            Fields::AUTH_USERNAME => $response[BaseFields::CREDENTIALS][Fields::AUTH_USERNAME],
            Fields::CLIENT_ID     => $response[BaseFields::CREDENTIALS][Fields::CLIENT_ID],
            Fields::CLIENT_SECRET => $response[BaseFields::CREDENTIALS][Fields::CLIENT_SECRET],
        ];
    }
}
