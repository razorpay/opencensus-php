<?php

namespace RZP\Models\BankingAccount\Gateway;

use Redis;
use GuzzleHttp\Exception\RequestException;

use Razorpay\Trace;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Services\CardVault;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Channel;

class Processor extends Base\Core
{
    const PINCODES_REDIS_KEY = 'pincode_set';

    const CREDENTIALS_VAULT_NAMESPACE = 'banking_account_creds';

    public function validateAndPreProcessInputForAccountCreation(array $input)
    {
        $this->validateInputForAccountCreation($input);

        return $this->preProcessInputForAccountCreation($input);
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

    protected function tokenizeCredentials(string $element)
    {
        $request = [
            'namespace' => self::CREDENTIALS_VAULT_NAMESPACE,
            'secret'    => $element
        ];

        try
        {
            // if the vault service times out after some retries, we want to show error to the merchant
            $response = $this->app['card.cardVault']->createVaultToken($request);
        }
        catch (\Requests_Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace\Logger::CRITICAL,
                TraceCode::CARD_VAULT_REQUEST_TIMEOUT,
                [
                    'request' => $request,
                    'channel' => Channel::RBL
                ]);

            throw $e;
        }

        $this->checkForVaultResponseErrors($response);

        return $response[CardVault::TOKEN];
    }

    protected function checkForVaultResponseErrors(array $response)
    {
        if ($response[CardVault::SUCCESS] === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_VAULT_TOKENIZE_FAILED,
                null,
                [
                    'response' => $response
                ],
                'Merchant credentials could not be stored due to failure in Vault Service, Please try again!'
            );
        }
    }

    protected function isPincodeServiceable(string $pincode): bool
    {
        $redis = Redis::connection();

        $isAvailable = $redis->sismember(static::PINCODES_REDIS_KEY, $pincode);

        return (bool) $isAvailable;
    }
}
