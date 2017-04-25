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

        // Checks if there are any potential conflicting rules and throws exception
        // if sum of all loads of such conflicting cases is above 10000
        $this->checkIfTotalLoadIsValid($loadRule, $input);

        $this->repo->saveOrFail($loadRule);

        return $loadRule;
    }

    public function fetchApplicableRules(array $terminals, array $input)
    {
        $merchantId = $input['merchant']->getId();

        $ruleFetchParams = $this->getRuleFetchParams($terminals, $input);

        $rules = $this->repo->gateway_load_rule->fetchApplicableRules($ruleFetchParams);

        // We check if any merchant specific rules are present. If present we only deal with
        // those rules as our rule set and discard any other rules
        $merchantSpecificRules = $this->getMerchantSpecificRules($loadRules, $merchantId);

        if ($merchantSpecificRules->isEmpty() === false)
        {
            $loadRules = $merchantSpecificRules;
        }

        // We now filter rules based on payment criteria to get collection of
        // applicable rules for the particular payment
        $applicableRules = (new Filter($rules, $input))->filter();

        return $applicableRules;
    }

    /**
     * Matches terminals to a rule based on comparing terminal attributes to terminal
     * related rule attributes. Returns a map, mapping rule id's to terminals like
     * [
     *     <rule_id> => [<terminal_ids>]
     * ]
     *
     * @param  Base\PublicCollection $terminals collection of available terminals
     * @param  Base\PublicCollection $rules     collection of applicable rules
     * @return array                            map of rule_id => terminals
     */
    public function matchTerminalsToRule(Base\PublicCollection $terminals, Base\PublicCollection $rules)
    {
       $map = [];

       foreach ($rules as $rule)
       {
            foreach ($terminals as $terminal)
            {
                if ($rule->matches($terminal) === true)
                {
                    $map[$rule->getId()][] = $terminal;
                }
            }
       }

       return $map;
    }

    protected function getMerchantSpecificRules(Base\PublicCollection $rules, string $merchantId)
    {
        $merchantSpecificRules = $rules->filter(function ($rule) use ($merchantId)
        {
            return ($rule->getMerchantId() === $merchantId);
        });

        return $merchantSpecificRules;
    }

    protected function getRuleFetchParams(array $terminals, array $input)
    {
        $payment = $input['payment'];

        $merchant = $input['merchant'];

        $gateways = $this->getTerminalGateways($terminals);

        $card = null;

        if ($payment->hasCard() === true)
        {
            $card = $payment->card;
        }

        $params = [
            Entity::MERCHANT_ID   => [$merchant->getId(), Account::SHARED_ACCOUNT],
            Entity::GATEWAY       => $gateways,
            Entity::METHOD        => $payment->getMethod(),
            Entity::INTERNATIONAL => $payment->isInternational(),
        ];

        // We include null in the list of possible values here for issuer, network etc
        // as we also want to fetch rules where thes attributes are set to null, as it
        // has a meaning of any/all.
        if ($card !== null)
        {
            $params[Entity::CARD_TYPE] = [$card->getType(), null];

            $params[Entity::NETWORK] = [$card->getNetworkCode(), null];

            $params[Entity::ISSUER] = [$card->getIssuer(), null];
        }

        if ($payment->isNetbanking() === true)
        {
            $params[Entity::ISSUER] = [$payment->getBank(), null];
        }

        // TODO: add cases for handling other methods like wallet, upi etc

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
        $conflictingRules = $this->repo->gateway_load_rule->fetchConflictingRules($input);

        $totalLoad = $conflictingRules->reduce(function ($carry, $rule)
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
