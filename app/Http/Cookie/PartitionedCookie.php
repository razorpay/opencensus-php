<?php

namespace RZP\Http\Cookie;

use Symfony\Component\HttpFoundation\Cookie;
use Stringable;

class PartitionedCookie extends Cookie implements Stringable
{
    protected bool $partitioned = false;

    /**
     * Sets the partitioned attribute to true.
     */
    public function setPartitioned(bool $partitioned): void
    {
        $this->partitioned = $partitioned;
    }

    /**
     * Checks if the cookie is partitioned.
     */
    public function isPartitioned(): bool
    {
        return $this->partitioned;
    }

    /**
     * Returns the cookie as a string with the partitioned attribute if set.
     */
    public function __toString(): string
    {
        $str = parent::__toString();

        if ($this->isPartitioned()) {
            $str .= '; Partitioned';
        }

        return $str;
    }
}
