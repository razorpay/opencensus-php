<?php

namespace RZP\Models\Merchant\Acs\SplitzHelper;

use App;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Modules\Acs\Wrapper\Constant;

class SplitzHelper
{
    protected $app;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app[Constant::TRACE];

        $this->splitzService = $this->app[Constant::SPLITZ_SERVICE];

    }

    /**
     * @param string $experimentId
     * @param string $id
     * @param array $metadata
     * @return bool
     */
    public function isSplitzOn(string $experimentId, string $id, array $metadata = []): bool
    {
        try {
            $request = ['id' => $id, 'experiment_id' => $experimentId];

            $this->trace->info(TraceCode::ASV_SPLITZ_REQUEST, ['request' => $request, 'metadata' => $metadata]);
            $response = $this->splitzService->evaluateRequest($request);
            $this->trace->info(TraceCode::ASV_SPLITZ_RESPONSE, $response);

            if ($response['status_code'] !== 200) {
                return false;
            }

            $variant = $response['response']['variant'] ?? [];

            $variables = $variant['variables'] ?? [];

            foreach ($variables as $variable) {
                $key = $variable['key'] ?? '';
                $value = $variable['value'] ?? '';

                if ($key === 'enabled' && $value === 'true') {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ASV_SPLITZ_ERROR);

            return false;
        }
    }
}

