<?php

namespace RZP\Models\Payout;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;

class WorkflowMigration
{
    protected $merchant;

    protected $merchantId;

    protected $payoutCore;

    public function __construct()
    {
        $this->payoutCore = new Core();
    }

    public function convertOldSummaryIntoNew(Merchant\Entity $merchant, bool $skipFetchFromWfs, bool $returnOld, bool $isCacEnabled = false)
    {
        $this->merchant = $merchant;

        $this->merchantId = $merchant->getId();

        $oldConfigByAmountRules = $this->payoutCore->getFetchWorkflowSummary($skipFetchFromWfs);

        if ($returnOld === true)
        {
            return $oldConfigByAmountRules;
        }

        return $this->getNewConfigFromOldAmountRules($oldConfigByAmountRules, $isCacEnabled);
    }

    protected function getNewConfigFromOldAmountRules($oldConfigByAmountRules, $isCacEnabled)
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

            $parentStateName = "{$minAmount}-{$maxAmount}_workflow";

            $startStateTransitions['next_states'][] = $parentStateName;

            $childStateData = $this->getAmountRuleStateData($parentStateName, $minAmount, $maxAmount);

            $template['states_data'][$parentStateName] = $childStateData;

            $parentStateNames[] = $parentStateName;

            foreach ($oldConfigByAmountRule['steps'] as $step => $stepData )
            {
                $childStateNames = [];

                foreach ($stepData['roles'] as $key => $role )
                {
                    $name = str_replace(" ", "_", $role['name']);
                    $nextChildStateName = "{$name}_{$role['id']}_{$rangeKey}_{$step}_Approval";
                    $childStateNames[] = $nextChildStateName;

                    $template['states_data'][$nextChildStateName] = $this->getCheckerStatedata($nextChildStateName, $step, $role, $isCacEnabled);
                }

                $this->createStateTransitionForParent($template, $parentStateNames, $childStateNames);

                $parentStateNames = $childStateNames;

                if($stepData['op_type'] === 'and' && count($parentStateNames) > 1)
                {
                    $andStateName = "And_{$rangeKey}_{$step}_Result";

                    $this->createStateTransitionForParent($template, $parentStateNames, [$andStateName]);

                    $template['states_data'][$andStateName] = $this->getAndResultStateData($andStateName, $step, $parentStateNames);

                    $parentStateNames = [];
                    $parentStateNames[] = $andStateName;
                }
            }

            $this->createStateTransitionForParent($template, $parentStateNames, ["END_STATE"]);

            $parentStateNames = [];
        }

        return $newConfig;
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
                        "owner" => [
                            "actions" => [
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

    protected function getAmountRuleStateData($parentStateName, $minAmount, $maxAmount)
    {
        return [
            "name" => $parentStateName,
            "group_name" => "0",
            "type" => "between",
            "rules" => [
                "key" => "amount",
                "min" => $minAmount,
                "max" => $maxAmount,
            ]
        ];
    }

    protected function getCheckerStatedata($nextChildStateName, $step, $role, $isCacEnabled)
    {
        $value = strtolower(str_replace(" ", "_", $role['name']));

        if ($isCacEnabled === true)
        {
            $value = $role['id'];
        }

        return [
            "name" => $nextChildStateName,
            "group_name" => strval($step+1),
            "type" => "checker",
            "rules" => [
                "actor_property_key" => "role",
                "actor_property_value" => $value,
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
    }

    protected function createStateTransitionForParent( & $template, $parentNames, $childs)
    {
        foreach ($parentNames as $parentName)
        {
            $template['state_transitions'][$parentName] = [
                "current_state" => $parentName,
                "next_states" => $childs
            ];
        }
    }

    protected function getAndResultStateData($andStateName, $step, $parentStateNames)
    {
        return [
            "name" => $andStateName,
            "group_name" => strval($step+1),
            "type" => "merge_states",
            "rules" => [
                "states" => $parentStateNames
            ]
        ];
    }

}
