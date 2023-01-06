<?php

namespace RZP\Models\BankingAccount\Gateway\Icici;

use App;

use RZP\Services\Mozart;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\BadRequestException;
use RZP\Services\BankingAccountService;
use RZP\Exception\ServerErrorException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\BankingAccount\Gateway\Icici;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\BankingAccountStatement\Details as BasDetails;
use RZP\Models\BankingAccountStatement\Processor\Icici\Validator;
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

    protected $accountCredentials;

    protected $accountNumber;

    protected $merchantId;

    public function __construct(array $setUpForBalanceFetch = [])
    {
        parent::__construct();

        if (empty($setUpForBalanceFetch) === false)
        {
            $app = App::getFacadeRoot();

            /* @var BankingAccountService $bas */
            $bas = $app['banking_account_service'];

            $merchantId    = $setUpForBalanceFetch[BasDetails\Entity::MERCHANT_ID];
            $accountNumber = $setUpForBalanceFetch[BasDetails\Entity::ACCOUNT_NUMBER];
            $channel       = $setUpForBalanceFetch[BasDetails\Entity::CHANNEL];

            $this->accountCredentials = $bas->fetchBankingCredentials($merchantId, $channel, $accountNumber);

            (new Validator)->validateInput('icici_credentials', $this->accountCredentials);

            $this->accountNumber = $accountNumber;

            $this->merchantId = $merchantId;
        }
    }

    protected function formatDataForMozartBalanceFetchApi()
    {
        // If AGGR_ID, AGGR_NAME, BENEFICIARY_API_KEY are available from BAS then we use those, else we fetch them from credstash
        // These creds will only be available from BAS for merchants on BaaS flow
        $aggrId            = $this->config['banking_account']['icici'][Fields::AGGR_ID_CONFIG];
        $aggrName          = $this->config['banking_account']['icici'][Fields::AGGR_NAME_CONFIG];
        $beneficiaryApikey = $this->config['banking_account']['icici'][Fields::BENEFICIARY_API_KEY_CONFIG];

        $data = [
            Fields::SOURCE_ACCOUNT => [
                Fields::SOURCE_ACCOUNT_NUMBER => $this->accountNumber,
                Fields::CREDENTIALS           => [
                    Fields::CORP_ID             => $this->accountCredentials[Fields::CORP_ID],
                    Fields::CORP_USER           => $this->accountCredentials[Fields::CORP_USER],
                    Fields::URN                 => $this->accountCredentials[Fields::URN],
                    Fields::AGGR_ID             => $aggrId,
                    Fields::AGGR_NAME           => $aggrName,
                    Fields::BENEFICIARY_API_KEY => $beneficiaryApikey,
                ],
            ],
        ];

        if ((array_key_exists(Icici\Fields::CREDENTIALS, $this->accountCredentials) === true) and
            ($this->accountCredentials[Icici\Fields::CREDENTIALS] !== null))
        {
            $this->modifyRequestForMerchantsOnBaasFlow($data);
        }

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

    protected function verifyCredentials()
    {
        (new Validator)->validateInput('icici_credentials', $this->accountCredentials);

        $merchant = $this->repo->merchant->getMerchant($this->merchantId);

        if ($merchant->isFeatureEnabled(Features::ICICI_BAAS) === true)
        {
            $this->validateCredentialsForBaasMerchants($this->accountCredentials[Icici\Fields::CREDENTIALS]);
        }

        $request = $this->formatDataForMozartBalanceFetchApi();

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
    public function fetchGatewayBalance(): int
    {
        $response = $this->verifyCredentials();

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

    public function validateCredentialsForBaasMerchants($baasCredentials)
    {
        if (($baasCredentials === null) or
            (empty($baasCredentials[Icici\Fields::AGGR_ID]) === true) or
            (empty($baasCredentials[Icici\Fields::AGGR_NAME]) === true) or
            (empty($baasCredentials[Icici\Fields::BENEFICIARY_API_KEY]) === true))
        {
            $errorMessage = TraceCode::getMessage(TraceCode::BAS_INVALID_CREDENTIALS_ERROR);

            $this->trace->error(
                TraceCode::BAS_INVALID_CREDENTIALS_ERROR,
                [
                    'merchant_id'   => $this->merchantId,
                    'creds_present' => isset($baasCredentials),
                ]
            );

            throw new ServerErrorException(
                $errorMessage,
                ErrorCode::SERVER_ERROR,
                [
                    'merchant_id' => $this->merchantId
                ]
            );
        }
    }

    public function modifyRequestForMerchantsOnBaasFlow(&$data)
    {
        //Use aggrId, aggrName and beneficiaryApikey from BAS instead of credstash
        $aggrId            = $this->accountCredentials[Icici\Fields::CREDENTIALS][Icici\Fields::AGGR_ID];
        $aggrName          = $this->accountCredentials[Icici\Fields::CREDENTIALS][Icici\Fields::AGGR_NAME];
        $beneficiaryApikey = $this->accountCredentials[Icici\Fields::CREDENTIALS][Icici\Fields::BENEFICIARY_API_KEY];

        $data[Fields::SOURCE_ACCOUNT][Fields::CREDENTIALS][Fields::AGGR_ID]             = $aggrId;
        $data[Fields::SOURCE_ACCOUNT][Fields::CREDENTIALS][Fields::AGGR_NAME]           = $aggrName;
        $data[Fields::SOURCE_ACCOUNT][Fields::CREDENTIALS][Fields::BENEFICIARY_API_KEY] = $beneficiaryApikey;

        //append merchant_id in the request as well
        $data[Fields::MERCHANT_ID] = $this->merchantId;
    }
}
