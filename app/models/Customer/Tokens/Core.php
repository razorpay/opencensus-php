<?php

namespace Models\Customer\Token;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Customer;
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
        $input[Token\Entity::CUSTOMER_ID] = $customer->getKey();

        $input[Token\Entity::TOKEN] = $this->generateToken();

        $token = (new Token\Entity)->build($input);

        $this->validateExistingToken($token);

        $this->repo->saveOrFail($token);

        return $token;
    }

    public function edit($token, $input)
    {
        $this->trace->info(
            TraceCode::CUSTOMER_TOKENS_EDIT,
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
        $appEntity = (new Customer\App\Repository)->findByAppIdAndMerchantId($appId, $merchantId);

        $tokens = $this->fetchTokensByCustomerId(Account::SHARED_ACCOUNT, $appEntity->getCustomerId());

        return $tokens;
    }

    public function fetchTokensByCustomerId($merchantId, $customerId)
    {
        $customer = $this->custRepo->findOrFailPublic($customerId);

        assert($customer->getMerchantId() === $merchantId);

        $tokens = $this->repo->getByCustomerId($customerId);

        return $tokens;
    }

    protected function validateExistingToken($token)
    {
        $params = array(
            Token\Entity::METHOD      => $token->getMethod(),
            Token\Entity::CUSTOMER_ID => $token->getCustomerId(),
        );

        $existingTokens = $this->repo->getByParams($params);

        $func = 'validateExistingToken'.$token->getMethod();

        $this->$func($existingTokens, $token);
    }

    protected function validateExistingTokenCard($existingTokens, $newToken)
    {
        foreach ($existingTokens as $token)
        {
            if($token->getCardId() === $newToken->getCardId())
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
            if(($token->getBank()  === $newToken->getBank()) and
                ($token->getAccountKey() === $newToken->getAccountKey()))
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
            if(($token->getWallet()  === $newToken->getWallet()) and
                ($token->getAccountKey() === $newToken->getAccountKey()))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CUSTOMER_WALLET_ALREADY_EXISTS);
            }
        }
    }

    protected function generateToken()
    {
        $token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 14);

        return $token;
    }
}
