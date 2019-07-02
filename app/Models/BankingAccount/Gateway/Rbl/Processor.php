<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use Carbon\Carbon;

use RZP\Constants;
use RZP\Services\FTS;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant\Balance;
use RZP\Exception\LogicException;

class Processor extends BankingAccount\Gateway\Processor
{
    const DATE_FORMAT                   = 'Y-m-d';

    const PINCODES_REDIS_KEY            = 'rbl_pincode_set';

    const CREDENTIALS_VAULT_NAMESPACE   = 'banking_account_creds';

    const MAX_RETRY_COUNT               = 1;

    const MAX_BANK_REFERENCE_NUMBER     = 100000;

    const START_BANK_REFERENCE_NUMBER   = 10000;

    public function preProcessAccountInfoNotification(array $input)
    {
        (new Validator)->validateInput(Validator::PRE_ACCOUNT_INFO_WEBHOOK, $input);

        $input = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::BODY];

        (new Validator)->validateInput(Validator::ACCOUNT_INFO_WEBHOOK, $input);
    }

    public function processAccountInfoNotification(array $input): array
    {
        $input = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::BODY];

        $attributes = $this->getMappedAttributes(Fields::$rblFieldsToEntityMap, $input);

        $attributes[BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE] = $this->parseAndFormatRblDate(
                                                        $attributes[BankingAccount\Entity::ACCOUNT_ACTIVATION_DATE]);

        $attributes[BankingAccount\Entity::STATUS] = BankingAccount\Status::PROCESSED;

        $attributes[BankingAccount\Entity::BANK_INTERNAL_STATUS] = Status::CLOSED;

        $attributes[BankingAccount\Entity::BANK_REFERENCE_NUMBER] = $input[Fields::REF_NUM_1];

        return $attributes;
    }

    public function postProcessAccountInfoNotificationResponse(array $input, string $status)
    {
        $tranId = $input[Fields::RZP_ALERT_NOTIFICATION_REQUEST][Fields::HEADER][Fields::TRAN_ID] ?? null;

        $bankStatus = Status::getInternalStatusForBankWebhook($status);

        if (empty($tranId) === true)
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_WEBHOOK_MISSING_TRANSACTION_ID,
                [
                    'channel' => BankingAccount\Channel::RBL,
                    'input'   => $input,
                ]);

            $bankStatus = Status::FAILURE;
        }

        $response = [
            Fields::RZP_ALERT_NOTIFICATION_RESPONSE => [
                Fields::HEADER =>
                    [
                        Fields::TRAN_ID => $tranId,
                    ],
                Fields::BODY   =>
                    [
                        Fields::STATUS => $bankStatus
                    ]
            ],
        ];

        return $response;
    }

    public function validateAccountBeforeUpdating(array $input)
    {
        $keys = [
            BankingAccount\Entity::BANK_INTERNAL_STATUS,
            BankingAccount\Entity::STATUS,
            BankingAccount\Entity::BANK_INTERNAL_REFERENCE_NUMBER,
            BankingAccount\Entity::BANK_REFERENCE_NUMBER,
        ];

        $attributes = array_only($input, $keys);

        (new Validator)->validateInput(Validator::ACCOUNT_UPDATE, $attributes);

        $this->checkRblToInternalStatusMapping($attributes);
    }

    /**
     * @param BankingAccount\Entity $bankingAccount
     * @param array $input
     * @return array
     */
    public function storeCredentials(BankingAccount\Entity $bankingAccount, array $input)
    {
        (new Validator)->validateInput(Validator::ADD_CREDENTIALS, $input);

        $input[Fields::SUBCORP_USER_PASSWORD] = $this->tokenizeCredentials($input[Fields::SUBCORP_USER_PASSWORD]);

        $balance = $this->fetchBalanceAndVerifyCredentials($bankingAccount, $input);

        // we will save credentials only once the mozart call is successful and we are able to receive
        // balance for the account.
        $attributes = $this->getMappedAttributes(Fields::$rblFieldsToEntityMap, $input);

        $bankingAccount->fill($attributes);

        $bankingAccount->saveOrFail($input);

        $balanceDetails = [
            Constants\Entity::BALANCE           => $balance,
            Balance\Entity::ACCOUNT_TYPE        => 'Direct',
            Balance\Entity::ACCOUNT_PROVIDER    => BankingAccount\Channel::RBL,
            Balance\Entity::ACCOUNT_NUMBER      => $bankingAccount->getAccountNumber(),
        ];

        return $balanceDetails;
    }
    
    public function generateRequestForSourceAccount(BankingAccount\Entity $bankingAccount)
    {
        $rbl = $this->config['gateway']['razorpayx']['ca']['rbl'];

        $credentials = [
            Fields::USERNAME                  => $rbl[Fields::AUTH_USERNAME],
            Fields::PASSWORD                  => $rbl[Fields::AUTH_PASSWORD],
            Fields::CLIENT_ID                 => $rbl[Fields::CLIENT_ID],
            Fields::CLIENT_SECRET             => $rbl[Fields::CLIENT_SECRET],
            Fields::SUBCORP_ID                => $bankingAccount->getUsername(),
            Fields::SUBCORP_USER_ID           => $bankingAccount->getPassword(),
            Fields::SUBCORP_USER_PASSWORD     => $bankingAccount->getReference1(),
        ];

        $mozartIdentifier = $rbl[Fields::MOZART_IDENTIFIER];

        $body = [
            FTS\Constants::CREDENTIALS       => $credentials,
            FTS\Constants::MOZART_IDENTIFIER => $mozartIdentifier
        ];

        return $body;
    }

    protected function fetchBalanceAndVerifyCredentials(BankingAccount\Entity $bankingAccount, array $input)
    {
        $request = $this->formatDataForMozartFetchBalanceApi($bankingAccount, $input);

        $retryCount = 0;

        while (true)
        {
            try
            {
                $response = $this->app->mozart->sendMozartRequest('razorpayx', BankingAccount\Channel::RBL,
                                                                   Action::ACCOUNT_BALANCE, $request);

                break;
            }
            catch (\Throwable $exception)
            {
                $errorCode = $exception->getCode();

                if ($errorCode === ErrorCode::SERVER_ERROR_MOZART_SERVICE_TIMEOUT)
                {
                    if ($retryCount < self::MAX_RETRY_COUNT)
                    {
                        $this->trace-info(
                            TraceCode::MOZART_SERVICE_RETRY,
                            [
                                'message' => $exception->getMessage(),
                                'data'    => $exception->getData(),
                            ]
                        );

                        $retryCount++;
                    }
                    else
                    {
                        throw $exception;
                    }
                }
                else
                {
                    throw $exception;
                }
            }
        }

        // ToDo add a validator for response format from mozart
        $balance = $response[Fields::DATA][Fields::GET_ACCOUNT_BALANCE][Fields::BODY]
                        [Fields::BAL_AMOUNT][Fields::AMOUNT_VALUE];

        return $this->getFormattedAmount($balance);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount * 100, 2, '.', '');
    }

    protected function formatDataForMozartFetchBalanceApi(BankingAccount\Entity $bankingAccount, array $input)
    {
        $credentials = $this->getAccountCredentials();
        
        $merchantCredentials = [
            Fields::SUBCORP_ID                => $bankingAccount->getUsername(),
            Fields::SUBCORP_USER_NAME         => $bankingAccount->getPassword(),
            Fields::SUBCORP_USER_PASSWORD     => $bankingAccount->getReference1()
        ];

        $credentials = array_merge($credentials, $merchantCredentials);

        $data = [
            Fields::SOURCE_ACCOUNT => [
                Fields::ACCOUNT_NUMBER => $bankingAccount->getAccountNumber(),
                Fields::ID             => $bankingAccount->getBankReferenceNumber(),
                Fields::CREDENTIALS    => $credentials
            ],
        ];

        return $data;
    }

     protected function validateResponse(array $response, array $request)
     {
         if ($response[Fields::GET_ACCOUNT_BALANCE][Fields::HEADER][Fields::SUBCORP_ID] !==
             ($request[Fields::CREDENTIALS][Fields::SUBCORP_ID]))
         {
             throw new BadRequestException(
                 ErrorCode::BAD_REQUEST_ACCOUNT_NUMBER_MISMATCH,
                 null,
                 [
                     'response' => $response,
                     'request'  => $request
                 ],
                 'Account details mismatch. Please try again'

             );
         }
     }

     protected function getAccountCredentials()
     {
         $config = $this->config['gateway']['razorpayx']['ca']['rbl'];

         $credentials = [
             Fields::USERNAME      => $config[Fields::AUTH_USERNAME],
             Fields::PASSWORD      => $config[Fields::AUTH_PASSWORD],
             Fields::CLIENT_ID     => $config[Fields::CLIENT_ID],
             Fields::CLIENT_SECRET => $config[Fields::CLIENT_SECRET],
         ];

         return $credentials;
     }

    protected function validateInputForAccountCreation(array $input)
    {
        (new Validator)->validateInput(Validator::ACCOUNT_AVAILABILITY, $input);
    }

    protected function preProcessInputForAccountCreation(array $input)
    {
        $availability =  $this->isPincodeRblServiceable($input[BankingAccount\Entity::PINCODE]);

        $bankReferenceNumber = $this->generateBankReferenceNumber();

        return [
            BankingAccount\Entity::STATUS                   => BankingAccount\Status::CREATED,
            BankingAccount\Entity::BANK_REFERENCE_NUMBER    => $bankReferenceNumber
        ];
    }

    protected function generateBankReferenceNumber()
    {
        $bankingAccount = $this->repo->banking_account->getLatestInsertedBankingAccountEntity(
                                                                            BankingAccount\Channel::RBL);
        if ($bankingAccount !== null)
        {
            $referenceNumber = (int) $bankingAccount->getBankReferenceNumber() + 1;

            if ($referenceNumber >= self::MAX_BANK_REFERENCE_NUMBER)
            {
                throw new LogicException('Rbl maximum account number limit reached',
                    ErrorCode::SERVER_ERROR_BANKING_ACCOUNT_NUMBER_LIMIT_REACHED,
                    [
                        BankingAccount\Entity::CHANNEL => BankingAccount\Channel::RBL
                    ]);
            }
        }
        else
        {
            $referenceNumber = self::START_BANK_REFERENCE_NUMBER;
        }

        return $referenceNumber;
    }

    /**
     * We are not rejecting requests based on the pincode availability for now.
     * This is being done to store all the leads we get for account creation.
     * Later we can choose to reject requests directly from here.
     *
     * @param string $pincode
     *
     * @return array
     */
    protected function isPincodeRblServiceable(string $pincode)
    {
        parent::isPincodeServiceable($pincode);
    }

    protected function getMappedAttributes($map, array $input)
    {
        $attr = [];

        foreach ($input as $key => $value)
        {
            if (isset($map[$key]))
            {
                $newKey        = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }

    protected function parseAndFormatRblDate(string $date)
    {
        $date = Carbon::parse($date)->format(self::DATE_FORMAT);

        return $date;
    }

    protected function checkRblToInternalStatusMapping(array $input)
    {
        if (isset($input[BankingAccount\Entity::BANK_INTERNAL_STATUS]) === false)
        {
            return;
        }

        $bankInternalStatus = $input[BankingAccount\Entity::BANK_INTERNAL_STATUS];

        $status = $input[BankingAccount\Entity::STATUS];

        Status::validateInternalBankStatusMappingToStatus($bankInternalStatus, $status);
    }
}
