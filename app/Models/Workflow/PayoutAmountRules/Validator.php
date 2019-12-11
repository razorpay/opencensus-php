<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Workflow\Base;

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
            return $a['min_amount'] <=> $b['min_amount'];
        });

        $presentAmount = 0;

        for($index = 0; $index < count($rules); $index++)
        {
            $rule = $rules[$index];

            if($rule['min_amount'] != $presentAmount)
            {
                break;
            }
            if(empty($rule['max_amount']) === false)
            {
                $presentAmount = $rule['max_amount'];
            }
            else
            {
                $index++;
                break;
            }
        }

        if($index !== count($rules))
        {
            throw new BadRequestValidationFailureException(
                'Ranges provided are not continuous and complete'
            );
        }
    }

    // Ensure that every workflow payout amount range is attached to only one workflow
    public function ensureDistinctWorkflowIds($rules)
    {
        if(count($rules) != count(array_unique(array_column($rules, 'workflow_id'))))
        {
            throw new BadRequestValidationFailureException(
                'Each workflow can have only one amount range'
            );
        }
    }
}
