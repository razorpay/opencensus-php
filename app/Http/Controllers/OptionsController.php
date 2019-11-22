<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use Response;
use ApiResponse;

class OptionsController extends Controller
{
    public function createOptions()
    {
        $input = Request::all();

        $option = $this->service()->create($input);

        return ApiResponse::json($option);
    }

    public function createOptionsAdmin($merchantId)
    {
        $input = Request::all();

        $option = $this->service()->createOptionsAdmin($input, $merchantId);

        return ApiResponse::json($option);
    }

    public function getOptions(string $namespace, string $service)
    {
        $option = $this->service()->find($namespace, $service, null);

        return ApiResponse::json($option);
    }

    public function getOptionsByReferenceId(string $namespace, string $service, string $id)
    {
        $option = $this->service()->find($namespace, $service, $id);

        return ApiResponse::json($option);
    }

    public function getOptionsById(string $id)
    {
        $input = Request::all();

        $option = $this->service()->fetch($id, $input);

        return ApiResponse::json($option);
    }

    public function updateOptions(string $id)
    {
        $input = Request::all();

        $option = $this->service()->update($id, $input);

        return ApiResponse::json($option);
    }

    public function deleteOption(string $id)
    {
        $option = $this->service()->delete($id);

        return ApiResponse::json($option);
    }
}
