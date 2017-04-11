<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant\Account;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $loadRule = (new Entity)->build($input);

        $existingRule = $this->repo->gateway_load_rule->findExistingRule($input);

        if ($existingRule !== null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_GATEWAY_LOAD_RULE_EXISTS);
        }

        $this->checkIfTotalLoadIsValid($loadRule, $input);

        $this->repo->saveOrFail($loadRule);

        return $loadRule;
    }

    public function fetchApplicableRules(array $terminals , array $input)
    {
        $ruleFetchParams = $this->getRuleFetchParams($terminals, $input);

        $loadRules = $this->repo->gateway_load_rule->fetchApplicableRules($ruleFetchParams);

        return $loadRules;
    }

    protected function getRuleFetchParams(array $terminals, array $input)
    {
        $payment = $input['payment'];

        $merchant = $input['merchant'];

        $card = null;

        if ($payment->hasCard() === true)
        {
            $card = $payment->card;
        }

        $params = [];

        $params[Entity::METHOD] = $payment->getMethod();

        $gateways = $this->getTerminalGateways($terminals);

        $params[Entity::GATEWAY] = $gateways;

        $params[Entity::MERCHANT_ID] = [$merchant->getId(), Account::SHARED_ACCOUNT];

        if ($payment->isInternational() === true)
        {
            $params[Entity::INTERNATIONAL] = true;
        }

        if ($card !== null)
        {
            $params[Entity::CARD_TYPE] = [$card->getType(), Entity::ALL];

            $params[Entity::NETWORK] = [$card->getNetworkCode(), Entity::ALL];

            $params[Entity::ISSUER] = [Entity::ALL];

            if ($card->getIssuer() !== null)
            {
                $params[Entity::ISSUER][] = $card->getIssuer();
            }
        }

        if ($payment->isNetbanking() === true)
        {
            $params[Entity::ISSUER] = [Entity::ALL, $payment->getBank()];
        }

        return $params;
    }

    protected function getTerminalGateways(array $terminals)
    {
        $gateways = array_map(function ($terminal)
        {
            return $terminal->getGateway();
        }, $terminals);

        $gateways = array_values(array_unique($gateways));

        return $gateways;
    }

    protected function checkIfTotalLoadIsValid(Entity $rule, array $input)
    {
        $matchingRules = $this->repo->gateway_load_rule->fetchMatchingRules($input);

        $totalLoad = $matchingRules->reduce(function ($carry, $rule)
        {
            $load = $rule->getLoad();

            return $carry + $load;
        });

        $totalLoad += $rule->getLoad();

        if ($totalLoad > Entity::MAX_LOAD)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MAX_GATEWAY_LOAD_EXCEEDED);
        }
    }
}
