<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class ItemController extends Controller
{
    public function createItem()
    {
        $input = Request::all();

        $item = $this->service()->create($input);

        return ApiResponse::json($item);
    }

    public function getItem($id)
    {
        $item = $this->service()->fetch($id);

        return ApiResponse::json($item);
    }

    public function getItems()
    {
        $input = Request::all();

        $items = $this->service()->fetchMultiple($input);

        return ApiResponse::json($items);
    }

    public function updateItem($id)
    {
        $input = Request::all();

        $item = $this->service()->update($id, $input);

        return ApiResponse::json($item);
    }

    public function deleteItem($id)
    {
        $response = $this->service()->delete($id);

        return ApiResponse::json($response);
    }
}
