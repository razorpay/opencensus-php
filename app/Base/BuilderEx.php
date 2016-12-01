<?php

namespace RZP\Base;

use RZP\Error\ErrorCode;
use RZP\Exception;

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
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $e);
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     *
     * @throws Exception\BadRequestException
     */
    public function firstOrFailPublic($columns = array('*'))
    {
        if ( ! is_null($model = $this->first($columns))) return $model;

        $e = array(
                'model' => get_class($this->model),
                'attributes' => $columns,
                'operation' => 'find');

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, $e);
    }
}
