<?php

namespace RZP\Models\P2p\Base\Libraries;

use RZP\Exception\LogicException;
use Razorpay\Api\ArrayableInterface;

class Rules implements ArrayableInterface
{
    protected $rules = [];

    public function __construct(array $rules)
    {
        $this->rules = $rules;
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

    public function merge(Rules $rules): self
    {
        $this->rules = array_merge($this->rules, $rules->toArray());

        return $this;
    }

    /**
     * Make array rules with $prepend string. And append in the existing rules.
     *
     * @param string $prepend
     * @param array $rules
     * @param bool $nested
     * @return $this
     */
    public function arrayRules(string $prepend, array $rules, bool $nested = false)
    {
        $prepended = [
            $prepend    => 'sometimes|array',
        ];

        $connector = $nested ? '.*.' : '.';

        foreach ($rules as $key => $rule)
        {
            $prepended[$prepend . $connector . $key] = $rule;
        }

        $this->merge(new Rules($prepended));

        return $this;
    }

    /**
     * Wrap all the rules into given $prepend string. And replace the existing rules
     *
     * @param string $prepend
     * @param bool $nested
     * @return $this
     */
    public function wrapRules(string $prepend, bool $nested = false)
    {
        $prepended = [
            $prepend    => 'sometimes|array',
        ];

        $connector = $nested ? '.*.' : '.';

        foreach ($this->rules as $key => $rule)
        {
            $prepended[$prepend . $connector . $key] = $rule;
        }

        $this->rules = $prepended;

        return $this;
    }

    public function toArray()
    {
        return $this->rules;
    }

    /**
     * If the rule definition is found in base rules,
     * we will prepend it to current rule.
     *
     * @param array $with
     * @return $this
     */
    public function with(array $with): self
    {
        foreach ($with as $key => & $rule)
        {
            if (isset($this->rules[$key]) === true)
            {
                $rule .= '|' . $this->rules[$key];
            }
        }

        $this->rules = $with;

        return $this;
    }
}
