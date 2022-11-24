<?php

namespace RZP\Models\Base;

use RZP\Exception;
use RZP\Error\ErrorCode;
use Illuminate\Database\Eloquent;

class Collection extends Eloquent\Collection
{
    /**
     * @throws Exception\BadRequestException
     */
    public function firstOrFail($key = null, $operator = null, $value = null)
    {
        $first = parent::first($key, $operator);

        if ($first === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, null, 'No db records found');
        }

        return $first;
    }
}
