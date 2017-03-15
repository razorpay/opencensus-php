<?php

namespace RZP\Services\Mock;

use App;
use RZP\Trace\TraceCode;
use RZP\Services\Drip as BaseDrip;

class Drip extends BaseDrip
{
    /**
     * Tracing request when mock = true
     */
    protected function sendRequest(string $url, array $data, string $method)
    {
        $app = App::getFacadeRoot();

        $url = $this->baseUrl . $this->accountId . $url;

        $content = json_encode($data);

        $app['trace']->info(
            TraceCode::MOCK_DRIP_REQUEST,
            [
                'url'     => $url,
                'content' => $content,
                'method'  => $method
            ]);
    }
}
