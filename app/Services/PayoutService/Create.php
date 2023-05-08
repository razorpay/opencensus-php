<?php

namespace RZP\Services\PayoutService;

use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
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

    const TYPE     = 'type';
    const CONSUMER = 'consumer';
    const PASSPORT = 'passport';

    const NAME               = 'name';
    const APP_USER_ID_HEADER = 'App-User-Id';

    /**
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function createPayoutViaMicroservice(array $input,
                                                string $merchantId,
                                                bool $isInternal = false,
                                                array $creditsInfo = [])
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

        $request = $this->createRequestBody($input, $merchantId, $creditsInfo);

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

    public function getHeadersWithJwt()
    {
        $jwt = $this->app['basicauth']->getPassportJwt($this->baseUrl);

        /** @var BasicAuth $ba */
        $ba = $this->app['basicauth'];

        $headers = [];

        if ($ba->isPrivilegeAuth() === true)
        {
            $passport = $ba->getPassport();

            if (array_key_exists(self::CONSUMER, $passport) === true)
            {
                if ($passport[self::CONSUMER][self::TYPE] === BasicAuth::PASSPORT_CONSUMER_TYPE_USER)
                {
                    $this->trace->info(TraceCode::PASSPORT_EDIT_FOR_PRIVILEGE_AUTH_WITH_USER_CLAIMS,
                                       [
                                           self::PASSPORT => $ba->getPassport(),
                                       ]);

                    $baTemp = clone $ba;

                    $baTemp->setPassportConsumerClaims(BasicAuth::PASSPORT_CONSUMER_TYPE_APPLICATION,
                                                       $ba->getInternalApp(),
                                                       true,
                                                       [self::NAME => $ba->getInternalApp()]);

                    $jwt = $baTemp->getPassportJwt($this->baseUrl);

                    $headers[self::APP_USER_ID_HEADER] = $ba->getUser()->getId();
                }
            }
        }

        $headers[Passport::PASSPORT_JWT_V1] = $jwt;

        return $headers;
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
                                      array $creditsInfo = []): array
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
}
