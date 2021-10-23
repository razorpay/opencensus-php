<?php

namespace RZP\Http\Controllers;

use RZP\Http\Request\Requests;
use Request;
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
    public function any($path)
    {
        $method = Request::method();
        $input = Request::all();
        $headers         = ['Content-Type' => 'application/json'];

        //In case of content and apspdcl endpoint is not present in payload, then continue the current flow
        if (isset($input['url']) === true)
        {
            $apspdclEndpoint = $input['url'];
            $inputParam      = $input['content'];
        }
        else
        {
            /*
             * To handle the existing flow added this condition. this happens when route is called from old flow
             */
            $apspdclEndpoint = config('services.apspdcl.base_url') . '/' . $path;
            $inputParam      = $input;
        }

        $this->trace->info(TraceCode::APSPDCL_REQUEST, compact('apspdclEndpoint', 'headers', 'input', 'method'));

        // Default response code, body and headers for failure case.
        $respCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
        $respBody    = '';
        $respHeaders = [];

        try
        {
            $resp        = Requests::request($apspdclEndpoint, $headers, json_encode($inputParam), $method);
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
