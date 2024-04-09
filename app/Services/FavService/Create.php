<?php

namespace RZP\Services\FavService;


use RZP\Models\Vpa;
use RZP\Models\Card;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Models\BankAccount;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use RZP\Models\IdempotencyKey;
use RZP\Models\PayoutsDetails;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Base\PublicEntity;
use Razorpay\Edge\Passport\Passport;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\FundAccount\Validation\Entity as FavEntity;

class Create extends Base
{
    const CREATE_FAV_SERVICE_URI = '/fund_accounts/validations';

    // fav create service name for singleton class
    const FAV_SERVICE_CREATE     = 'fav_service_create';

    /**
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function createFavViaMicroservice(array $input,
                                                string $merchantId)
    {
        $uri = self::CREATE_FAV_SERVICE_URI;

        $request = $this->createRequestBody($input, $merchantId);

        $this->trace->info(TraceCode::FAV_CREATE_VIA_MICROSERVICE_REQUEST,
            [
                'request'   => $request,
            ]);

        $headers = $this->getHeadersWithJwt();

//        TODO: add idempotency key for fav service headers if given by merchant
//        $this->addIdempotencyKeyToHeaders($headers, $merchantId);

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
                                      string $merchantId): array
    {
        $requestBody = [
            FavEntity::SOURCE_ACCOUNT_NUMBER     => (string)$input[FavEntity::SOURCE_ACCOUNT_NUMBER],
            FavEntity::VALIDATION_TYPE           => (string)$input[FavEntity::VALIDATION_TYPE],
            FavEntity::REFERENCE_ID              => $input[FavEntity::REFERENCE_ID] ?? null,
            FavEntity::FUND_ACCOUNT              => $input[FavEntity::FUND_ACCOUNT],
            FavEntity::MERCHANT_ID               => $merchantId
        ];

        if (empty($input[FavEntity::NOTES]) === false)
        {
            $requestBody[FavEntity::NOTES] = $input[FavEntity::NOTES];
        }

        return $requestBody;
    }

}
