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

    public function checkIfWorkflowAlreadyCreated($merchantId)
    {
        $repo = new Repository();

        if(count($repo->fetchWorkflowRulesForMerchant($merchantId)->toArray()) != 0 )
        {
            throw new BadRequestValidationFailureException(
                'Payout amount rules already present'
            );
        }
    }

    public function checkIfAllMerchantIdsAreSame($rules) : string
    {
        $repo = new \RZP\Models\Workflow\Repository();

        $identicalId = nullOrEmptyString();

        foreach ($rules as $rule)
        {
            $merchantIds = $repo->fetchMerchantIdsFromWorkflow($rule['workflow_id'])->toArray();

            if(count($merchantIds) != 1)
            {
                throw new BadRequestValidationFailureException(
                    'Invalid workflow id'
                );
            }

            $merchantId = $merchantIds[0];

            if($identicalId == nullOrEmptyString())
            {
                $identicalId = $merchantId;
            }

            if($identicalId != $merchantId)
            {
                throw new BadRequestValidationFailureException(
                    'Changing workflows of multiple merchants not allowed'
                );
            }
        }

        return $identicalId;
    }

    public function checkForValidAmountRanges($rules)
    {

        usort($rules, function($a, $b) {
            return $a['min_amount'] <=> $b['min_amount'];
        });

        $presentAmount = 0;

        foreach($rules as $rule)
        {
            if($rule['min_amount'] != $presentAmount)
            {
                throw new BadRequestValidationFailureException(
                    'Ranges specified in workflows are overlapping'
                );
            }
            if($rule['max_amount'] && $rule['max_amount'] != PHP_INT_MAX)
            {
                $presentAmount = $rule['max_amount'] + 1;
            }
            else
            {
                return;
            }
        }

        throw new BadRequestValidationFailureException(
            'Provided ranges are not complete'
        );
    }
}
