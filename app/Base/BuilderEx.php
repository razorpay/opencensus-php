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

    /**
     * Queries for many entity by ids.
     * If any single of the given ids are not found, failss with bad request.
     *
     * @param array $ids
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @throws Exception\BadRequestException
     */
    public function findManyOrFailPublic(array $ids, array $columns = ['*'])
    {
        $models = $this->findMany($ids, $columns);

        //
        // If all of the requested ids are found, return the collection.
        //

        $foundIds = $models->pluck('id')->toArray();

        if (count($foundIds) === count($ids))
        {
            return $models;
        }

        //
        // Else, throw error with attributes holding all not found ids.
        //

        $notFoundIds = array_diff($ids, $foundIds);

        $extra = [
            'model' => get_class($this->model),
            'attributes' => $notFoundIds,
            'operation' => 'findMany',
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_IDS, null, $extra);
    }
}
