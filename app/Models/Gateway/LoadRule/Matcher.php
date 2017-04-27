<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Models\Base;
use RZP\Models\Terminal;

trait Matcher
{
    /**
     * Evaluates if a rule's terminal related attributes match those of
     * given terminal
     *
     * @param  Terminal\Entity $terminal Terminal entity to compare against
     * @return bool whether rule matches terminal
     */
    public function matches(Terminal\Entity $terminal): bool
    {
        foreach (self::COMPARISON_ATTRIBUTES as $key)
        {
            // For certain attributes like gateway_acquirer, null means all, hence
            // we don't match if the value for these attributes is null
            if ((in_array($key, self::NULLABLE_ATTRIBUTES, true) === true) and
                    $this->getAttribute($key) === null)
            {
                continue;
            }

            if ($this->match($key, $terminal) === false)
            {
                return false;
            }
        }

        return true;
    }

    protected function match(string $key, Terminal\Entity $terminal): bool
    {
        return ($this->getAttribute($key) === $terminal->getAttribute($key));
    }
}
