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

    protected static $fetchMerchantIdRules = [
        'count'                 => 'required|integer|min:0',
        'skip'                  => 'required|integer|min:0',
        Entity::MERCHANT_ID     => 'sometimes|string|size:14',
    ];

    public function checkForValidAmountRanges($rules)
    {
        usort($rules, function($a, $b) {
            return $a[Entity::MIN_AMOUNT] <=> $b[Entity::MIN_AMOUNT];
        });

        $currentMinAmount = 0;

        for ($index = 0; $index < count($rules); $index++)
        {
            $rule = $rules[$index];

            if ($rule[Entity::MIN_AMOUNT] != $currentMinAmount)
            {
                break;
            }

            if (empty($rule[Entity::MAX_AMOUNT]) === false)
            {
                $currentMinAmount = $rule[Entity::MAX_AMOUNT];
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

    /**
     * Ensure that every workflow payout amount range is attached to only one workflow
     *
     * @param $rules
     */
    public function ensureDistinctWorkflowIds($rules)
    {
        $uniqueWorkflowIds = array_unique(array_column($rules, Entity::WORKFLOW_ID));

        if (count($rules) != count($uniqueWorkflowIds))
        {
            throw new BadRequestValidationFailureException(
                'Each workflow can have only one amount range'
            );
        }
    }
}
