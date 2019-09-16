<?php

namespace RZP\Services\Mock;

use RZP\Services\Doppler as BaseDoppler;

class Doppler extends BaseDoppler
{

    public function sendSuccessFeedback($data)
    {
        return null;
    }

    public function sendFailureFeedback($data)
    {
        return [
            'error' => '',
            'success' => true,
        ];
    }
}
