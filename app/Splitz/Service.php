<?php

namespace App\Splitz;

use App\Base;
use App\Trace\TraceCode;
use App\User\Constants;
use App\Admin\ApiRequestAny;
use Illuminate\Foundation\Application;

class Service extends Base\Service
{
    protected $trace;

    /**
     * @var Application
     */
    protected $app;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];
    }

    public function getSplitzVariant($merchantId): array
    {
        $data = [];

        foreach (config('splitz.experiments') as $experimentFeatureFlag)
        {
            $variant = $this->getVariant($experimentFeatureFlag, $merchantId);

            $data[$experimentFeatureFlag] = $this->transformVariablesFromVariantIfExist($variant);
        }

        return $data;
    }

    public function getVariant($experimentId, $merchantId)
    {
        if (empty($experimentId) === true)
        {
            return [];
        }

        $request = new ApiRequestAny();

        $requestData = ['mid' => $merchantId];

        $input = [
            'id'            => $merchantId,
            'experiment_id' => $experimentId,
            'request_data'  => json_encode($requestData, true)
        ];

        list($error, $data) = $request->processInput($input)->send("splitz/evaluate", 'POST');

        if (empty($error) === false)
        {
            $this->trace->info(TraceCode::SPLITZ_EVALUATE_FAILED, ["error" => $error]);

            return [];
        }

        if (isset($data['response']['variant']) === true)
        {
            return $data['response']['variant'];
        }

        // return empty response if not found.
        return [];
    }

    public function transformVariablesFromVariantIfExist($variant)
    {
        $variableTrans = [];

        if (isset($variant['variables']) === true)
        {
            $variables = $variant['variables'];

            foreach ($variables as $variable)
            {
                $key = $variable['key'] ?? null;
                $value = $variable['value'] ?? null;

                if (empty($key) === false && empty($value) === false)
                {
                    $variableTrans[$key] = $value;
                }
            }

            $variant['variables'] = $variableTrans;
        }

        return $variant;
    }
}
