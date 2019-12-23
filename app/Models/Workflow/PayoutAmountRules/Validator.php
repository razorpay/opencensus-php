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
        Entity::WORKFLOW_ID     => 'required|string|size:14',
        Entity::MIN_AMOUNT      => 'required|integer|min:0',
        Entity::MAX_AMOUNT      => 'sometimes|integer|nullable',
    ];

    public function checkForValidAmountRanges($rules)
    {
        usort($rules, function($a, $b) {
            return $a[Entity::MIN_AMOUNT] <=> $b[Entity::MIN_AMOUNT];
        });

        $presentAmount = 0;

        for ($index = 0; $index < count($rules); $index++)
        {
            $rule = $rules[$index];

            if ($rule[Entity::MIN_AMOUNT] != $presentAmount)
            {
                break;
            }
            if (empty($rule[Entity::MAX_AMOUNT]) === false)
            {
                $presentAmount = $rule[Entity::MAX_AMOUNT];
            }
            else
            {
                $index++;
                break;
            }
        }

        if ($index !== count($rules))
        {
            throw new BadRequestValidationFailureException(
                'Ranges provided are not continuous and complete'
            );
        }
    }

    // Ensure that every workflow payout amount range is attached to only one workflow
    public function ensureDistinctWorkflowIds($rules)
    {
        if ( count($rules) != count(array_unique(array_column($rules, 'workflow_id'))))
        {
            throw new BadRequestValidationFailureException(
                'Each workflow can have only one amount range'
            );
        }
    }
}
