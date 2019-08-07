<?php

namespace RZP\Models\Base;

use RZP\Exception;
use RZP\Error\ErrorCode;
use Illuminate\Database\Eloquent;

class Collection extends Eloquent\Collection
{
    public function firstOrFail(callable $callback = null, $default = null)
    {
        $first = parent::first($callback, $default);

        if ($first === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, null, 'No db records found');
        }

        return $first;
    }
}
