<?php

namespace RZP\Models\Base;

use RZP\Error\ErrorCode;
use RZP\Exception;

class BuilderEx extends \Razorpay\Spine\BuilderEx
{
    public function findOrFailPublic($id, $columns = array('*'))
    {
        if ( ! is_null($model = $this->find($id, $columns))) return $model;

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID);
    }
}
