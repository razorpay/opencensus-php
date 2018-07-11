<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class ItemController extends Controller
{
    public function createItem()
    {
        $item = $this->service()->create($this->input);

        return ApiResponse::json($item);
    }

    public function getItem(string $id)
    {
        $item = $this->service()->fetch($id, $this->input);

        return ApiResponse::json($item);
    }

    public function getItems()
    {
        $items = $this->service()->fetchMultiple($this->input);

        return ApiResponse::json($items);
    }

    public function updateItem(string $id)
    {
        $item = $this->service()->update($id, $this->input);

        return ApiResponse::json($item);
    }

    public function deleteItem(string $id)
    {
        $response = $this->service()->delete($id);

        return ApiResponse::json($response);
    }
}
