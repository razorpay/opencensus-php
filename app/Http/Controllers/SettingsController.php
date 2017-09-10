<?php

namespace RZP\Http\Controllers;

use Request;

use ApiResponse;
use RZP\Models\Settings;

class SettingsController extends Controller
{
    protected $service = Settings\Service::class;

    public function get(string $module, string $key = null)
    {
        if ($key === null)
        {
            $settings = $this->service()->getAll($module);
        }
        else
        {
            $settings = $this->service()->get($module, $key);
        }

        return ApiResponse::json($settings);
    }

    public function upsert(string $module)
    {
        $input = Request::all();

        $this->service()->upsert($module, $input);

        return ApiResponse::json(['success' => true]);
    }

    public function delete(string $module, string $key)
    {
        $this->service()->delete($module, $key);

        return ApiResponse::json(['success' => true]);
    }

    public function getDefined(string $module)
    {
        $settings = $this->service()->getDefined($module);

        return ApiResponse::json($settings);
    }
}
