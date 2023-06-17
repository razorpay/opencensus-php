<?php

namespace RZP\Services\PayoutService;

use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Models\Vpa;
use RZP\Models\Card;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Models\FundAccount;
use RZP\Models\BankAccount;
use RZP\Models\IdempotencyKey;
use RZP\Models\PayoutsDetails;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\RazorxTreatment;

class Create extends Base
{
    const CREATE_PAYOUT_SERVICE_URI                  = '/payouts';
    const CREATE_PAYOUT_INTERNAL_SERVICE_URI         = '/payouts/payouts_internal';
    const CREATE_INTERNAL_CONTACT_PAYOUT_SERVICE_URI = '/payouts/internal_contact_payout';
    // payout create service name for singleton class
    const PAYOUT_SERVICE_CREATE = 'payout_service_create';

    /**
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function createPayoutViaMicroservice(array $input,
                                                string $merchantId,
                                                bool $isInternal = false,
                                                array $creditsInfo = [],
                                                array $fundAccountInfo = [])
    {
        $data = $input;

        unset($data[Payout\Entity::ACCOUNT_NUMBER]);

        $this->trace->info(TraceCode::PAYOUT_CREATE_VIA_MICROSERVICE_REQUEST,
            [
                'input' => $data,
            ]);

        $uri = self::CREATE_PAYOUT_SERVICE_URI;

        if ($isInternal === true)
        {
            $uri = self::CREATE_INTERNAL_CONTACT_PAYOUT_SERVICE_URI;
        }

        elseif ($this->app['basicauth']->isAppAuth() === true)
        {
            $uri = self::CREATE_PAYOUT_INTERNAL_SERVICE_URI;
        }

        $request = $this->createRequestBody($input, $merchantId, $creditsInfo, $fundAccountInfo);

        $headers = $this->getHeadersWithJwt();

        $this->addIdempotencyKeyToHeaders($headers, $merchantId);

        $response = $this->makeRequestAndGetContent(
            $request,
            $uri,
            Requests::POST,
            $headers
        );

        return $response;
    }

    public function addIdempotencyKeyToHeaders(array & $headers, string $merchantId)
    {
        $idempotencyKeyId = $this->app['basicauth']->getIdempotencyKeyId();

        if (empty($idempotencyKeyId) === false)
        {
            $fetchInput = [
                IdempotencyKey\Entity::SOURCE_TYPE => Entity::PAYOUT,
                IdempotencyKey\Entity::ID          => $idempotencyKeyId,
            ];

            /** @var IdempotencyKey\Entity $idempotencyKeyEntity */
            $idempotencyKeyEntity = $this->repo->idempotency_key->fetch($fetchInput, $merchantId)->first();

            $headers[RequestHeader::X_PAYOUT_IDEMPOTENCY] = $idempotencyKeyEntity->getIdempotencyKey();
        }

        return $headers;
    }

    /**
     * Create request body for create request
     *
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function createRequestBody(array $input,
                                      string $merchantId,
                                      array $creditsInfo = [],
                                      array $fundAccountInfo = []): array
    {
        $fundAccountId = PublicEntity::stripDefaultSign($input[Payout\Entity::FUND_ACCOUNT_ID]);

        $requestBody = [
            Payout\Entity::PURPOSE               => $input[Payout\Entity::PURPOSE],
            Payout\Entity::AMOUNT                => (int) $input[Payout\Entity::AMOUNT],
            Payout\Entity::CURRENCY              => $input[Payout\Entity::CURRENCY],
            Payout\Entity::QUEUE_IF_LOW_BALANCE  => (boolean) ($input[Payout\Entity::QUEUE_IF_LOW_BALANCE] ?? false),
            Payout\Entity::ACCOUNT_NUMBER        => (string) $input[Payout\Entity::ACCOUNT_NUMBER],
            Payout\Entity::REFERENCE_ID          => $input[Payout\Entity::REFERENCE_ID] ?? null,
            Payout\Entity::NARRATION             => $input[Payout\Entity::NARRATION] ?? null,
            Payout\Entity::FUND_ACCOUNT_ID       => $fundAccountId,
            Payout\Entity::MERCHANT_ID           => $merchantId,
            Payout\Entity::FEE_TYPE              => $input[Payout\Entity::FEE_TYPE] ?? null,
        ];

        if (empty($input[Payout\Entity::SOURCE_DETAILS]) === false)
        {
            $requestBody[Payout\Entity::SOURCE_DETAILS] = $input[Payout\Entity::SOURCE_DETAILS];
        }
        if (empty($input[Payout\Entity::NOTES]) === false)
        {
            $requestBody[Payout\Entity::NOTES] = $input[Payout\Entity::NOTES];
        }
        if (isset($input[Payout\Entity::SCHEDULED_AT]) === true)
        {
            $requestBody[Payout\Entity::SCHEDULED_AT] = $input[Payout\Entity::SCHEDULED_AT];
        }
        if (isset($input[Payout\Entity::MODE]) === true)
        {
            $requestBody[Payout\Entity::MODE] = $input[Payout\Entity::MODE];
        }
        if (empty($input[Payout\Entity::SKIP_WORKFLOW]) === false)
        {
            $requestBody[Payout\Entity::SKIP_WORKFLOW] = $input[Payout\Entity::SKIP_WORKFLOW];
        }
        if (isset($input[Payout\Entity::ORIGIN]) === true)
        {
            $requestBody[Payout\Entity::ORIGIN] = $input[Payout\Entity::ORIGIN];
        }
        if (array_key_exists(Payout\Entity::ENABLE_WORKFLOW_FOR_INTERNAL_CONTACT, $input))
        {
            $requestBody[Payout\Entity::ENABLE_WORKFLOW_FOR_INTERNAL_CONTACT] = filter_var($input[Payout\Entity::ENABLE_WORKFLOW_FOR_INTERNAL_CONTACT], FILTER_VALIDATE_BOOLEAN);
        }

        // Passing info like credits and fund_account for PS payouts to avoid back and forth calls to API.
        if ((isset($creditsInfo[Payout\Entity::FETCH_UNUSED_CREDITS_SUCCESS]) === true) and
            ($creditsInfo[Payout\Entity::FETCH_UNUSED_CREDITS_SUCCESS] === true))
        {
            $requestBody[Payout\Entity::EXTRA_INFO] = [
                Payout\Entity::CREDITS_INFO => [
                    Payout\Entity::AMOUNT => (int) $creditsInfo[Payout\Entity::UNUSED_CREDITS]
                ]
            ];
        }

        $this->addFundAccountExtraInfoInRequestBody($requestBody, $fundAccountInfo);

        if (empty($input[PayoutsDetails\Entity::ATTACHMENTS]) === false)
        {
            $requestBody[PayoutsDetails\Entity::ATTACHMENTS] = $input[PayoutsDetails\Entity::ATTACHMENTS];
        }

        if (empty($input[PayoutsDetails\Entity::TDS]) === false)
        {
            $requestBody[PayoutsDetails\Entity::TDS] = $input[PayoutsDetails\Entity::TDS];
        }

        if (empty($input[PayoutsDetails\Entity::SUBTOTAL_AMOUNT]) === false)
        {
            $requestBody[PayoutsDetails\Entity::SUBTOTAL_AMOUNT] = $input[PayoutsDetails\Entity::SUBTOTAL_AMOUNT];
        }

        return $requestBody;
    }

    /**
     * @param array $request
     * @param array $fundAccountInfo
     * @return void
     */
    public function addFundAccountExtraInfoInRequestBody(array & $request, array $fundAccountInfo = [])
    {
        if ((empty($fundAccountInfo[Payout\Entity::FETCH_FUND_ACCOUNT_INFO_SUCCESS]) === true) or
            (empty($fundAccountInfo[Payout\Entity::FUND_ACCOUNT]) === true))
        {
            return;
        }

        $fundAccountObject = $fundAccountInfo[Payout\Entity::FUND_ACCOUNT];

        $fundAccountExtraInfoRequestBody = [
            FundAccount\Entity::ID            => $fundAccountObject[FundAccount\Entity::ID],
            FundAccount\Entity::ENTITY        => $fundAccountObject[FundAccount\Entity::ENTITY ],
            FundAccount\Entity::CONTACT_ID    => $fundAccountObject[FundAccount\Entity::CONTACT_ID],
            FundAccount\Entity::ACCOUNT_TYPE  => $fundAccountObject[FundAccount\Entity::ACCOUNT_TYPE],
            FundAccount\Entity::ACTIVE        => $fundAccountObject[FundAccount\Entity::ACTIVE],
            FundAccount\Entity::BATCH_ID      => $fundAccountObject[FundAccount\Entity::BATCH_ID],
            FundAccount\Entity::CREATED_AT    => $fundAccountObject[FundAccount\Entity::CREATED_AT],
        ];

        if (empty($fundAccountObject[FundAccount\Entity::BANK_ACCOUNT]) === false)
        {
            $bankAccountExtraInfo = $fundAccountObject[FundAccount\Entity::BANK_ACCOUNT];

            $fundAccountExtraInfoRequestBody[FundAccount\Entity::BANK_ACCOUNT] = [
                BankAccount\Entity::ID              => $bankAccountExtraInfo[BankAccount\Entity::ID],
                BankAccount\Entity::NAME            => $bankAccountExtraInfo[BankAccount\Entity::NAME],
                BankAccount\Entity::IFSC            => $bankAccountExtraInfo[BankAccount\Entity::IFSC],
                BankAccount\Entity::ACCOUNT_NUMBER  => $bankAccountExtraInfo[BankAccount\Entity::ACCOUNT_NUMBER],
                BankAccount\Entity::BANK_NAME       => $bankAccountExtraInfo[BankAccount\Entity::BANK_NAME],
            ];
        }

        if (empty($fundAccountObject[FundAccount\Entity::CARD]) === false)
        {
            $cardExtraInfo = $fundAccountObject[FundAccount\Entity::CARD];

            $fundAccountExtraInfoRequestBody[FundAccount\Entity::CARD] = [
                Card\Entity::ID            => $cardExtraInfo[Card\Entity::ID],
                Card\Entity::TYPE          => $cardExtraInfo[Card\Entity::TYPE],
                Card\Entity::LAST4         => $cardExtraInfo[Card\Entity::LAST4],
                Card\Entity::ISSUER        => $cardExtraInfo[Card\Entity::ISSUER],
                Card\Entity::SUBTYPE       => $cardExtraInfo[Card\Entity::SUBTYPE],
                Card\Entity::NETWORK       => $cardExtraInfo[Card\Entity::NETWORK],
                Card\Entity::TOKEN_IIN     => $cardExtraInfo[Card\Entity::TOKEN_IIN],
                Card\Entity::TOKEN_LAST_4  => $cardExtraInfo[Card\Entity::TOKEN_LAST_4],
                Card\Entity::VAULT_TOKEN   => $cardExtraInfo[Card\Entity::VAULT_TOKEN],
                Card\Entity::VAULT         => $cardExtraInfo[Card\Entity::VAULT],
                Card\Entity::TRIVIA        => $cardExtraInfo[Card\Entity::TRIVIA],
                Card\Entity::INPUT_TYPE    => $cardExtraInfo[Card\Entity::INPUT_TYPE],
            ];
        }

        if (empty($fundAccountObject[FundAccount\Entity::VPA]) === false)
        {
            $vpaExtraInfo = $fundAccountObject[FundAccount\Entity::VPA];

            $fundAccountExtraInfoRequestBody[FundAccount\Entity::VPA] = [
                Vpa\Entity::ID        => $vpaExtraInfo[Vpa\Entity::ID],
                Vpa\Entity::USERNAME  => $vpaExtraInfo[Vpa\Entity::USERNAME],
                Vpa\Entity::HANDLE    => $vpaExtraInfo[Vpa\Entity::HANDLE],
                Vpa\Entity::ADDRESS   => $vpaExtraInfo[Vpa\Entity::ADDRESS],
            ];
        }

        if (empty($fundAccountObject[FundAccount\Entity::CONTACT]) === false)
        {
            $contactExtraInfo = $fundAccountObject[FundAccount\Entity::CONTACT];

            $fundAccountExtraInfoRequestBody[FundAccount\Entity::CONTACT] = [
                Contact\Entity::ID            => $contactExtraInfo[Contact\Entity::ID],
                Contact\Entity::ENTITY        => $contactExtraInfo[Contact\Entity::ENTITY],
                Contact\Entity::NAME          => $contactExtraInfo[Contact\Entity::NAME],
                Contact\Entity::CONTACT       => $contactExtraInfo[Contact\Entity::CONTACT],
                Contact\Entity::EMAIL         => $contactExtraInfo[Contact\Entity::EMAIL],
                Contact\Entity::TYPE          => $contactExtraInfo[Contact\Entity::TYPE],
                Contact\Entity::REFERENCE_ID  => $contactExtraInfo[Contact\Entity::REFERENCE_ID],
                Contact\Entity::BATCH_ID      => $contactExtraInfo[Contact\Entity::BATCH_ID],
                Contact\Entity::ACTIVE        => $contactExtraInfo[Contact\Entity::ACTIVE],
                Contact\Entity::CREATED_AT    => $contactExtraInfo[Contact\Entity::CREATED_AT],
            ];
        }

        $request[Payout\Entity::EXTRA_INFO] += [
            Payout\Entity::FUND_ACCOUNT_INFO => [
                Payout\Entity::FUND_ACCOUNT => $fundAccountExtraInfoRequestBody
            ]
        ];
    }
}
