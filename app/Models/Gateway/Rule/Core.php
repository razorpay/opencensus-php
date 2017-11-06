<?php

namespace RZP\Models\Gateway\Rule;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Account;
use RZP\Models\Currency\Currency;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $this->trace->info(TraceCode::GATEWAY_RULE_CREATE_REQUEST, $input);

        $rule = (new Entity)->build($input);

        $validatorMethod = $this->getValidatorMethod($rule);

        $this->$validatorMethod($rule);

        $this->repo->saveOrFail($rule);

        return $rule;
    }

    public function update(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_RULE_UPDATE_REQUEST,
            [
                'id'    => $id,
                'input' => $input
            ]);

        $rule = $this->repo->gateway_rule->findOrFailPublic($id);

        $rule->edit($input);

        $validatorMethod = $this->getValidatorMethod($rule);

        $this->$validatorMethod($rule);

        $this->repo->saveOrFail($rule);

        return $rule;
    }

    /**
     * Fetches rules from db as per payment criteria during terminal selection
     *
     * @param  array        $terminals Set of all terminals
     * @param  array        $input     Array containing payment, merchant entities
     * @param  bool         $verbose
     * @return PublicCollection collection of applicable rules
     */
    public function fetchApplicableRulesForPayment(
                        array $terminals,
                        array $input): Base\PublicCollection
    {
        $ruleFetchParams = $this->getRuleFetchParams($terminals, $input);

        $applicableRules = $this->repo
                                ->gateway_rule
                                ->fetchApplicableRulesForPayment($ruleFetchParams);

        if ($input['payment']->isMethodCardOrEmi() === true)
        {
            $iins = (array) $input['payment']->card->getIin();

            $applicableRules = $this->getRulesWithOverLappingIins($iins, $applicableRules);
        }

        return $applicableRules;
    }

    /**
     * For filter rules checks if there is any rule which satisfies same criteria
     * as new rule, and same gateway but opposite filter type in the same group
     * Ror e.g select rule for gateway A and reject rule for gateway A cannot be
     * present in same group
     * @param  Entity $rule Rule entity being created
     */
    protected function validateFilterRule(Entity $rule)
    {
        $matchingRules = $this->getRulesWithMatchingCriteria($rule);

        if ($matchingRules->isNotEmpty() === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'select and reject filter rules for same criteria cannot be present in same group');
        }
    }

    /**
     * For sorter rules checks if the total load across all existing rules
     * matching the criteria defined by current rule is less than the max load value of 100,
     * This is required so that we don't end up having rules during
     * terminal sorting whose total load exceeds the distribution space of 100
     * as we are treating load values as percentages
     *
     * @param  Entity $rule  New rule
     * @param  array  $input Request data
     */
    protected function validateSorterRule(Entity $rule)
    {
        $matchingRules = $this->getRulesWithMatchingCriteria($rule);

        if ($matchingRules->isNotEmpty() === true)
        {
            $this->groupRulesBySpecificityScore($matchingRules, $rule);

            $totalExistingLoad = $matchingRules->sum(Entity::LOAD);

            $totalLoad = $rule->getLoad() + $totalExistingLoad;

            if ($totalLoad > Entity::MAX_LOAD)
            {
                $data = [
                    'total_load' => $totalLoad,
                ];

                throw new Exception\BadRequestValidationFailureException(
                    'Load across all gateway rules must be less than 100 percent',
                    null,
                    $data);
            }
        }
    }

    /**
     * Forms the query param array for fetching rules from db during terminal
     * selction
     *
     * @param  array  $terminals set of all terminals
     * @param  array  $input     payment related input
     * @return array             array of parameters on which to build db query
     */
    protected function getRuleFetchParams(array $terminals, array $input): array
    {
        $payment = $input['payment'];

        $merchant = $input['merchant'];

        $currency = ($payment->getConvertCurrency() === true) ? Currency::INR : $payment->getCurrency();

        $params = [
            Entity::MERCHANT_ID   => [$merchant->getId(), Account::SHARED_ACCOUNT],
            Entity::METHOD        => $payment->getMethod(),
            Entity::INTERNATIONAL => false,
            Entity::CATEGORY2     => $merchant->getCategory2(),
            Entity::CURRENCY      => $currency,
            // Here min_amount and max_amount are both set to payment_amount
            // as the final query will be min_amount <= payment_amount <= max_amount
            Entity::MIN_AMOUNT    => $payment->getAmount(),
            Entity::MAX_AMOUNT    => $payment->getAmount()
        ];

        $this->fillMethodSpecificDetails($params, $payment);

        return $params;
    }

    protected function fillMethodSpecificDetails(array & $params, Payment\Entity $payment)
    {
        $method = $payment->getMethod();
        $card = $payment->card;
        $emiPlan = $payment->emiPlan;
        $bank = $payment->getBank();

        switch ($method)
        {
            case Payment\Method::CARD:
                $params[Entity::METHOD_TYPE]   = $card->getType();
                $params[Entity::NETWORK]       = $card->getNetworkCode();
                $params[Entity::ISSUER]        = $card->getIssuer();
                $params[Entity::INTERNATIONAL] = $payment->isInternational();

                break;

            case Payment\Method::EMI:
                // For certain banks whose emi payments need to go through card terminals
                // we set the method sa card both while fetching applicable rules
                if (in_array($bank, Payment\Gateway::$emiBanksUsingCardTerminals, true) === true)
                {
                    $params[Entity::METHOD] = Payment\Method::CARD;
                }

                $params[Entity::METHOD_TYPE]    = $card->getType();
                $params[Entity::NETWORK]        = $card->getNetworkCode();
                $params[Entity::ISSUER]         = $payment->getBank();
                $params[Entity::EMI_DURATION]   = $emiPlan->getDuration();
                $params[Entity::EMI_SUBVENTION] = $emiPlan->getSubvention();

                break;

            case Payment\Method::NETBANKING:
                $params[Entity::ISSUER] = $payment->getBank();

                break;

            case Payment\Method::WALLET:
                $params[Entity::ISSUER] = $payment->getWallet();

                break;
        }
    }

    /**
     * Fetches rules whose applicability criteria for a particular payment, overlaps
     * with the applicablity criteria for the rule being compared against
     *
     * @param  Entity $rule             Rule entity against which we need to check overlap
     * @return Base\PublicCollection    rules which have matching criteria
     */
    protected function getRulesWithMatchingCriteria(Entity $rule): Base\PublicCollection
    {
        $matchingRules = $this->repo
                              ->gateway_rule
                              ->getRulesWithMatchingCriteria($rule);

        if ($rule->isMethodCardOrEmi() === true)
        {
            $matchingRules = $this->getRulesWithOverLappingIins($rule->getIins(), $matchingRules);
        }

        return $matchingRules;
    }

    protected function groupRulesBySpecificityScore(Base\PublicCollection $rules, Entity $rule)
    {
        $criteria = [];

        foreach (Entity::ATTRIBUTE_SCORES as $attribute => $score)
        {
            $criteria[$attribute] = $rule->getAttribute($attribute);
        }

        foreach ($rules as $rule)
        {
            $rule->calculateSpecificityScoreForCriteria($criteria);
        }
    }

    /**
     * Returns rules which have iins overlapping with given iins.
     * If any existing rule has null iin, that is also considered overlapping
     * with current rule
     *
     * @param  Base\PublicCollection $rules Collection of exisitng rules which can have
     *                                      overlapping ins
     * @return Base\PublicCollection        rules with overlapping iins
     */
    protected function getRulesWithOverLappingIins(array $iins, Base\PublicCollection $rules): Base\PublicCollection
    {
        $rules = $rules->filter(function ($rule) use ($iins)
        {
            if ((empty($iins) === true) or (empty($rule->getIins()) === true))
            {
                return true;
            }

            return count(array_intersect($iins, $rule->getIins())) > 0;
        });

        return $rules;
    }

    protected function getValidatorMethod(Entity $rule)
    {
        return 'validate' . ucfirst($rule->getType()) . 'Rule';
    }
}
