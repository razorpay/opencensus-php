<?php

namespace RZP\Http\Controllers;

use RZP\Models\Base\EsDao;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use Request;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use ApiResponse;

class EsController extends Controller
{
    protected $esDao;

    /**
     * Methods allowed for debug endpoint.
     * MUST ONLY CONTAIN READ OPERATIONS!
     *
     * @var array
     */
    protected static $allowedDebugMethods =[
        'cat',
        'search',
        'explain',
        'getMapping',
        'getSettings',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->esDao = new EsDao;
    }

    /**
     * Route intended for use by dev debugging, exposed via internal auth only.
     * ONLY READ OPERATIONS, NO WRITE OPERATIONS TO BE ADDED EVER!
     *
     * @param string $method
     *
     * @return ApiResponse
     * @throws BadRequestException
     */
    public function debug(string $method)
    {
        if (in_array($method, self::$allowedDebugMethods, true) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ES_DEBUG_METHOD_NOT_VALID,
                null,
                [
                    'method' => $method
                ]);
        }

        $params = Request::all();
        $res    = [];

        try
        {
            $res = $this->esDao->getEsClient()->$method($params);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ES_DEBUG_FAILED,
                $params);
        }

        return ApiResponse::json($res);
    }
}
