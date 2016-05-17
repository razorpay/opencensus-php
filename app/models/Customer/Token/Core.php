<?php

namespace Models\Customer\Token;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Card;
use Models\Customer;
use Models\Terminal;
use Models\Customer\App;
use Models\Customer\Token;
use Models\Merchant\Account;

class Core extends Base\Core
{
    protected $custRepo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Token\Repository;

        $this->custRepo = new Customer\Repository;
    }

    public function create($customer, $input)
    {
        $token = new Token\Entity;

        if (isset($input[Token\Entity::CARD_ID]))
        {
            $card = (new Card\Repository)->findOrFailPublic($input[Token\Entity::CARD_ID]);

            $token->card()->associate($card);
        }

        if (isset($input[Token\Entity::TERMINAL_ID]))
        {
            $terminal = (new Terminal\Repository)->findOrFailPublic($input[Token\Entity::TERMINAL_ID]);

            $token->terminal()->associate($terminal);

            unset($input[Token\Entity::TERMINAL_ID]);
        }

        $token->customer()->associate($customer);
        $token->merchant()->associate($customer->merchant);

        $token->build($input);
        $this->validateExistingToken($token);

        $this->repo->saveOrFail($token);

        return $token;
    }

    public function edit($token, $input)
    {
        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_EDIT,
            [
                'token_id' => $token->getId(),
                'fields' => array_keys($input),
            ]);

        $token->edit($input);

        $this->validateExistingToken($token);

        $this->repo->saveOrFail($token);

        return $token;
    }

    public function fetchTokensByAppId($merchantId, $appId)
    {
        $appEntity = (new Customer\App\Repository)->findByIdAndMerchantId($appId, $merchantId);

        $tokens = $this->fetchTokensByCustomerId(Account::SHARED_ACCOUNT, $appEntity->customer->getId());

        return $tokens;
    }

    public function fetchTokensByCustomerId($merchantId, $customerId)
    {
        $customer = $this->custRepo->findByIdAndMerchantId($customerId, $merchantId);

        $tokens = $this->repo->getByCustomerId($customerId);

        return $tokens;
    }

    protected function validateExistingToken($token)
    {
        $params = array(
            Token\Entity::METHOD      => $token->getMethod(),
            Token\Entity::CUSTOMER_ID => $token->customer->getId());

        $existingTokens = $this->repo->getByMethodAndCustomerId(
                                $token->getMethod(), $token->customer->getId());

        $func = 'validateExistingToken'.$token->getMethod();

        $this->$func($existingTokens, $token);
    }

    protected function validateExistingTokenCard($existingTokens, $newToken)
    {
        foreach ($existingTokens as $token)
        {
            if ($token->card->getId() === $newToken->card->getId())
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CUSTOMER_CARD_ALREADY_EXISTS);
            }
        }
    }

    protected function validateExistingTokenNetbanking($existingTokens, $newToken)
    {
        foreach ($existingTokens as $token)
        {
            if (($token->getBank()  === $newToken->getBank()) and
                ($token->getGatewayToken() === $newToken->getGatewayToken()))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CUSTOMER_BANK_ALREADY_EXISTS);
            }
        }
    }

    protected function validateExistingTokenWallet($existingTokens, $newToken)
    {
        foreach ($existingTokens as $token)
        {
            if (($token->getWallet()  === $newToken->getWallet()) and
                ($token->getGatewayToken() === $newToken->getGatewayToken()))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CUSTOMER_WALLET_ALREADY_EXISTS);
            }
        }
    }
}
