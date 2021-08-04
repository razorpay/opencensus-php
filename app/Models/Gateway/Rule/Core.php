<?php

namespace RZP\Models\Gateway\Rule;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Trace\TraceCode;
use RZP\Services\SmartRouting;
use RZP\Models\Currency\Currency;
use RZP\Models\Admin\Org\Entity as Org;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $this->trace->info(TraceCode::GATEWAY_RULE_CREATE_REQUEST, $input);

        $rule = (new Entity)->build($input);

        if (empty($input[Entity::MERCHANT_ID]) === false)
        {
            $merchantId = $input[Entity::MERCHANT_ID];

            $orgId = $input[Entity::ORG_ID];

            $merchant = $this->repo->merchant->findByIdAndOrgId($merchantId, $orgId);

            $rule->merchant()->associate($merchant);
        }

        $validatorMethod = $this->getValidatorMethod($rule);

        $matchingRules = $this->getRulesWithMatchingRuleCriteria($rule);

        $this->$validatorMethod($rule, $matchingRules);

        try
        {
            $this->repo->transaction(function () use ($rule)
            {
                $this->repo->saveOrFail($rule);

                $response = $this->app->smartRouting->createGatewayRule($rule->toArray());

                if ($response === null)
                {
                    throw new Exception\RuntimeException('Router rule create failed', $rule->toArray());
                }
            });
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::GATEWAY_RULE_CREATE_REQUEST);

            throw $e;
        }

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

        $matchingRules = $this->getRulesWithMatchingRuleCriteria($rule);

        $this->$validatorMethod($rule, $matchingRules);

        try
        {
            $this->repo->transaction(function () use ($rule)
            {
                $this->repo->saveOrFail($rule);

                $response = $this->app->smartRouting->updateGatewayRule($rule->toArray());

                if ($response === null)
                {
                    throw new Exception\RuntimeException('Router rule update failed', $rule->toArray());
                }

            });
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::GATEWAY_RULE_UPDATE_REQUEST);

            throw $e;
        }

        return $rule;
    }

    /**
     * Fetches rules from db as per payment criteria during terminal selection
     *
     * @param  array        $input      Array containing payment, merchant entities
     *
     * @return Base\PublicCollection    collection of applicable rules
     */
    public function fetchApplicableRulesForPayment(array $input): Base\PublicCollection
    {
        $searchCriteria = $this->getRuleSearchCriteriaForPayment($input);

        $applicableRules = $this->repo
                                ->gateway_rule
                                ->fetchRulesForSearchCriteria($searchCriteria);

        if (($input['payment']->isMethodCardOrEmi() === true) and
            ($input['payment']->isGooglePayCard() === false))
        {
            $iins = (array) $input['payment']->card->getIin();

            $applicableRules = $this->getRulesWithOverLappingIins($iins, $applicableRules);
        }

        return $applicableRules;
    }

    public function fetchApplicableAuthenticationRulesForPayment(array $input): Base\PublicCollection
    {
        $payment = $input['payment'];

        $merchant = $input['merchant'];

        $validAuths = $input['auths'];

        $searchCriteria = [
            Entity::METHOD        => $payment->getMethod(),
            Entity::MERCHANT_ID   => $merchant->getId(),
            Entity::GATEWAY       => $payment->terminal->getGateway(),
            Entity::AUTH_TYPE     => $validAuths,
            Entity::STEP          => Entity::AUTHENTICATION,
        ];

        if ($payment->isGooglePayCard() === false)
        {
            $card = $payment->card;

            $searchCriteria[Entity::NETWORK] = $card->getNetworkCode();
            $searchCriteria[Entity::ISSUER]  = $card->getIssuer();
        }

        $this->trace->info(TraceCode::AUTH_RULES_SEARCH_CRITERIA, $searchCriteria);

        $applicableRules = $this->repo
                                ->gateway_rule
                                ->fetchAuthenticationRulesForSearchCriteria($searchCriteria);

        return $applicableRules;
    }

    /**
     * For filter rules checks if there is any rule which satisfies same criteria
     * as new rule, and same gateway but opposite filter type in the same group
     * Ror e.g select rule for gateway A and reject rule for gateway A cannot be
     * present in same group
     *
     * @param  Entity                   $rule           Rule entity being created
     * @param  Base\PublicCollection    $matchingRules  Set of matching rules for the given criteria
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateFilterRule(Entity $rule, Base\PublicCollection $matchingRules)
    {
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
     * @param  Entity                   $rule           New rule
     * @param  Base\PublicCollection    $matchingRules  Set of matching rules for the given criteria
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateSorterRule(Entity $rule, Base\PublicCollection $matchingRules)
    {
        if ($matchingRules->isNotEmpty() === true)
        {
            $ruleSpecificityScore = $rule->calculateSpecificityScore();

            $matchingRules = $matchingRules->getRulesWithSpecificityScore($ruleSpecificityScore);

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
     * Forms the search criteria to be used for fetching relevant rules during a payment
     *
     * @param  array  $input     payment related input
     * @return array             array of parameters on which to build db query
     */
    protected function getRuleSearchCriteriaForPayment(array $input): array
    {
        $payment = $input['payment'];

        $merchant = (empty($input['charge_account_merchant']) === false) ? $input['charge_account_merchant'] : $input['merchant'];

        $currency = ($payment->getConvertCurrency() === true) ? Currency::INR : $payment->getGatewayCurrency();

        $params = [
            Entity::MERCHANT_ID   => $merchant->getId(),
            Entity::ORG_ID        => $merchant->getOrgId(),
            Entity::METHOD        => $input['payment']['method'],
            Entity::INTERNATIONAL => false,
            Entity::CATEGORY      => $merchant->getCategory(),
            Entity::CATEGORY2     => $merchant->getCategory2(),
            Entity::CURRENCY      => $currency,
            // Here min_amount and max_amount are both set to payment_amount
            // as the final query will be min_amount <= payment_amount <= max_amount
            Entity::MIN_AMOUNT    => $payment->getAmount(),
            Entity::MAX_AMOUNT    => $payment->getAmount(),
            Entity::STEP          => Entity::AUTHORIZATION,
        ];

        $this->fillMethodSpecificDetails($params, $payment);

        return $params;
    }

    protected function fillMethodSpecificDetails(array & $params, Payment\Entity $payment)
    {
        $method = $payment->getMethod();

        switch ($method)
        {
            case Payment\Method::CARD:
                $params[Entity::INTERNATIONAL]  = $payment->isInternational();
                $params[Entity::RECURRING]      = $payment->isRecurring();
                $params[Entity::RECURRING_TYPE] = $payment->getRecurringType();

                if ($payment->isGooglePayCard() === false)
                {
                    $card = $payment->card;

                    $params[Entity::METHOD_TYPE]    = $card->getType();
                    $params[Entity::NETWORK]        = $card->getNetworkCode();
                    $params[Entity::ISSUER]         = $card->getIssuer();
                    $params[Entity::CARD_CATEGORY]  = $card->getCategory();
                    $params[Entity::METHOD_SUBTYPE] = $card->getSubType();
                }

                break;

            case Payment\Method::EMI:

                $emiPlan = $payment->emiPlan;

                $card = $payment->card;

                $bank = $payment->getBank();

                // For certain banks whose emi payments need to go through card terminals
                // we set the method sa card both while fetching applicable rules
                if (in_array($bank, Payment\Gateway::$emiBanksUsingCardTerminals, true) === true)
                {
                    $params[Entity::METHOD] = [Payment\Method::CARD, Payment\Method::EMI];
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

            case Payment\Method::UPI:
                $params[Entity::ISSUER] = $payment->getBankCodeFromVpa();

                break;
        }
    }

    /**
     * Fetches rules whose applicability criteria is the same as that of the rule passed
     *
     * @param  Entity $rule             Rule entity against which we need to check overlap
     *
     * @return Base\PublicCollection    rules which have matching criteria
     */
    protected function getRulesWithMatchingRuleCriteria(Entity $rule): Base\PublicCollection
    {
        $searchCriteria = $rule->getSearchCriteria();

        $matchingRules = $this->getMatchingRules($rule, $searchCriteria);

        if ($rule->isMethodCardOrEmi() === true)
        {
            $matchingRules = $this->getRulesWithOverLappingIins($rule->getIins(), $matchingRules);
        }

        return $matchingRules;
    }

    protected function getMatchingRules(Entity $rule, array $searchCriteria): Base\PublicCollection
    {
        if ($rule->getStep() === Entity::AUTHENTICATION)
        {
            return $this->repo
                        ->gateway_rule
                        ->fetchAuthenticationRulesForSearchCriteria($searchCriteria);
        }

        return $this->repo
                    ->gateway_rule
                    ->fetchRulesForSearchCriteria($searchCriteria);
    }

    /**
     * Returns rules which have iins overlapping with given iins.
     * If any existing rule has null iin, that is also considered overlapping
     * with current rule
     *
     * @param  array                 $iins  iins to check for overlap
     * @param  Base\PublicCollection $rules Collection of exisitng rules which can have
     *                                      overlapping ins
     *
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
