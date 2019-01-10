<?php

namespace RZP\Models\PaymentLink\Template;

interface StorageAccess
{
    /**
     * Returns true if content for set identifier(by constructor) exists.
     * @return boolean
     */
    public function exists(): bool;

    /**
     * Returns the string content for set identifier if exists, else null.
     * @return string|null
     */
    public function get();
}
