<?php

namespace RZP\Models\Pricing\ChargeCollections;

use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Pricing\Entity;
use RZP\Trace\TraceCode;

class Utils {


    public static function generatePlanAndRuleIds($ruleCount){
        $planId = UniqueIdEntity::generateUniqueId();

        $ruleIds = [];

        for ($i=0; $i<$ruleCount; $i++){
            $ruleIds[] = UniqueIdEntity::generateUniqueId();
        }

        return [
            'planId' => $planId,
            'ruleIds' => $ruleIds,
        ];
    }

    public static function addPlanDetailsToRules($rules, $planAndRuleIds)
    {
        $planId = $planAndRuleIds['planId'];
        $ruleIds = $planAndRuleIds['ruleIds'];

        if (is_array($rules)) {
            foreach ($rules as $index => &$rule) {
                // Set the plan ID and plan name
                $rule[Entity::PLAN_ID] = $planId;
                $rule[Entity::ID] = $ruleIds[$index];
            }
        }

        return $rules;
    }

    public static function extractMethodFromFunction($inputString){
        $lastPos = strrpos($inputString, '\\');

        if ($lastPos !== false) {
            return substr($inputString, $lastPos + 1);
        }

        return $inputString;
    }

}
