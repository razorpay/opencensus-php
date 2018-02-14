<?php

namespace RZP\Services\Mock;

class ShieldClient
{
    public function createRule(array $input)
    {
        return [
            'id'         => '12345678',
            'expression' => $input['expression'],
            'is_active'  => true,
            'created_at' => 1518608813,
            'updated_at' => 1518608813,
        ];
    }
}
