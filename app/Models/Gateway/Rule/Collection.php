<?php

namespace RZP\Models\Gateway\Rule;

use RZP\Models\Base;

class Collection extends Base\PublicCollection
{
    /**
     * Among a set of rules grouped by the specificity score, gets the rules
     * which have a given score. It returns an empty collection if no rules
     * with given score are presen
     *
     * @param  int          $score
     * @return Collection
     */
    public function getRulesWithSpecificityScore(int $score): Collection
    {
        $array = $this->groupBySpecificityScore();

        $rules = $array[$score] ?? new Collection([]);

        return $rules;
    }

    /**
     * Calculates the specificity score for each rule and groups them by the score.
     * The returned array is of the form
     * [
     *     <score> => rule_collection
     * ]
     *
     * @return array
     */
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

        //
        // We order the array in decreasing order of the specificity scores
        // as that is the order each collection of rules will be considered
        // during terminal sorting
        //
        krsort($array);

        return $array;
    }
}
