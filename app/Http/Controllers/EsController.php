<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\EsClient;
use RZP\Models\Base\EsDao;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;

class EsController extends Controller
{
    /**
     * Methods allowed for debug endpoint.
     * Must only contain read operations!
     *
     * @var array
     */
    const ALLOWED_DEBUG_METHODS =[
        'cat',
        'mget',
        'search',
        'explain',
        'getAliases',
        'getMapping',
        'getSettings',
    ];

    /**
     * Route intended for use by dev debugging(READ ONLY), exposed via internal auth.
     *
     * @param string $method
     *
     * @return ApiResponse
     * @throws BadRequestException
     */
    public function debug(string $method)
    {
        $this->validateDebugMethod($method);

        $params = Request::all();

        try
        {
            $res = $this->getEsClient()->$method($params);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ES_DEBUG_FAILED,
                $this->getTracePayload());

            throw $e;
        }

        $resTracePayload = $this->getTracePayload(['response' => $res]);

        $this->trace->info(TraceCode::ES_DEBUG_RESPONSE, $resTracePayload);

        return ApiResponse::json($res);
    }

    // -------------------- Write endpoint starts -----------------------------

    public function postAliases()
    {
        $this->trace->info(TraceCode::ES_ALIASES_WRITE_OP_REQUEST, $this->getTracePayload());

        $params = Request::all();

        try
        {
            $res = $this->getEsClient()->postAliases($params);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ES_ALIASES_WRITE_OP_FAILED,
                $this->getTracePayload());

            throw $e;
        }

        $resTracePayload = $this->getTracePayload(['response' => $res]);

        $this->trace->info(TraceCode::ES_ALIASES_WRITE_OP_RESPONSE, $resTracePayload);

        return ApiResponse::json($res);
    }

    // -------------------- Write endpoint ends -------------------------------

    // -------------------- Protected methods starts --------------------------

    protected function validateDebugMethod(string $method)
    {
        $allowed = in_array($method, self::ALLOWED_DEBUG_METHODS, true);

        if ($allowed === false)
        {
            $tracePayload = $this->getTracePayload();

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ES_DEBUG_METHOD_NOT_VALID,
                null,
                $tracePayload);
        }
    }

    protected function getEsClient(): EsClient
    {
        return (new EsDao)->getEsClient();
    }

    protected function getTracePayload(array $with = [])
    {
        $routeParameters = Request::route()->parameters();
        $input           = Request::all();

        $data = [
            'route_params' => $routeParameters,
            'input'        => $input,
        ];

        return $data + $with;
    }

    // -------------------- Protected methods ends ----------------------------
}
