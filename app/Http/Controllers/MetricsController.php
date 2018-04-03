<?php

namespace RZP\Http\Controllers;

use RZP\Services\Metrics\Facade\Metrics;

class MetricsController extends Controller
{
    public function get()
    {
        // TODO: Wrap in try..catch
        $rendered = Metrics::render();

        return response($rendered, 200, ['Content-Type', 'text/plain']);
    }
}
