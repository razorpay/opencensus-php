<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Exception;
use Monolog\Logger;
use RZP\Services\Mozart;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\CardVault;
use RZP\Jobs\FaVpaValidation;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\LogicException;
use RZP\Exception\RuntimeException;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\BankingAccount\AccountType;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\BankingAccountService\Channel;
use RZP\Models\FundAccount\Validation\Metric;
use RZP\Models\FundAccount\Entity as FaEntity;
use RZP\Models\FundAccount\Validation\Constants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundAccount\Validation\Entity as FavEntity;
use RZP\Models\FundAccount\Validation\Entity as Validation;
use RZP\Models\BankingAccount\Gateway\Rbl\Fields as Fields;
use RZP\Models\BankingAccount\Service as BankingAccountService;

class Vpa extends Base
{
    public function __construct(Validation $validation)
    {
        parent::__construct($validation);
    }

    public function preProcessValidation()
    {
        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_VPA_VALIDATE_JOB_REQUEST,
            [
                'fav_id' => $this->validation->getId(),
            ]
        );

        FaVpaValidation::dispatch($this->mode, $this->validation->getId());

        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_VPA_VALIDATE_JOB_REQUEST_DISPATCHED,
            [
                'fav_id' => $this->validation->getId(),
            ]
        );
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws ServerErrorException
     * @throws BadRequestException
     * @throws \Throwable
     */
    public function validateVpaUsingRblValidateVpaApi($vpaInput)
    {
        $shouldFetchSessionTokenFromGateway = false;
        $shouldFetchGatewayAuthTokenFromGateway = false;
        $retryCount = 0;
        $maxRetryCount = 3;

        while ($retryCount < $maxRetryCount)
        {
            try
            {
                $requestData = $this->getRequestDataForMozartRblValidateVpaAction(
                                        $vpaInput,
                                        $shouldFetchSessionTokenFromGateway,
                                        $shouldFetchGatewayAuthTokenFromGateway);

                $shouldFetchGatewayAuthTokenFromGateway = false;
                $shouldFetchSessionTokenFromGateway = false;

                $response = $this->app->mozart->sendMozartRequest(
                    'fts',
                    Channel::RBL,
                    \RZP\Gateway\Mozart\Action::VALIDATE_VPA,
                    $requestData,
                    Mozart::DEFAULT_MOZART_VERSION
                );

                return [
                    "customer_name" => $response['data']['description'],
                    "ifsc_code"     => $response['data']['ifsc_code'],
                    "success"       => true,
                ];
            }
            // Fav will be marked as failed if there is gateway error exception except when the error is
            // invalid vpa error (case when status is 0 and desc is empty)
            catch (Exception\GatewayErrorException $ex)
            {
                list($gatewayErrorCode, $gatewayErrorDesc) = $ex->getGatewayErrorCodeAndDesc();

                $this->trace->error(
                    TraceCode::RBL_FAV_VPA_VALIDATION_FAILED_GATEWAY_ERROR, [
                        'error' => $ex->getMessage(),
                        'gateway_error_code' => $gatewayErrorCode,
                        'gateway_error_desc' => $gatewayErrorDesc,
                        'retryCount' => $retryCount
                    ]
                );

                $this->trace->count(
                    Metric::RBL_VPA_VALIDATE_GATEWAY_ERROR_COUNT,
                    [
                        'gateway_error_code'        => $gatewayErrorCode,
                        'gateway_error_description' => $gatewayErrorDesc
                    ]);

                $data = $ex->getData()['data'];

                $retryCount += 1;

                if ($data['description'] === "" && $data['status'] === "0")
                {
                    return [
                        "success" => false
                    ];
                }
                else if ($gatewayErrorCode === "Your Session has been Expired or Invalid.Please Relogin the Application")
                {
                    $shouldFetchSessionTokenFromGateway = true;
                }
                else if ($gatewayErrorCode === "E001:Invalid Auth token")
                {
                    $shouldFetchGatewayAuthTokenFromGateway = true;
                }
                else
                {
                    throw $ex;
                }

                if ($retryCount >= $maxRetryCount)
                {
                    throw $ex;
                }
            }
            // If there is a failure other than gateway error exception, fav will remain in created state
            catch (\Throwable $ex)
            {
                $this->trace->error(
                    TraceCode::RBL_FAV_VPA_VALIDATION_FAILED, [
                        'error_code' => $ex->getCode(),
                        'error' => $ex->getMessage()
                    ]
                );

                throw $ex;
            }
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    public function getRequestDataForMozartRblValidateVpaAction($vpaInput, $shouldFetchSessionTokenFromGateway = false, $shouldFetchGatewayAuthTokenFromGateway = false): array
    {
        $credentials = $this->getAllRblApiCredentials();

        $sessionToken = $this->getSessionTokenForRblValidateVpaApi($shouldFetchSessionTokenFromGateway, $credentials);

        $gatewayAuthToken = $this->getGatewayAuthTokenForRblValidateVpaApi($shouldFetchGatewayAuthTokenFromGateway, $credentials, $sessionToken);

        list($username, $handle) = explode('@', $vpaInput['vpa']);

        return [
            FavEntity::FUND_ACCOUNT => [
                FaEntity::VPA => [
                    Fields::VPA_HANDLE => $handle,
                    Fields::VPA_USERNAME => $username,
                ]
            ],
            Fields::SOURCE_ACCOUNT   => [
                Fields::CREDENTIALS    => $credentials
            ],
            Fields::GATEWAY_AUTH => [
                Fields::TOKEN => empty($gatewayAuthToken) === false ? $gatewayAuthToken: $credentials[Fields::HMAC_KEY],
            ],
            Fields::GATEWAY_SESSION => [
                Fields::TOKEN => $sessionToken,
            ]
        ];
    }

    /**
     * @throws LogicException
     */
    public function getAllRblApiCredentials(): array
    {
        /**
         * @var Entity $bankingAccount
         */
        $bankingAccount = $this->repo
            ->banking_account
            ->fetchBankingAccountByMerchantIdAccountTypeChannelAndStatus(
                Constants::RblPoolAccountMID,
                Channel::RBL,
                AccountType::CURRENT,
                "migrated");

        $basCredentials = (new BankingAccountService())->fetchCredentialsFromApiAndBas(
            $bankingAccount->getMerchantId(),
            Channel::RBL,
            $bankingAccount->getAccountNumber()
        );

        return [
            Fields::AUTH_USERNAME       => $basCredentials[Fields::CREDENTIALS][Fields::AUTH_USERNAME],
            Fields::AUTH_PASSWORD       => $basCredentials[Fields::CREDENTIALS][Fields::AUTH_PASSWORD],
            Fields::CLIENT_ID           => $basCredentials[Fields::CREDENTIALS][Fields::CLIENT_ID],
            Fields::CLIENT_SECRET       => $basCredentials[Fields::CREDENTIALS][Fields::CLIENT_SECRET],
            Fields::CORP_ID             => $basCredentials[Fields::CREDENTIALS][Fields::CORP_ID],
            Fields::PAYER_VPA           => $this->fetchValueFromBankingAccountDetailsTable($bankingAccount, Fields::PAYER_VPA),
            Fields::BCAGENT             => $this->fetchValueFromBankingAccountDetailsTable($bankingAccount, Fields::BCAGENT),
            Fields::BCAGENT_USERNAME    => $this->fetchValueFromBankingAccountDetailsTable($bankingAccount, Fields::BCAGENT_USERNAME),
            Fields::BCAGENT_PASSWORD    => $this->fetchValueFromBankingAccountDetailsTable($bankingAccount, Fields::BCAGENT_PASSWORD),
            Fields::HMAC_KEY            => $this->fetchValueFromBankingAccountDetailsTable($bankingAccount, Fields::HMAC_KEY),
            Fields::MRCH_ORG_ID         => $this->fetchValueFromBankingAccountDetailsTable($bankingAccount, Fields::MRCH_ORG_ID),
            Fields::AGGR_ORG_ID         => $this->fetchValueFromBankingAccountDetailsTable($bankingAccount, Fields::AGGR_ORG_ID),
        ];
    }

    /**
     * @throws LogicException
     */
    public function fetchValueFromBankingAccountDetailsTable($bankingAccount, $key)
    {
        /**
         * @var $bankingAccountDetails \RZP\Models\BankingAccount\Detail\Entity
         */
        $bankingAccountDetails =  $this->repo->banking_account_detail->getDetailsForKeyAndBankingAccount($bankingAccount, $key);

        if ($bankingAccountDetails == null)
        {
            $this->trace->error(
                TraceCode::FAV_VPA_VALIDATE_CREDENTIAL_NOT_FOUND, [
                    'banking_account_id' => $bankingAccount->getId(),
                    'key'                => $key,
                    'merchant_id'        => $bankingAccount->getMerchantId(),
                ]
            );

            throw new Exception\LogicException(
                'banking account credentials not found',
                ErrorCode::BAD_REQUEST_VPA_VALIDATE_API_CREDENTIAL_NOT_FOUND,
                [
                    Entity::BANKING_ACCOUNT_ID  => $bankingAccount->getId(),
                    'key'                       => $key,
                ]);
        }

        return $bankingAccountDetails->getAttribute('gateway_value');
    }

    /**
     * @throws RuntimeException
     */
    protected function tokenizeKey(string $element): string
    {
        $request = [
                'namespace'    => Constants::CREDENTIALS_VAULT_NAMESPACE,
                'secret'       => $element,
                'bu_namespace' => Constants::RAZORPAYX_NODAL_CERTS,
            ];

        /** @var CardVault $cardVaultService */
        $cardVaultService = app('card.cardVault');

        $response = $cardVaultService->createVaultToken($request);

        return $response[CardVault::TOKEN];
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    public function getGatewayAuthTokenForRblValidateVpaApi(bool $shouldFetchGatewayAuthTokenFromGateway, $credentials, $sessionToken)
    {
        if ($shouldFetchGatewayAuthTokenFromGateway)
        {
            $startTime = millitime();

            $token = $this->fetchGatewayAuthTokenFromGateway($credentials, $sessionToken);

            $endTime = millitime();

            $this->trace->histogram(
                Metric::RBL_VPA_VALIDATE_GATEWAY_AUTH_TOKEN_FETCH_TIME_DURATION,
                $endTime-$startTime);

            $tokenizedValue = $this->tokenizeKey($token);

            (new AdminService)->setConfigKeys([ConfigKey::RBL_VPA_VALIDATE_API_GATEWAY_AUTH_TOKEN => $tokenizedValue]);

            return $tokenizedValue;
        }

        return (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_VPA_VALIDATE_API_GATEWAY_AUTH_TOKEN]);
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws ServerErrorException
     */
    public function fetchGatewayAuthTokenFromGateway($credentials, $sessionToken)
    {
        try
        {
            $requestData = $this->getRequestDataForMozartRblGatewayAuthAction($credentials, $sessionToken);

            $response = $this->app->mozart->sendMozartRequest('fts',
                Channel::RBL,
                \RZP\Gateway\Mozart\Action::GATEWAY_AUTH,
                $requestData,
                Mozart::DEFAULT_MOZART_VERSION,
                false
            );

            return $response['data']['gateway_auth']['token'];
        }
        catch (Exception\GatewayErrorException $ex)
        {
            throw $ex;
        }
        catch (\Throwable $exception)
        {
            $this->traceAndThrowMozartServerErrorException(
                $exception,
                TraceCode::MOZART_SERVICE_REQUEST_FAILED,
                'Fetch Gateway Auth Token Failed due to Server Error',
                ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR
            );
        }
    }

    public function getRequestDataForMozartRblGatewayAuthAction($credentials, $sessionToken): array
    {
        return [
            Fields::SOURCE_ACCOUNT   => [
                Fields::CREDENTIALS    => $credentials
            ],
            Fields::GATEWAY_SESSION => [
                Fields::TOKEN => $sessionToken
            ]
        ];
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    public function getSessionTokenForRblValidateVpaApi(bool $shouldFetchSessionTokenFromGateway, $credentials)
    {
        if ($shouldFetchSessionTokenFromGateway)
        {
            $startTime = millitime();

            $token = $this->fetchSessionTokenFromGateway($credentials);

            $endTime = millitime();

            $this->trace->histogram(
                Metric::RBL_VPA_VALIDATE_SESSION_TOKEN_FETCH_TIME_DURATION,
                $endTime-$startTime);

            (new AdminService)->setConfigKeys([ConfigKey::RBL_VPA_VALIDATE_API_SESSION_TOKEN => $token]);

            return $token;
        }

        return (new AdminService)->getConfigKey(['key' => ConfigKey::RBL_VPA_VALIDATE_API_SESSION_TOKEN]);
    }

    public function getRequestDataForMozartRblGatewaySessionAction($credentials): array
    {
        return [
            Fields::SOURCE_ACCOUNT   => [
                Fields::CREDENTIALS    => $credentials
            ]
        ];
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws ServerErrorException
     */
    public function fetchSessionTokenFromGateway($credentials)
    {
        try
        {
            $requestData = $this->getRequestDataForMozartRblGatewaySessionAction($credentials);

            $response = $this->app->mozart->sendMozartRequest('fts',
                Channel::RBL,
                \RZP\Gateway\Mozart\Action::GATEWAY_SESSION,
                $requestData,
                Mozart::DEFAULT_MOZART_VERSION,
                false
            );

            return $response['data']['gateway_session']['token'];
        }
        catch (Exception\GatewayErrorException $ex)
        {
            throw $ex;
        }
        catch (\Throwable $exception)
        {
            $this->traceAndThrowMozartServerErrorException(
                $exception,
                TraceCode::MOZART_SERVICE_REQUEST_FAILED,
                'Fetch Session Token Failed due to Server Error',
                ErrorCode::SERVER_ERROR_MOZART_SERVICE_ERROR
            );
        }
    }

    protected function traceAndThrowMozartServerErrorException(\Throwable $exception, string $traceCode, string $errorMessage, string $errorCode)
    {
        $this->trace->traceException(
            $exception,
            Logger::ERROR,
            $traceCode,
            [
                'error_code'  => $exception->getCode(),
                'error_message' => $exception->getMessage(),
            ]
        );

        throw new Exception\ServerErrorException(
            $errorMessage,
            $errorCode,
            [
                'error_code'  => $exception->getCode(),
                'error_message' => $exception->getMessage(),
            ]
        );
    }

    public function setDefaultValuesForValidation()
    {
        return;
    }
}
