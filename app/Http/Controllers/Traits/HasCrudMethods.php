<?php

namespace RZP\Http\Controllers\Traits;

use Request;
use ApiResponse;

/**
 * This trait if used in a controller supports CRUD methods.
 *
 * Expects:
 * - $this->service to be initialized with corresponding class name(string)
 *
 */
trait HasCrudMethods
{
    public function get(string $id)
    {
        $entity = $this->service()->fetch($id);

        return ApiResponse::json($entity);
    }

    public function list()
    {
        $input = Request::all();

        $entities = $this->service()->fetchMultiple($input);

        return ApiResponse::json($entities);
    }

    public function create()
    {
        $input = Request::all();

        $entity = $this->service()->create($input);

        return ApiResponse::json($entity);
    }

    public function update(string $id)
    {
        $input = Request::all();

        $entity = $this->service()->update($id, $input);

        return ApiResponse::json($entity);
    }

    public function delete(string $id)
    {
        $response = $this->service()->delete($id);

        return ApiResponse::json($response);
    }
}
