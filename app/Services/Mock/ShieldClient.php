<?php

namespace RZP\Services\Mock;

class ShieldClient
{
    const TEST_RESPONSE = [
        "id"         => "12345678",
        "expression" => "amount > 10000",
        "created_at" => 1518608813,
        "updated_at" => 1518608813,
        "action"     => "",
    ];

    public function createRule(array $input)
    {
        return TEST_RESPONSE;
    }

    public function getRules()
    {
        return [TEST_RESPONSE];
    }

    public function getRuleById(string $id)
    {
        return TEST_RESPONSE;
    }

    public function deleteRuleById(string $id)
    {
        return null;
    }

    public function updateRuleById(string $id, array $input)
    {
        return TEST_RESPONSE;
    }

    public function evaluateRules($input)
    {
        return [
            "result" => false,
            "triggered_rules" => [],
        ];
    }
}
