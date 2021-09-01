<?php

namespace RZP\Models\Payout;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Exception;

class WorkflowMigration
{
    protected $merchantId;

    protected $merchant;

    public function convertOldSummaryIntoNew(Merchant\Entity $merchant, bool $skipFetchFromWfs, bool $returnOld)
    {
        $this->merchant = $merchant;

        $this->merchantId = $merchant['id'];

        $oldConfigByAmountRules = (new Service())->getWorkflowSummary($skipFetchFromWfs);
        if ($returnOld === true)
        {
            return $oldConfigByAmountRules;
        }

        return $this->getNewConfigFromOldAmountRules($oldConfigByAmountRules);
    }

    protected function getNewConfigFromOldAmountRules($oldConfigByAmountRules)
    {
        $newConfig = $this->getNewConfigPartial();
        $template = & $newConfig['config']['template'];
        $startStateTransitions = & $template['state_transitions']['START_STATE'];

        foreach ($oldConfigByAmountRules as $rangeKey => $oldConfigByAmountRule)
        {
            // use min as 1, not 0
            $minAmount = empty($oldConfigByAmountRule["min_amount"]) === true ? 1 : $oldConfigByAmountRule["min_amount"];
            // use max as 20cr if not defined
            $maxAmount = empty($oldConfigByAmountRule["max_amount"]) === true ? 20000000000 : $oldConfigByAmountRule["max_amount"];

            $parentNextStates = ['END_STATE'];
            if (count($oldConfigByAmountRule['steps']) > 0)
            {
                $nextStep = $oldConfigByAmountRule['steps'][0];
                $nextChildStateName = "{$nextStep['roles'][0]['name']}_{$rangeKey}_0_Approval";
                $parentNextStates = [$nextChildStateName];
            }

            $parentStateName = "{$minAmount}-{$maxAmount}_workflow";
            $startStateTransitions['next_states'][] = $parentStateName;

            $childStateData = [
                "name" => $parentStateName,
                "group_name" => "0",
                "type" => "between",
                "rules" => [
                    "key" => "amount",
                    "min" => $minAmount,
                    "max" => $maxAmount,
                ]
            ];

            $template['states_data'][$parentStateName] = $childStateData;
            $parentStateTransition = [
                "current_state" => $parentStateName,
                "next_states" => $parentNextStates,
            ];
            $template['state_transitions'][$parentStateName] = $parentStateTransition;

            $this->getStateDataFromRules( $newConfig['config']['template'], $oldConfigByAmountRule['steps'], $rangeKey);
        }

        return $newConfig;
    }

    protected function getStateDataFromRules(& $template, $oldConfigByAmountRuleSteps, $rangeKey)
    {
        foreach ($oldConfigByAmountRuleSteps as $key => $step)
        {
            $roles = $step['roles'];

            //TODO::Handelling more than one approver roles at a level is tricky while migrating the old workflow migrations to new WFS. Currently doing it manually.

            if (count($roles) > 1)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MORE_THAN_ONE_ROLE_FOR_A_LEVEL);
            }

            $role = $roles[0];
            $childStateName = "{$role['name']}_{$rangeKey}_{$key}_Approval";
            $parentNextStates[] = $childStateName;

            $nextStates = ['END_STATE'];
            if ($key < count($oldConfigByAmountRuleSteps) - 1)
            {
                $nextKeyCount = $key+1;
                $nextStep = $oldConfigByAmountRuleSteps[$nextKeyCount];
                $nextChildStateName = "{$nextStep['roles'][0]['name']}_{$rangeKey}_{$nextKeyCount}_Approval";
                $nextStates = [$nextChildStateName];
            }

            $childStateTransition = [
                "current_state" => $childStateName,
                "next_states" => $nextStates,
            ];

            $childStateData = [
                "name" => $childStateName,
                "group_name" => strval($key+1),
                "type" => "checker",
                "rules" => [
                    "actor_property_key" => "role",
                    "actor_property_value" => strtolower(str_replace(" ", "_", $role['name'])),
                    "count" => $role['reviewer_count'],
                ],
                "callbacks" => [
                    "status" => [
                        "in" => [
                            "created",
                            "processed"
                        ],
                    ],
                ],
            ];

            $template['state_transitions'][$childStateName] = $childStateTransition;
            $template['states_data'][$childStateName] = $childStateData;
        }
    }

    protected function getNewConfigPartial()
    {
        $merchantId = $this->merchant->getId();
        return [
            "config" => [
                "template" => [
                    "type" => "approval",
                    "state_transitions" => [
                        "START_STATE" => [
                            "current_state" => "START_STATE",
                            "next_states" => [],
                        ]
                    ],
                    "states_data" => [],
                    "allowed_actions" => [
                        "admin" => [
                            "actions" => [
                                "update_data",
                                "rejected"
                            ],
                        ],
                        "user" => [
                            "actions" => [
                                "approved",
                                "rejected"
                            ],
                        ],
                        "rx_live" => [
                            "actions" => [
                                "rejected"
                            ],
                        ],
                    ],
                    "meta" => [
                        "domain" => "payouts",
                        "task_list_name" => "payouts-approval",
                    ]
                ],
                "version" => "1",
                "type" => "payout-approval",
                "name" => $merchantId . " - Payout approval workflow",
                "service" => "rx_live",
                "owner_id" => $merchantId,
                "owner_type" => "merchant",
                "org_id" => "100000razorpay",
                "enabled" => "true"
            ]
        ];
    }
}
