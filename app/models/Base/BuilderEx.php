<?php

namespace Models\Base;

use EE\Error\ErrorCode;
use EE\Exception;

class BuilderEx extends \Razorpay\Spine\BuilderEx
{
    public function findOrFailPublic($id, $columns = array('*'))
    {
        if ( ! is_null($model = $this->find($id, $columns))) return $model;

        $e = array(
                'model' => get_class($this->model),
                'attributes' => $id,
                'operation' => 'find');

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID);
    }
}
