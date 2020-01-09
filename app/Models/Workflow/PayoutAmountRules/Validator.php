<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID     => 'sometimes|string|size:14',
        Entity::WORKFLOW_ID     => 'sometimes|string|size:14|nullable',
        Entity::MIN_AMOUNT      => 'required|integer|min:0',
        Entity::MAX_AMOUNT      => 'sometimes|integer|nullable',
    ];

    protected static $createRulesMultipleRules = [
        Entity::RULES                               => 'required|array|custom',
        Entity::RULES . '.*.' . Entity::WORKFLOW_ID => 'present|string|nullable',
        Entity::RULES . '.*.' . Entity::MIN_AMOUNT  => 'required|integer|min:0',
        Entity::RULES . '.*.' . Entity::MAX_AMOUNT  => 'present|integer|nullable',
    ];

    protected static $fetchMerchantIdRules = [
        'count'                 => 'sometimes|integer|min:0',
        'skip'                  => 'sometimes|integer|min:0',
        Entity::MERCHANT_ID     => 'sometimes|string|size:14',
    ];

    public function validateRules($attribute, $rules)
    {
        usort($rules, function($a, $b)
        {
            return $a[Entity::MIN_AMOUNT] <=> $b[Entity::MIN_AMOUNT];
        });

        $lastMaxAmount = 0;

        foreach ($rules as $rule)
        {
            // A rule is invalid is there are gaps in the ranges eg: [0-100] and [200-300]
            // Also if the min_amount >= max_amount
            if (($rule[Entity::MIN_AMOUNT] !== $lastMaxAmount) or
                (($rule[Entity::MAX_AMOUNT] !== null) and
                 ($rule[Entity::MIN_AMOUNT] >= $rule[Entity::MAX_AMOUNT])))
            {
                throw new BadRequestValidationFailureException(
                    'Ranges provided are not continuous and complete',
                    Entity::RULES,
                    $rules);
            }

            $lastMaxAmount = $rule[Entity::MAX_AMOUNT];
        }

        $this->ensureDistinctWorkflowIds($rules);
    }

    /**
     * Ensure that every workflow payout amount range is attached to only one workflow
     *
     * @param $rules
     */
    protected function ensureDistinctWorkflowIds(array $rules)
    {
        $workflowIds = array_filter(array_column($rules, Entity::WORKFLOW_ID));

        if (count($workflowIds) != count(array_unique($workflowIds)))
        {
            throw new BadRequestValidationFailureException(
                'Each workflow can have only one amount range',
                Entity::WORKFLOW_ID,
                $workflowIds
            );
        }
    }
}
