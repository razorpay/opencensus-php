<?php

namespace RZP\Base;

use RZP\Error\ErrorCode;
use RZP\Exception;

class BuilderEx extends \Razorpay\Spine\BuilderEx
{
    public function findOrFailPublic($id, $columns = array('*'))
    {
        $model = $this->find($id, $columns);

        if (is_null($model) === false)
        {
            return $model;
        }

        $data = [
                'model' => get_class($this->model),
                'attributes' => $id,
                'operation' => 'find'
            ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
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

        $data = array(
                'model' => get_class($this->model),
                'attributes' => $columns,
                'operation' => 'find');

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, $data);
    }

    /**
     * Queries for many entity by ids.
     * If any single of the given ids are not found, fails with bad request.
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
        if ($models->count() === count($ids))
        {
            return $models;
        }

        //
        // All ids not found so trace the diff with attributes
        // holding all the not found ids.
        //
        $foundIds = $models->pluck('id')->toArray();

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
