<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use App;
use Throwable;
use Exception;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Constants;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;

class RequestResponseFormatting
{
    const EMAIL_ALREADY_EXIST = 'EMAIL_ALREADY_EXIST';
    const INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';

    /**
     * @var mixed
     */
    private $trace;

    private $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    public function processErrorAndReturnResponse(array $input, string $status, Throwable $e = null): array
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_CREATE_FROM_RBL_LEAD_API_TRACE, [
            'input' => $input,
            'error' => $e,
            'status' => $status
        ]);

        if ($status === "")
        {
            $message = $e->getMessage();

            $this->trace->error(TraceCode::BANKING_ACCOUNT_CREATE_FROM_RBL_LEAD_API_ERROR, ['message' => $e->getMessage(), 'error' => $e]);

            if ($message == "The email has already been taken.")
            {
                $status = self::EMAIL_ALREADY_EXIST;
            }
            else
            {
                $status = self::INTERNAL_SERVER_ERROR;
            }
        }
        return $this->getResponseForStatus($input, $status, $e);
    }

    /**
     * @throws Exception
     */
    public function extractMerchantCreatePayload(array $input): array
    {
        return [
            \RZP\Models\User\Entity::NAME     => $input[Fields::NEO_BANKING_LEAD_REQUEST][Fields::BODY][Fields::CO_CREATED_CUSTOMER_NAME],
            \RZP\Models\User\Entity::EMAIL    => $input[Fields::NEO_BANKING_LEAD_REQUEST][Fields::BODY][Fields::EMAIL_ADDRESS],
            \RZP\Models\User\Entity::PASSWORD => bin2hex(random_bytes(20)),
        ];
    }


    /**
     * @param array $input
     *
     * @return array
     */
    public function getPreSignupPayload(array $input): array
    {
        // Business Type and Transaction volume are set some dummy value
        return [
            \RZP\Models\Merchant\Detail\Entity::CONTACT_NAME       => $input[Fields::NEO_BANKING_LEAD_REQUEST][Fields::BODY][Fields::CO_CREATED_CUSTOMER_NAME],
            \RZP\Models\Merchant\Detail\Entity::CONTACT_MOBILE     => $input[Fields::NEO_BANKING_LEAD_REQUEST][Fields::BODY][Fields::CUSTOMER_MOBILE_NUMBER],
            \RZP\Models\Merchant\Detail\Entity::BUSINESS_TYPE      => 4,
            \RZP\Models\Merchant\Detail\Entity::TRANSACTION_VOLUME => 2,
        ];
    }

    /**
     * @param array           $input
     * @param string          $status
     * @param Throwable|null $e
     *
     * @return array[]
     */
    private function getResponseForStatus(array $input, string $status, Throwable $e = null): array
    {
        switch ($status)
        {
            case Status::SUCCESS:
                return [
                    Fields::NEO_BANKING_LEAD_RESPONSE => [
                        Fields::HEADER => [
                            Fields::TRAN_ID            => $input[Fields::TRAN_ID],
                            Fields::CO_CREATED_CORP_ID => $input[Fields::CO_CREATED_CORP_ID],
                            Fields::STATUS             => Status::SUCCESS,
                            Constants::STATUS_MESSAGE  => Constants::SUCCESS_MESSAGE
                        ],
                    ]
                ];
            case Status::FAIL:
                $messages = ($e->getMessageBag())->getMessages();

                $message = array_key_first($messages);

                $validationFailedFor = explode('.', $message);

                return [
                    Fields::HEADER => [
                        Fields::TRAN_ID            => $input[Fields::TRAN_ID],
                        Fields::CO_CREATED_CORP_ID => $input[Fields::CO_CREATED_CORP_ID],
                        Fields::STATUS             => Status::FAIL,
                        Constants::ERROR_DESC      => sprintf(Constants::ERROR_MESSAGE, $validationFailedFor[2])
                    ],
                ];
            case self::EMAIL_ALREADY_EXIST:
                return [
                    Fields::HEADER => [
                        Fields::TRAN_ID            => $input[Fields::TRAN_ID],
                        Fields::CO_CREATED_CORP_ID => $input[Fields::CO_CREATED_CORP_ID],
                        Fields::STATUS             => Status::FAIL,
                        Constants::ERROR_DESC      => "EMAIL_ALREADY_EXIST"
                    ],
                ];
            case self::INTERNAL_SERVER_ERROR:
                return [
                    Fields::HEADER => [
                        Fields::TRAN_ID            => $input[Fields::TRAN_ID],
                        Fields::CO_CREATED_CORP_ID => $input[Fields::CO_CREATED_CORP_ID],
                        Fields::STATUS             => Status::FAIL,
                        Constants::ERROR_DESC      => 'INTERNAL_SERVER_ERROR'
                    ],
                ];
            default:
                $this->trace->error(TraceCode::BANKING_ACCOUNT_CREATE_FROM_RBL_LEAD_API_ERROR, ['message' => $e->getMessage()]);

                return [
                    Fields::HEADER => [
                        Fields::TRAN_ID            => $input[Fields::TRAN_ID],
                        Fields::CO_CREATED_CORP_ID => $input[Fields::CO_CREATED_CORP_ID],
                        Fields::STATUS             => Status::FAIL,
                        Constants::ERROR_DESC      => 'INTERNAL_SERVER_ERROR'
                    ],
                ];
        }
    }

    public function extractBankingAccountPayload(array $input): array
    {
        $payload = [];

        $payload[Entity::CHANNEL] = 'rbl';

        $payload[Entity::PINCODE] = $input[Fields::CUSTOMER_PINCODE];

        $payload[Entity::ACTIVATION_DETAIL] = [
            ActivationDetail\Entity::APPLICATION_TYPE => ActivationDetail\Entity::CO_CREATED,

            ActivationDetail\Entity::MERCHANT_POC_NAME => $input[Fields::CO_CREATED_CUSTOMER_NAME],

            ActivationDetail\Entity::MERCHANT_POC_EMAIL => $input[Fields::EMAIL_ADDRESS],

            ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER => $input[Fields::CUSTOMER_MOBILE_NUMBER],

            ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS => $input[Fields::CUSTOMER_ADDRESS],

            ActivationDetail\Entity::MERCHANT_CITY => $input[Fields::CUSTOMER_CITY],
        ];

        return $payload;
    }

    public function extractBankingEntityUpdatePayload(array $input): array
    {
        $payload = [];

        $payload[Entity::BANK_INTERNAL_REFERENCE_NUMBER] = $input[Fields::NEO_BANKING_LEAD_REQUEST][Fields::BODY][Fields::LEAD_ID];

        return $payload;
    }
}
