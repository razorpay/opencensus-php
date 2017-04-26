<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $this->trace->info(TraceCode::GATEWAY_LOAD_RULE_CREATE_REQUEST, $input);

        $loadRule = (new Entity)->build($input);

        $existingRule = $this->repo->gateway_load_rule->findExistingRule($input);

        if ($existingRule !== null)
        {
            throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_GATEWAY_LOAD_RULE_EXISTS,
                        null,
                        [
                            'existing_rule_id' => $existingRule->getId(),
                        ]);
        }

        // Checks if there are any potential conflicting rules and throws exception
        // if sum of all loads of such conflicting cases is above 10000
        $this->checkIfTotalLoadIsValid($loadRule, $input);

        $this->repo->saveOrFail($loadRule);

        return $loadRule;
    }

    public function fetchApplicableRules(array $terminals, array $input, bool $verbose = false)
    {
        $ruleFetchParams = $this->getRuleFetchParams($terminals, $input);

        $rules = $this->repo->gateway_load_rule->fetchApplicableRules($ruleFetchParams);

        $applicableRules = (new Filter($input))->filter($rules);

        if ($verbose === true)
        {
            $this->trace->info(
                TraceCode::GATEWAY_LOAD_RULES_POST_FILTER,
                $applicableRules->pluck(Entity::ID));
        }

        return $applicableRules;
    }

    public function update(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_LOAD_RULE_UPDATE_REQUEST,
            [
                'id'    => $id,
                'input' => $input
            ]);

        $loadRule = $this->repo->gateway_load_rule->findOrFailPublic($id);

        $loadRule->edit($input);

        $this->checkIfTotalLoadIsValid($loadRule, $input);

        $this->repo->saveOrFail($loadRule);

        return $loadRule;
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
    public function matchTerminalsToRule(array $terminals, Base\PublicCollection $rules, bool $verbose = true)
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

        $this->traceRuleToTerminalsMap($map, $verbose);

       return $map;
    }

    protected function getRuleFetchParams(array $terminals, array $input)
    {
        $payment = $input['payment'];

        $merchant = $input['merchant'];

        $gateways = $this->getTerminalGateways($terminals);

        $params = [
            Entity::MERCHANT_ID   => [$merchant->getId(), Account::SHARED_ACCOUNT],
            Entity::GATEWAY       => $gateways,
            Entity::METHOD        => $payment->getMethod(),
            Entity::INTERNATIONAL => $payment->isInternational(),
        ];

        $method = $payment->getMethod();

        // We include null in the list of possible values here for issuer, network etc
        // as we also want to fetch rules where thes attributes are set to null, as it
        // has a meaning of any/all.
        switch ($method)
        {
            case Payment\Method::CARD:
            case Payment\Method::EMI:

                $this->fillCardDetails($params, $payment);

                break;

            case Payment\Method::NETBANKING:

                $params[Entity::ISSUER] = $payment->getBank();

                break;

            case Payment\Method::WALLET:

                $params[Entity::ISSUER] = $payment->getWallet();

                break;

            default:
                // Not implemented for other methods as of now
                break;
        }

        return $params;
    }

    protected function fillCardDetails(array & $params, Payment\Entity $payment)
    {
        $card = $payment->card;

        $params[Entity::CARD_TYPE] = $card->getType();

        $params[Entity::NETWORK] = $card->getNetworkCode();

        $params[Entity::ISSUER] = $card->getIssuer();
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

    public function checkAndBalanceLoads(Base\PublicCollection $rules)
    {
        $totalLoad = $rules->reduce(function ($carry, $rule)
        {
            $load = $rule->getLoad();

            return $carry + $load;
        });

        if ($totalLoad > Entity::MAX_LOAD)
        {
            foreach ($rules as $rule)
            {
                $normalizedLoad = $rule->getNormalizedLoad($totalLoad);

                $rule->setLoad($normalizedLoad);
            }
        }
    }

    protected function checkIfTotalLoadIsValid(Entity $rule, array $input)
    {
        $conflictingRules = $this->repo->gateway_load_rule->fetchConflictingRules($input);

        // If the rule already exists (edit case) then we remove it from the set
        // of conflicting rules
        if ($rule->exists === true)
        {
            $conflictingRules = $conflictingRules->filter(function ($item) use ($rule)
            {
                return ($item->getId() !== $rule->getId());
            });
        }

        $totalLoad = $conflictingRules->reduce(function ($carry, $rule)
        {
            $load = $rule->getLoad();

            return $carry + $load;
        });

        $totalLoad += $rule->getLoad();

        if ($totalLoad > Entity::MAX_LOAD)
        {

            $conflictingRuleIds = $conflictingRules->map(function ($rule)
            {
                return $rule->getId();
            });

            $data = [
                'conflicting_rules' => $conflictingRuleIds,
                'total_load'        => $totalLoad,
            ];

            $this->trace->info(
                    TraceCode::GATEWAY_LOAD_RULE_CONFLCT,
                    $data);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MAX_GATEWAY_LOAD_EXCEEDED,
                null,
                $data);
        }
    }

    protected function traceRuleToTerminalsMap(array $map, bool $verbose)
    {
        if ($verbose === true)
        {
            $traceData = [];

            foreach ($map as $ruleId => $terminals)
            {
                $terminalIds = [];

                foreach ($terminals as $terminal)
                {
                    $terminalIds[] = $terminal->getId();
                }

                $traceData[$ruleId] = $terminalIds;
            }

            $this->trace->info(TraceCode::GATEWAY_LOAD_RULES_TO_TERMINALS_MAP, $traceData);
        }
    }
}
