<?php

namespace RZP\Models\Gateway\Rule;

use RZP\Models\Base;

class Collection extends Base\PublicCollection
{
    public function getRulesWithSpecificityScore(int $score): Collection
    {
        $array = $this->groupBySpecificityScore();

        $rules = $array[$score] ?? new Collection([]);

        return $rules;
    }

    public function groupBySpecificityScore(): array
    {
        $array = [];

        foreach ($this->items as $rule)
        {
            $score = $rule->calculateSpecificityScore();

            if (isset($array[$score]) === false)
            {
                $array[$score] = new Collection([$rule]);
            }
            else
            {
                $array[$score]->push($rule);
            }
        }

        krsort($array);

        return $array;
    }
}
