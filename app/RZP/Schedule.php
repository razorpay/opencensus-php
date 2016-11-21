<?php

namespace App\RZP;

use Razorpay\Api\Errors\BadRequestError as BadRequestError;
use Razorpay\Api\Errors\ServerError as ServerError;

class Schedule extends Entity
{
    public function getScheduleList()
    {
        $relativeUrl = $this->getEntityUrl();

        return $this->request('GET', $relativeUrl)->toArray();
    }

}
