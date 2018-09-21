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
        $path    = $req->getRequestUri();
        $method  = $req->method();
        $input   = $req->post();
        $headers = ['Content-Type' => 'application/json'];

        $this->trace->info(TraceCode::APSPDCL_REQUEST, compact('path', 'method', 'input', 'headers'));

        $apspdclEndpoint = config('services.apspdcl.base_url') . str_after($req->getRequestUri(), '/v1/apspdcl');

        // Default response code, body and headers for failure case.
        $code    = Response::HTTP_INTERNAL_SERVER_ERROR;
        $body    = '';
        $headers = [];

        try
        {
            $resp    = Requests::request($apspdclEndpoint, $headers, $input, $method);
            $code    = $resp->status_code;
            $body    = $resp->body;
            $headers = $resp->headers->getAll();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::APSPDCL_REQUEST_ERROR, compact('path', 'method', 'input'));
        }

        $this->trace->info(TraceCode::APSPDCL_RESPONSE, compact('code', 'body', 'headers'));

        return ResponseFactory::make($body, $code, $headers);
    }
}
