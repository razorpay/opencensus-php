<?php

namespace RZP\Http\Controllers;

use Request;

use RZP\Http\ApiResponse;
use RZP\Models\LineItem;

class LineItemController extends Controller
{
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = new LineItem\Service();
    }

    public function createLineItem()
    {
        $input = Request::all();

        $item = $this->service->create($input);

        return ApiResponse::json($item);
    }

    public function getLineItem($id)
    {
        $item = $this->service->fetch($id);

        return ApiResponse::json($item);
    }

    public function getLineItems()
    {
        $input = Request::all();

        $items = $this->service->fetchMultiple($input);

        return ApiResponse::json($items);
    }
}