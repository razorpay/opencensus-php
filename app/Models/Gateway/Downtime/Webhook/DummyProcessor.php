<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

use App;
use RZP\Constants\Mode;
use RZP\Models\Gateway\Downtime;

/**
 * This exists only for use in tests.
 */
class DummyProcessor implements ProcessorInterface
{
    public  function process(array $input)
    {
        $app = App::getFacadeRoot();

        $app['rzp.mode'] = Mode::TEST;

        \Database\DefaultConnection::set(Mode::TEST);

        return (new Downtime\Core)->create($input)->toArrayAdmin();
    }

    public function validate(array $input)
    {
        // Noop
    }
}
