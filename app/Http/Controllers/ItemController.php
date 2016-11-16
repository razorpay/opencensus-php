<?php

namespace RZP\Http\Controllers;

use Request;

use ApiResponse;
use RZP\Models\Item;

class ItemController extends Controller
{
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new Item\Service();
    }

    public function createItem()
    {
        $input = Request::all();

        $item = $this->service->create($input);

        return ApiResponse::json($item);
    }

    public function getItem($id)
    {
        $item = $this->service->fetch($id);

        return ApiResponse::json($item);
    }

    public function getItems()
    {
        $input = Request::all();

        $items = $this->service->fetchMultiple($input);

        return ApiResponse::json($items);
    }

    public function putItem($id)
    {
        $input = Request::all();

        $item = $this->service->put($id, $input);

        return ApiResponse::json($item);
    }

    public function deleteItem($id)
    {
        $this->service->delete($id);

        return ApiResponse::json([]);
    }
}
