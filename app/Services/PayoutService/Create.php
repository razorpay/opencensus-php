<?php

namespace RZP\Services\PayoutService;

use Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicEntity;

class Create extends Base
{
    const CREATE_PAYOUT_SERVICE_URI = '/payouts';

    // payout create service name for singleton class
    const PAYOUT_SERVICE_CREATE = 'payout_service_create';

    /**
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function createPayoutViaMicroservice(array $input, string $merchantId)
    {
        $this->trace->info(TraceCode::PAYOUT_CREATE_VIA_MICROSERVICE_REQUEST,
            [
                'input' => $input,
            ]);

        $request = $this->createRequestBody($input, $merchantId);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent(
            $request,
            self::CREATE_PAYOUT_SERVICE_URI,
            Requests::POST,
            $headers
        );

        return $response;
    }

    /**
     * Create request body for create request
     *
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function createRequestBody(array $input, string $merchantId): array
    {
        $fundAccountId = PublicEntity::stripDefaultSign($input[Payout\Entity::FUND_ACCOUNT_ID]);

        return [
            Payout\Entity::PURPOSE               => $input[Payout\Entity::PURPOSE],
            Payout\Entity::AMOUNT                => (int) $input[Payout\Entity::AMOUNT],
            Payout\Entity::CURRENCY              => $input[Payout\Entity::CURRENCY],
            Payout\Entity::MODE                  => $input[Payout\Entity::MODE],
            Payout\Entity::QUEUE_IF_LOW_BALANCE  => $input[Payout\Entity::QUEUE_IF_LOW_BALANCE] ?? false,
            Payout\Entity::ACCOUNT_NUMBER        => $input[Payout\Entity::ACCOUNT_NUMBER],
            Payout\Entity::REFERENCE_ID          => $input[Payout\Entity::REFERENCE_ID] ?? null,
            Payout\Entity::NARRATION             => $input[Payout\Entity::NARRATION] ?? null,
            Payout\Entity::FUND_ACCOUNT_ID       => $fundAccountId,
            Payout\Entity::MERCHANT_ID           => $merchantId,
            Payout\Entity::FEE_TYPE              => $input[Payout\Entity::FEE_TYPE] ?? null
        ];
    }
}
