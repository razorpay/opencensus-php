<?php

use Response;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\Partnerships\PartnershipsService;
use RZP\Trace\TraceCode;

trait PartnershipServiceAPITrait
{
    public function proxyToPartnershipService(array $parameters, string $partnerId)
    {
        $currentRoute = app('request.ctx')->getRoute();
        $mode =  app('basicauth')->getMode();
        $variant = $this->getReadApiExperimentVariant($partnerId, $currentRoute, $mode);
        if ($this->isReadApiCutOffEnabled($variant) || $this->isReadApiShadowEnabled($variant))
        {
            $prtsPath = PartnershipsService::PartnershipServicePathMap[$currentRoute];
            $this->trace->info(TraceCode::PRTS_READ_API_PROXY_REUEST, [
                'route'      => $currentRoute,
                'prts_path'  => $prtsPath,
                'parameters' => $parameters,
            ]);
            $result =  $this->app->partnerships->sendRequestWithRetry($parameters, $prtsPath, Requests::POST);
            if($result['status_code'] != 200)
            {
                $this->trace->error(TraceCode::PARTNERSHIPS_REQUEST_ERROR, [
                    'route'      => $currentRoute,
                    'prts_path'  => $prtsPath,
                    'parameters' => $parameters,
                    'response'   => $result
                ]);
            }
            return ['response'=> Response::make((string) $result['response'], $result['status_code']), 'isCutOffEnabled' => $this->isApiCutOffEnabled($variant) ];
        }
    }

    private function getReadApiExperimentVariant(string $partnerId, string $routeName, string $mode)
    {
        try
        {
            $requestData = ['mid' => $partnerId, 'route_name' => $routeName, 'mode'=> $mode];

            $properties = [
                'id'            => $partnerId,
                'experiment_id' => $this->app['config']->get('app.prts_read_api_exp_id'),
                'request_data'  => json_encode($requestData),
            ];
            $response = $this->app['splitzService']->evaluateRequest($properties);
            return $response['response']['variant']['name'] ?? '';
        }
        catch (\Exception $e)
        {
            $id        = $properties['id'] ?? null;
            $traceCode = $traceCode ?? TraceCode::SPLITZ_ERROR;
            $this->trace->traceException($e, Trace::ERROR, $traceCode, ['id' => $id]);
            return '';
        }
    }

    private function isReadApiCutOffEnabled(string $variant)
    {
        return $variant == 'cutoff';
    }

    private function isReadApiShadowEnabled(string $variant)
    {
        return $variant == 'shadow';
    }

    public function checkParity($prtsResult, $apiResult)
    {
        // TODO: check if this needs to be updated incase of expected parity fields between api and prts
        $isIdentical =  array_diff($prtsResult, $apiResult) == null;
        if ($isIdentical == false )
        {
            $currentRoute = app('request.ctx')->getRoute();
            $this->trace->info(TraceCode::PRTS_API_PARITY_CHECK_FAILED, [
                'variant'    => $currentRoute,
                'prts_res'   => $prtsResult,
                'api_res'    => $apiResult,
            ]);
            $this->trace->count('prts_api_parity_failed', ['route'=> $currentRoute]);
        }
    }
}
