<?php

namespace RZP\Models\Customer\Token;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Customer\AppToken;
use RZP\Models\Customer\Token;
use RZP\Models\Customer\GatewayToken;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Token\Core;
    }

    /**
     * Adds token for a customer
     *
     * @param string $id customer ID
     * @param array  $input token params
     *
     * @return array
     */
    public function add($id, $input)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $token = $this->core->createDirectToken($customer, $input);

        return $token->toArrayPublic();
    }

    /**
     * Edit an existing token for local customer
     * @param  string $id customer_id
     * @param  entity $tokenId token
     * @param  array  $input token edit params
     *
     * @return array  edited token
     */
    public function edit($id, $tokenId, $input)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $token = $this->core->getByTokenIdAndCustomer($tokenId, $customer);

        $token = $this->core->edit($token, $input);

        return $token->toArrayPublic();
    }

    /**
     * fetch token for local customer
     *
     * @param  string $id customer_id
     * @param  string $tokenId token id
     * @return entity token
     */
    public function fetch($id, $tokenId)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $token = $this->core->getByTokenIdAndCustomer($tokenId, $customer);

        return $token->toArrayPublic();
    }

    /**
     * fetch tokens for local customer
     *
     * @param string $id customer ID
     *
     * @return entity tokens
     */
    public function fetchMultiple($id)
    {
        // This is needed to ensure that the merchant is getting only HIS customer's details
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $tokens = $this->repo->token->getByCustomer($customer);

        return $tokens->toArrayPublic();
    }

    /**
     * fetch tokens for an app_token (global customer)
     *
     * @return entity tokens
     */
    public function fetchTokensForGlobalCustomer()
    {
        $appTokenId = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        $tokens = new Base\PublicCollection;

        if ($appTokenId !== null)
        {
            $app = (new AppToken\Core)->getAppByAppTokenId($appTokenId, $this->merchant);

            $tokens = $this->core->fetchTokensByCustomer($app->customer);
        }

        return $tokens->toArrayPublic();
    }

    /**
     * Deletes tokens associated with the local customer
     */
    public function deleteTokenForLocalCustomer($id, $token)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        return $this->deleteTokenForCustomer($token, $customer);
    }

    /**
     * Deletes token associated with a card for a global customer
     */
    public function deleteTokenForGlobalCustomer($token)
    {
        $appTokenId = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        if ($appTokenId !== null)
        {
            $app = (new AppToken\Core)->getAppByAppTokenId($appTokenId, $this->merchant);

            return $this->deleteTokenForCustomer($token, $app->customer);
        }

        return null;
    }

    public function migrateToGatewayTokens(array $input = [])
    {
        $failureCount = $total = $successCount = 0;
        $failures = [];

        if (empty($input['token_ids']) === false)
        {
            $tokens = $this->repo->token->findMany($input['token_ids']);
        }
        else
        {
            //
            // Hardcoding these for now here, just to ensure
            // we don't migrate wrong stuff by mistake.
            //
            $input[Entity::METHOD] = 'card';
            $input[Entity::RECURRING] = true;

            $tokens = $this->repo->token->fetch($input);
        }

        $total = $tokens->count();

        $this->trace->info(
            TraceCode::TOKENS_FETCHED_COUNT_FOR_MIGRATE,
            [
                'input' => $input,
                'count' => $total,
            ]);

        foreach ($tokens as $token)
        {
            $this->trace->info(TraceCode::TOKEN_BEING_MIGRATED, $token->toArrayPublic());

            if (($token->isRecurring() === false) or ($token->getMethod() !== 'card'))
            {
                throw new Exception\LogicException(
                    'Only card and recurring tokens can be migrated',
                    null,
                    [
                        $token->toArrayPublic()
                    ]);
            }

            try
            {
                $gatewayTokenInput = [
                    GatewayToken\Entity::RECURRING      => $token->isRecurring(),
                    GatewayToken\Entity::ACCESS_TOKEN   => $token->getGatewayToken(),
                    GatewayToken\Entity::REFRESH_TOKEN  => $token->getGatewayToken2(),
                ];

                $gatewayToken = (new GatewayToken\Entity)->build($gatewayTokenInput);

                $gatewayToken->token()->associate($token);
                $gatewayToken->merchant()->associate($token->merchant);
                $gatewayToken->terminal()->associate($token->terminal);

                $this->repo->saveOrFail($gatewayToken);

                $this->trace->info(TraceCode::GATEWAY_TOKEN_MIGRATED, $gatewayToken->toArray());

                $successCount += 1;
            }
            catch(\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::DEBUG,
                    TraceCode::TOKEN_MIGRATE_TO_GATEWAY_TOKEN_FAILED,
                    [
                        'token' => $token->toArrayPublic(),
                    ]);

                $failureCount += 1;
                $failures[] = $token->getId();

                continue;
            }
        }

        $summary = [
            'total'         => $total,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failures'      => $failures,
        ];

        return $summary;
    }

    protected function deleteTokenForCustomer($tokenId, $customer)
    {
        $token = $this->core->getByTokenIdAndCustomer($tokenId, $customer);

        if ($token === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token not found');
        }

        $token = $this->repo->token->deleteOrFail($token);

        if ($token === null)
        {
            return ['deleted' => true];
        }

        return $token->toArrayPublic();
    }
}
