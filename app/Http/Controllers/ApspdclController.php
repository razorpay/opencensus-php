<?php

namespace RZP\Http\Controllers;

use Requests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Response as ResponseFactory;

use RZP\Trace\TraceCode;

class ApspdclController extends Controller
{
    /**
     * Forwards request to <API>/apspdcl/* to <apspdcl>/* and returns the response.
     * @param  Request $req
     * @return Response
     */
    public function any(Request $req)
    {
        $path            = $req->getRequestUri();
        $method          = $req->method();
        $input           = $req->post();
        $headers         = ['Content-Type' => 'application/json'];
        $apspdclEndpoint = config('services.apspdcl.base_url') . str_after($req->getRequestUri(), '/v1/apspdcl');

        $this->trace->info(TraceCode::APSPDCL_REQUEST, compact('apspdclEndpoint', 'headers', 'input', 'method'));

        // Default response code, body and headers for failure case.
        $respCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
        $respBody    = '';
        $respHeaders = [];

        try
        {
            $resp        = Requests::request($apspdclEndpoint, $headers, json_encode($input), $method);
            $respCode    = $resp->status_code;
            $respBody    = $resp->body;
            $respHeaders = $resp->headers->getAll();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::APSPDCL_REQUEST_ERROR,
                compact('apspdclEndpoint', 'headers', 'input', 'method'));
        }

        $this->trace->info(TraceCode::APSPDCL_RESPONSE, compact('respCode', 'respBody', 'respHeaders'));

        return ResponseFactory::make($respBody, $respCode, $respHeaders);
    }
}
