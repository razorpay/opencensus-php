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

        $this->validateNewRule($loadRule, $input);

        $this->repo->saveOrFail($loadRule);

        return $loadRule;
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

        // Checks if the edited load value will cause total load across similar
        // rules to exceed max load value of 100
        $this->checkIfTotalLoadIsValid($loadRule);

        $this->repo->saveOrFail($loadRule);

        return $loadRule;
    }

    /**
     * Fetches rules from db as per payment criteria during terminal selection
     *
     * @param  array        $terminals Set of all terminals
     * @param  array        $input     Array containing payment, merchant enttties
     * @param  bool         $verbose
     * @return PublicCollection collection of applicable rules
     */
    public function fetchApplicableRules(array $terminals, array $input, bool $verbose = false): Base\PublicCollection
    {
        $ruleFetchParams = $this->getRuleFetchParams($terminals, $input);

        $applicableRules = $this->repo->gateway_load_rule->fetchApplicableRules($ruleFetchParams);

        if ($verbose === true)
        {
            $this->trace->info(
                TraceCode::GATEWAY_LOAD_RULES_POST_FILTER,
                $applicableRules->pluck(Entity::ID)->toArray());
        }

        return $applicableRules;
    }

    /**
     * Checks if the new rule is not a duplicate and that the total load across
     * rules with criteria matching the current rule's criteria does not exceed
     * 100 which is the distribution size limit
     *
     * @param  Entity $rule  New rule built from input
     * @param  array  $input Request data
     */
    protected function validateNewRule(Entity $rule, array $input)
    {
        // Unsetting load here as it is not required to check for conflicting rules
        unset($input[Entity::LOAD]);

        // Checks if there is already a rule defined with the exact same criteria
        $existingRulesCount = $this->repo->gateway_load_rule->fetch($input)->count();

        if ($existingRulesCount > 0)
        {
            throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_GATEWAY_LOAD_RULE_EXISTS);
        }

        // Checks that the total load across all rules with similar criteria doesn't exceed
        // max load.
        $this->checkIfTotalLoadIsValid($rule);
    }

    /**
     * Checks if the total load across all existing rules matching the criteria
     * defined by current rule is less than the max load value of 100. This is
     * required so that we don't end up having rules during terminal sorting whose
     * total load does not exceed the distribution space of 100 as we are treating
     * load values as percentages
     *
     * @param  Entity $rule  New rule
     * @param  array  $input Request data
     */
    protected function checkIfTotalLoadIsValid(Entity $rule)
    {
        $totalLoadForSimilarRules = $this->repo
                                         ->gateway_load_rule
                                         ->getTotalLoadForSimilarRules($rule);

        $totalLoad = $rule->getLoad() + $totalLoadForSimilarRules;

        if ($totalLoad > Entity::MAX_LOAD)
        {
            $data = [
                'total_load' => $totalLoad,
            ];

            $this->trace->info(
                    TraceCode::GATEWAY_LOAD_RULE_CONFLCT,
                    $data);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TOTAL_LOAD_EXCEEDS_MAX_LOAD,
                null,
                $data);
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

        $gateways = $this->getTerminalGateways($terminals);

        $params = [
            Entity::MERCHANT_ID   => [$merchant->getId(), Account::SHARED_ACCOUNT],
            Entity::GATEWAY       => $gateways,
            Entity::METHOD        => $payment->getMethod(),
            Entity::INTERNATIONAL => false,
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

        $params[Entity::INTERNATIONAL] = $payment->isInternational();
    }

    protected function getTerminalGateways(array $terminals): array
    {
        $gateways = array_pluck($terminals, 'gateway');

        $gateways = array_values(array_unique($gateways));

        return $gateways;
    }
}
