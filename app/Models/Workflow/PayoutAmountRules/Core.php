<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Models\Base;
use RZP\Models\Workflow;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Workflow\Entity as WorkflowEntity;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    /**
     * This is the time for which payments will be locked via acquireMultiple
     * After this time, the keys will be released
     */
    const DEFAULT_LOCK_TIME = 180; // 3 Minutes

    /**
     * This is used for naming the redis lock key.
     * It's named as {payment_id}_verify.
     * We do not use the payment_id directly because
     * it's already being used in the core flows of refund and capture.
     */
    const KEY_SUFFIX = '_payout_workflow';

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function fetchWorkflowForMerchantIfDefined(int $amount, Merchant\Entity $merchant)
    {
        $rules = $this->repo->workflow_payout_amount_rules->fetchWorkflowRulesForMerchant($merchant->getId());

        //
        // Filter the rules to match exactly one and return if found
        // If no rules were defined, or no match was found, return null
        //
        /** @var Entity|null $workflowRule */
        $workflowRule = $this->filterRulesByAmount($rules, $amount);

        if ($workflowRule !== null)
        {
            return $workflowRule->workflow;
        }

        return null;
    }

    public function fetchPayoutAmountRuleForMerchantIfDefined(int $amount, Merchant\Entity $merchant)
    {
        $rules = $this->repo->workflow_payout_amount_rules->fetchWorkflowRulesForMerchant($merchant->getId());

        //
        // Filter the rules to match exactly one and return if found
        // If no rules were defined, or no match was found, return null
        //
        /** @var Entity|null $workflowRule */
        $workflowRule = $this->filterRulesByAmount($rules, $amount);

        return $workflowRule;
    }

    protected function filterRulesByAmount(Base\PublicCollection $rules, int $amount)
    {
        $evaluatedRule = null;

        /** @var Entity $rule */
        foreach ($rules as $rule)
        {
            $minAmount = $rule->getMinAmount();
            $maxAmount = $rule->getMaxAmount();

            if (($minAmount === null) and
                ($maxAmount === null))
            {
                $evaluatedRule = $rule;

                break;
            }

            $maxAmount = $maxAmount ?: PHP_INT_MAX;

            if (($minAmount < $amount) and ($maxAmount >= $amount))
            {
                $evaluatedRule = $rule;

                break;
            }
        }

        return $evaluatedRule;
    }

    /**
     * @param array $rules
     * @param Merchant\Entity $merchant
     *
     * @return Base\PublicCollection
     */
    public function create(array $rules, Merchant\Entity $merchant)
    {
        // Insert all rules together into database
        $payoutAmountRules = $this->repo->transaction(function() use ($rules, $merchant)
        {
            $payoutAmountRules = new Base\PublicCollection();

            foreach ($rules as $rule)
            {
                $workflow = null;

                if (empty($rule[Entity::WORKFLOW_ID]) === false)
                {
                    $workflow = $this->repo->workflow->findByPublicId($rule[Entity::WORKFLOW_ID]);
                }

                unset($rule[Entity::WORKFLOW_ID]);

                $payoutAmountRule = new Entity();

                $payoutAmountRule->merchant()->associate($merchant);

                $payoutAmountRule->workflow()->associate($workflow);

                $payoutAmountRule->build($rule);

                $payoutAmountRules->push($payoutAmountRule);

                $this->repo->saveOrFail($payoutAmountRule);
            }

            return $payoutAmountRules;
        });

        return $payoutAmountRules;
    }

    /**
     * @param array $updatedWorkflows
     * @param Merchant\Entity $merchant
     *
     * @return Base\PublicCollection
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     */
    public function edit(array $updatedWorkflows, Merchant\Entity $merchant)
    {
        $merchantId = $this->merchant->getId();

        $amountRules =  $this->repo
            ->workflow_payout_amount_rules
            ->fetch([], $merchantId);

        $payoutAmountRules = $this->repo->transaction(function() use ($amountRules, $updatedWorkflows, $merchant)
        {
            $payoutAmountRanges = [];

            $workflowCore = new Workflow\Core;

            $payoutAmountRules = new Base\PublicCollection();

            $lockIds = [];

            foreach ($amountRules as $amountRule)
            {
                $lockIds[] = $amountRule[Entity::ID];
            }

            $this->mutex->acquireMultiple(
                $lockIds, self::DEFAULT_LOCK_TIME, self::KEY_SUFFIX);

            foreach ($amountRules as $amountRule)
            {
                if ($amountRule[Entity::WORKFLOW_ID] !== null)
                {
                    $workflowToBeDeleted = $this->repo->workflow->findOrFailPublic($amountRule[Entity::WORKFLOW_ID]);

                    $workflowCore->delete($workflowToBeDeleted);
                }

                $this->repo->deleteOrFail($amountRule);
            }

            foreach ($updatedWorkflows as $updatedWorkflow)
            {
                $newPayoutAmountRule = $updatedWorkflow[Entity::PAYOUTAMOUNTRULES];

                unset($updatedWorkflow[Entity::PAYOUTAMOUNTRULES]);

                $workflow = null;

                if (empty($updatedWorkflow) === false)
                {
                    Org\Entity::verifyIdAndStripSign($updatedWorkflow[WorkflowEntity::ORG_ID]);

                    Permission\Entity::verifyIdAndStripSignMultiple($updatedWorkflow[WorkflowEntity::PERMISSIONS]);

                    $workflow = $workflowCore->create($updatedWorkflow, $this->merchant);
                }

                $payoutAmountRule = new Entity();

                $payoutAmountRule->merchant()->associate($merchant);

                $payoutAmountRule->workflow()->associate($workflow);

                $payoutAmountRule->build($newPayoutAmountRule[0]);

                $payoutAmountRules->push($payoutAmountRule);

                $this->repo->saveOrFail($payoutAmountRule);
            }

            $this->mutex->releaseMultiple($lockIds, self::KEY_SUFFIX);

            return $payoutAmountRules;
        });

        return $payoutAmountRules;
    }
}
