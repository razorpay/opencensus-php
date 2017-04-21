<?php

namespace RZP\Http\Controllers\Traits;

use ApiResponse;

/**
 * This trait if used in a controller supports CRUD methods.
 *
 * Expects:
 * - $this->service
 *
 */
trait HasCrudMethods
{
    public function get(string $id)
    {
        $entity = $this->service->fetch($id);

        return ApiResponse::json($entity);
    }

    public function list()
    {
        $entities = $this->service->fetchMultiple($this->input);

        return ApiResponse::json($entities);
    }

    public function create()
    {
        $entity = $this->service->create($this->input);

        return ApiResponse::json($entity);
    }

    public function update(string $id)
    {
        $entity = $this->service->update($id, $this->input);

        return ApiResponse::json($entity);
    }

    public function delete(string $id)
    {
        $response = $this->service->delete($id);

        return ApiResponse::json($response);
    }
}
