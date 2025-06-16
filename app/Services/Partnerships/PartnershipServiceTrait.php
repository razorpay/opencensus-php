<?php

namespace  RZP\Services\Partnerships;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\Partnerships\PartnershipsService;
use RZP\Trace\TraceCode;

trait PartnershipServiceTrait
{

    static array $PartnershipServicePathMap = array(
        'commissions_invoice_fetch_all'       => '/twirp/rzp.commissions.commission_invoice.v1.CommissionInvoiceAPI/List',
        'commissions_invoice_fetch'           => '/twirp/rzp.commissions.commission_invoice.v1.CommissionInvoiceAPI/Get',
        'commissions_get_multiple'            => PartnershipsService::LIST_COMMISSION_URL,
        'commissions_get'                     => PartnershipsService::GET_COMMISSION_URL,
        'partner_kyc_access_request'          => PartnershipsService::CREATE_PARTNER_KYC_ACCESS_STATE,
        'partner_kyc_approve_reject'          => PartnershipsService::APPROVE_REJECT,  
    );

    static array $RouteExcludedKeyMap = [
        'commissions_invoice_fetch_all'       => array('created_at', 'updated_at', 'pdf', 'line_items', 'notes', 'tnc'),
        'commissions_invoice_fetch'           => array('created_at', 'updated_at', 'pdf', 'notes', 'tnc'),
        'commissions_get_multiple'            => array('created_at', 'updated_at'),
        'commissions_get'                     => array('created_at', 'updated_at'),
        'partner_kyc_access_request'          => array('created_at', 'updated_at'),
    ];

    public function proxyToPartnershipService(array $parameters, string $partnerId)
    {
        $currentRoute = app('request.ctx')->getRoute();
        $mode =  app('basicauth')->getMode();
        $experimentId = $this->app['config']->get('app.prts_read_api_exp_id');
        $variant = $this->getExperimentVariant($experimentId, $partnerId, $currentRoute, $mode);
        try {
            if ($this->isExpModeCutOff($variant) || $this->isExpModeShadow($variant)) {
                $prtsPath = static::$PartnershipServicePathMap[$currentRoute];
                $this->trace->info(TraceCode::PRTS_READ_API_PROXY_REUEST, [
                    'route' => $currentRoute,
                    'prts_path' => $prtsPath,
                    'parameters' => $parameters,
                ]);
                $result = $this->app->partnerships->sendRequestWithRetry($parameters, $prtsPath, Requests::POST);
                if ($result['status_code'] != 200) {
                    $this->trace->error(TraceCode::PARTNERSHIPS_REQUEST_ERROR, [
                        'route' => $currentRoute,
                        'prts_path' => $prtsPath,
                        'parameters' => $parameters,
                        'response' => $result
                    ]);
                }
                return ['response' => $result['response'] ?? [], 'status_code' => $result['status_code'], 'isCutOffEnabled' => $this->isExpModeCutOff($variant)];
            }
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PRTS_READ_API_PROXY_ERROR, ['route' => $currentRoute, 'parameters' => $parameters]);
            if ($this->isExpModeCutOff($variant)) {
                throw new Exception\BadRequestException(ErrorCode::SERVER_ERROR, $e->getMessage());
            }
        }
        return ['response' => []];
    }

    public function proxyToPartnershipServiceForPKYC(array $parameters, string $partnerId)
    {
        $currentRoute = app('request.ctx')->getRoute();

        $experimentId = $this->app['config']->get('app.prts_kyc_access_status_writes_exp_id');
        $variant = $this->getExperimentVariant($experimentId, $partnerId, $currentRoute);

        // Inject exp_mode into the payload so that PRTS doesn't need to evaluate it twice
        $parameters['exp_mode'] = $parameters['exp_mode'] ?? $variant;

        try {
            // Call PRTS via api proxy for reverse-shadow and cutoff.
            if ($this->isExpModeReverseShadow($variant) || $this->isExpModeCutOff($variant)) {
                $prtsPath = static::$PartnershipServicePathMap[$currentRoute];
                $this->trace->info(TraceCode::PRTS_PKYC_WRITE_API_PROXY_REQUEST, [
                    'route' => $currentRoute,
                    'prts_path' => $prtsPath,
                    'parameters' => $parameters,
                ]);
                $result = $this->app->partnerships->sendRequest($parameters, $prtsPath, Requests::POST);
                if ($result['status_code'] != 200) {
                    $this->trace->error(TraceCode::PARTNERSHIPS_REQUEST_ERROR, [
                        'route' => $currentRoute,
                        'prts_path' => $prtsPath,
                        'parameters' => $parameters,
                        'response' => $result
                    ]);
                }
                return [
                    'response' => $result['response'] ?? [],
                    'status_code' => $result['status_code'],
                    'variant' => $variant,
                ];
            }
        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PRTS_PKYC_WRITE_API_PROXY_ERROR, ['route' => $currentRoute, 'parameters' => $parameters]);
            if ($this->isExpModeCutOff($variant)) {
                throw new Exception\BadRequestException(ErrorCode::SERVER_ERROR, $e->getMessage());
            }
        }
        return [
            'response' => [],
            'status_code' => null,
            'variant'  => $variant,
        ];
    }


    private function getExperimentVariant(string $experimentId, string $partnerId, string $routeName)
    {
        try {
            $requestData = ['mid' => $partnerId, 'route_name' => $routeName];

            $properties = [
                'id'            => $partnerId,
                'experiment_id' => $experimentId,
                'request_data'  => json_encode($requestData),
            ];
            $response = $this->app['splitzService']->evaluateRequest($properties);
            return $response['response']['variant']['name'] ?? '';
        } catch (\Exception $e) {
            $id        = $properties['id'] ?? null;
            $traceCode = $traceCode ?? TraceCode::SPLITZ_ERROR;
            $this->trace->traceException($e, Trace::ERROR, $traceCode, ['id' => $id]);
            return '';
        }
    }

    private function isExpModeCutOff(string $variant)
    {
        return $variant == 'cutoff';
    }

    private function isExpModeShadow(string $variant)
    {
        return $variant == 'shadow';
    }

    private function isExpModeReverseShadow(string $variant)
    {
        return $variant == 'reverse-shadow';
    }

    public function checkParity($prtsResult, $apiResult)
    {
        $currentRoute = app('request.ctx')->getRoute();
        $isIdentical = $this->isResultIdentical($prtsResult, $apiResult, $currentRoute);
        if ($isIdentical === false) {
            $this->trace->info(TraceCode::PRTS_API_PARITY_CHECK_FAILED, [
                'variant'    => $currentRoute,
                'prts_res'   => $prtsResult,
                'api_res'    => $apiResult,
            ]);
            $this->trace->count('prts_api_parity_failed', ['route' => $currentRoute]);
        }
    }

    private function isResultIdentical(array $prtsResult, array $apiResult, string $routeName): bool
    {
        // check if all the keys in prts is present in api and values are same
        // some keys might be present in api which are not there in api we can ignore those values
        foreach ($prtsResult as $key => $value) {
            $excludedKeyArray = static::$RouteExcludedKeyMap[$routeName] ?? [];
            // ignore some keys from parity
            if (in_array($key, $excludedKeyArray) ==  true) {
                continue;
            }
            // if key is not present in api result then return false
            if (array_key_exists($key, $apiResult) == false) {
                return false;
            }
            // if value is array then recursively call the function
            if (is_array($value)) {
                return $this->isResultIdentical($prtsResult[$key], $apiResult[$key], $routeName);
            } else {
                if ($value != $apiResult[$key]) {
                    return false;
                }
            }
        }
        return true;
    }
}
