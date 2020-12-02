<?php


namespace RZP\lib\ConditionParser;


class Parser
{
    public function parse(array $conditions, callable $matcherFunc): bool
    {
        return $this->parseCondition($conditions, $matcherFunc, Operator:: AND);
    }

    private function parseCondition(array $conditions, callable $matcherFunc, string $operator): bool
    {
        if (empty($conditions) === true) {
            return false;
        }

        $result = ($operator === Operator:: AND);

        foreach ($conditions as $key => $condition) {
            if ($this->isOperator($key) === true) {
                $decision = $this->parseCondition($condition, $matcherFunc, $key);
            } else {
                $decision = $matcherFunc($key, $condition);
            }
            $result = $this->join($result, $decision, $operator);
        }

        return $result;
    }

    private function isOperator($key): bool
    {
        return in_array($key, Operator::OPERATORS, true);
    }

    private function join(bool $flag,bool $value, string $operator)
    {
        switch ($operator)
        {
            case Operator::AND:
                return ($flag and $value);
            case Operator::OR:
                return ($flag or $value);
        }
    }

}
