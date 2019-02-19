<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

use RZP\Models\Gateway\Downtime;

/**
 * This exists only for use in tests.
 */
class DummyProcessor implements ProcessorInterface
{
    public  function process(array $input)
    {
        return (new Downtime\Core)->create($input)->toArrayAdmin();
    }

    public function validate(array $input)
    {
        // Noop
    }
}
