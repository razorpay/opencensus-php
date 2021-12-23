<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Settings;

class XAppsController extends Controller
{
    public function getAllSettings()
    {
        $settings = (new Settings\Service())->getAll(Settings\Module::X_APPS);

        return ApiResponse::json($settings);
    }

    public function addOrUpdateSettings()
    {
        (new Settings\Service())->upsert(Settings\Module::X_APPS, $this->input);

        return ApiResponse::json(['success' => true]);
    }
}

