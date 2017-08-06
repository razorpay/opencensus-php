<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Models\Settings;

class SettingsController extends Controller
{
    protected $service = Settings\Service::class;

    public function get(string $entity, string $id, string $key = null)
    {
        if ($key === null)
        {
            $settings = $this->service()->getAll($entity, $id);
        }
        else
        {
            $settings = $this->service()->get($entity, $id, $key);
        }

        return ApiResponse::json($settings);
    }

    public function upsert(string $entity, string $id)
    {
        $input = Request::all();

        return $this->service()->upsert($entity, $id, $input);
    }

    public function delete(string $entity, string $id, string $key)
    {
        return $this->service()->delete($entity, $id, $key);
    }
}
