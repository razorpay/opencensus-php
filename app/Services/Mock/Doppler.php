<?php

namespace RZP\Services\Mock;

use RZP\Services\Doppler as BaseDoppler;

class Doppler extends BaseDoppler
{
    public function sendFeedback($eventData)
    {
        // testing;
        s($eventData);
        s("inside doppler mock");
    }
}
