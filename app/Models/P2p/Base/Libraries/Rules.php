<?php

namespace RZP\Models\P2p\Base\Libraries;

use Razorpay\Api\ArrayableInterface;

class Rules implements ArrayableInterface
{
    protected $rules = [];

    public function __construct(array $rules, array $with = [])
    {
        $this->rules = $this->with($rules, $with);
    }

    public function only(array $keys): self
    {
        $this->rules = array_only($this->rules, $keys);

        return $this;
    }

    public function except(array $keys): self
    {
        $this->rules = array_except($this->rules, $keys);

        return $this;
    }

    public function merge(array $array): self
    {
        $this->rules = array_merge($this->rules, $array);

        return $this;
    }

    public function arrayRules(string $prepend, array $rules)
    {
        $prepended = [
            $prepend    => 'sometimes|array',
        ];

        foreach ($rules as $key => $rule)
        {
            $prepended[$prepend . '.' . $key] = $rule;
        }

        $this->merge($prepended);
    }

    public function toArray()
    {
        return $this->rules;
    }

    /**
     * If the rule definition is found in base rules,
     * we will prepend it to current rule.
     *
     * @param array $rules
     * @param array $with
     * @return array
     */
    protected function with(array $baseRules, array $with)
    {
        foreach ($with as $key => & $rule)
        {
            if (isset($baseRules[$key]) === true)
            {
                $rule .= '|' . $baseRules[$key];
            }
        }

        return $with;
    }
}
