<?php

namespace RZP\Models\BankingAccount\Gateway\Axis;

use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\GatewayErrorException;
use RZP\Models\BankingAccount\Gateway\Fields as BaseFields;
use RZP\Models\BankingAccount\Gateway\Processor as BaseProcessor;

class Processor extends BaseProcessor
{

    const FETCH_GATEWAY_BALANCE_TIMEOUT         = 10;
    const FETCH_GATEWAY_BALANCE_CONNECT_TIMEOUT = 5;

    const GATEWAY_ERROR_PREFIX = 'AXIS Gateway Error: ';

    const MAX_MOZART_RETRIES = 1;

    public function __construct(array $input = [])
    {
        parent::__construct();

        $this->setUpForBalanceFetch($input);

        (new Validator)->setStrictFalse()->validateInput('axis_credentials', $this->basResponse);

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
                                                                  BankingAccount\Channel::AXIS,
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
                                                             BankingAccount\Channel::AXIS);
            }
            catch (\Throwable $exception)
            {
                $this->handleThrowableErrorExceptionFromMozart($request,
                                                               $exception,
                                                               BankingAccount\Channel::AXIS,
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
                    Fields::ENCRYPTION_KEY => $this->accountCredentials[Fields::ENCRYPTION_KEY],
                    Fields::ENCRYPTION_IV  => $this->accountCredentials[Fields::ENCRYPTION_IV],
                    Fields::CLIENT_ID      => $this->accountCredentials[Fields::CLIENT_ID],
                    Fields::CLIENT_SECRET  => $this->accountCredentials[Fields::CLIENT_SECRET],
                    Fields::CORP_CODE      => $this->accountCredentials[Fields::CORP_CODE],
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

    public function extractBankingAccountCredsFromBASResponse($response)
    {
        return [
            Fields::ENCRYPTION_KEY => $response[BaseFields::CREDENTIALS][Fields::ENCRYPTION_KEY],
            Fields::ENCRYPTION_IV  => $response[BaseFields::CREDENTIALS][Fields::ENCRYPTION_IV],
            Fields::CLIENT_ID      => $response[BaseFields::CREDENTIALS][Fields::CLIENT_ID],
            Fields::CLIENT_SECRET  => $response[BaseFields::CREDENTIALS][Fields::CLIENT_SECRET],
            Fields::CORP_CODE      => $response[BaseFields::CREDENTIALS][Fields::CORP_CODE],
        ];
    }
}
