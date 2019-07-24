<?php

namespace RZP\Models\BankingAccount\Gateway;

use Redis;

use Razorpay\Trace;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Services\CardVault;
use RZP\Exception\LogicException;
use RZP\Models\BankingAccount\Channel;
use RZP\Exception\BadRequestException;

class Processor extends Base\Core
{
    const PINCODES_REDIS_KEY = 'pincode_set';

    const CREDENTIALS_VAULT_NAMESPACE = 'banking_account_creds';

    public function validateAndPreProcessInputForAccountCreation(array $input)
    {
        $this->validateInputForAccountCreation($input);

        return $this->preProcessInputForAccountCreation($input);
    }

    public function preProcessAccountInfoNotification(array $input)
    {
        return;
    }

    public function processAccountInfoNotification(array $input): array
    {
        return [];
    }

    public function postProcessAccountInfoNotificationResponse(array $input, string $status)
    {
        return [];
    }

    public function validateAccountBeforeUpdating(array $input)
    {
        return;
    }

    public function formatInputParametersIfRequired(array $input)
    {
        return $input;
    }

    public function addServiceablePincodes(array $pincodes)
    {
        $redis = Redis::connection();

        $redis->sadd(static::PINCODES_REDIS_KEY, $pincodes);
    }

    public function deleteServiceablePincodes(array $pincodes)
    {
        $redis = Redis::connection();

        $redis->srem(static::PINCODES_REDIS_KEY, $pincodes);
    }

    protected function tokenizeCredentials(string $element): string
    {
        $request = $traceRequest =
            [
                'namespace' => self::CREDENTIALS_VAULT_NAMESPACE,
                'secret'    => $element
            ];

        unset($traceRequest['secret']);

        try
        {
            // If the vault service times out after some retries, we want to show error to the merchant
            /** @var CardVault $cardVaultService */
            $cardVaultService = app('card.cardVault');

            $response = $cardVaultService->createVaultToken($request);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace\Logger::CRITICAL,
                TraceCode::CARD_VAULT_REQUEST_FAILED,
                [
                    'request' => $traceRequest,
                    'channel' => Channel::RBL
                ]);

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED,
                null,
                [
                    'request' => $traceRequest,
                    'channel' => Channel::RBL
                ]
            );
        }

        $this->checkForVaultResponseErrors($response);

        return $response[CardVault::TOKEN];
    }

    protected function checkForVaultResponseErrors(array $response)
    {
        if ($response[CardVault::SUCCESS] === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_ACTIVATION_FAILED,
                null,
                ['response' => $response]);
        }
    }

    /**
     * We are not rejecting requests based on the pincode availability for now.
     * This is being done to store all the leads we get for account creation.
     * Later we can choose to reject requests directly from here.
     *
     * @param string $pincode
     *
     * @return bool
     */
    protected function isPincodeServiceable(string $pincode): bool
    {
        $redis = Redis::connection();

        $isAvailable = $redis->sismember(static::PINCODES_REDIS_KEY, $pincode);

        return (bool) $isAvailable;
    }
}
