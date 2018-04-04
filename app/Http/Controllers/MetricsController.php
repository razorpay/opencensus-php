<?php

namespace RZP\Http\Controllers;

use RZP\Http\Response\Header;
use RZP\Services\Metrics\Facade\Metrics;

class MetricsController extends Controller
{
    /**
     * Prometheus usage this endpoint to pull application metrics
     * @return \Illuminate\Http\Response
     */
    public function get()
    {
        $rendered = Metrics::render();
        $headers  = [Header::CONTENT_TYPE => 'text/plain'];

        return response($rendered, 200, $headers);
    }
}
