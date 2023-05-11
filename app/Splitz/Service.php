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

    public function getSplitzVariantBulk($merchantId): array
    {
        return $this->getVariantBulk($merchantId, config('splitz.experiments'));
    }

    public function getVariantBulk($merchantId, $experimentIds, $clientType = ['client_type' => 'merchant'], $url = 'splitz/bulkEvaluateProxy', $optionalRequestData = [])
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_SPLITZ_EXPERIMENTS_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $responseData = [];

        if (empty($experimentIds) === true)
        {
            return [];
        }

        $request = new ApiRequestAny($clientType);

        $requestData = ['mid' => $merchantId];

        $requestData = array_merge($requestData, $optionalRequestData);

        $input = [];

        foreach ($experimentIds as $experimentId)
        {
            $experimentInput = [
                'id'            => $merchantId,
                'experiment_id' => $experimentId,
                'request_data'  => json_encode($requestData, true)
            ];

            array_push($input, $experimentInput);
        }

        list($error, $data) = $request->processInput($input)->send($url, 'POST');

        if (empty($error) === false)
        {
            $this->trace->info(TraceCode::SPLITZ_BULK_EVALUATE_FAILED, ["error" => $error]);

            return [];
        }

        foreach ($data as $output)
        {
            if (isset($output['experiment']['id']) === true)
            {
                $experimentFeatureFlag = $output['experiment']['id'];

                if (isset($output['variant']) === true)
                {
                    $responseData[$experimentFeatureFlag] = $this->transformVariablesFromVariantIfExist($output['variant']);
                }
                else
                {
                    $responseData[$experimentFeatureFlag] = [];
                }
            }
        }

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_SPLITZ_EXPERIMENTS_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']

        ]);

        return $responseData;
    }

    public function getSplitzVariant($merchantId): array
    {
        $data = [];

        foreach (config('splitz.experiments') as $key => $experimentFeatureFlag)
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
