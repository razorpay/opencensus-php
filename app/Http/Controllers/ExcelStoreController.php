<?php

namespace RZP\Http\Controllers;

use Request;
use Requests;
use ApiResponse;
use Illuminate\Http\Response;
use Response as ResponseFactory;


use RZP\Trace\TraceCode;

class ExcelStoreController extends Controller
{
    /**
     * Forwards request from <API>/excel_store/* to <excel_store>/* and returns the response
     *
     * @param Request $path
     * @return Response
     */
    public function any($path)
    {
        $method = Request::method();
        $input = Request::all() ?: null;
        $excelStoreEndPoint = config('services.excel_store.base_url') . $path;
        $excelStoreAuthToken = config('services.excel_store.secret');
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $excelStoreAuthToken,
        ];

        $this->trace->info(
            TraceCode::EXCEL_STORE_REQUEST,
            compact('method', 'input', 'excelStoreEndPoint')
        );

        $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        $body = '';
        $respHeaders = [];

        try
        {
            $resp = Requests::request(
                $excelStoreEndPoint,
                $headers,
                json_encode($input),
                $method
            );

            $code = $resp->status_code;
            $body = json_decode($resp->body, true);

            $respHeaders = $resp->headers->getAll();
        } catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::EXCEL_STORE_ERROR,
                compact('excelStoreEndPoint', 'headers', 'input', 'method')
            );
        }

        $this->trace->info(
            TraceCode::EXCEL_STORE_RESPONSE,
            compact('code', 'body', 'respHeaders')
        );

        $data = $body;

        if(is_associative_array($body) === false) {
            $data = [ 'items' => $body ];
        }

        return ApiResponse::json($data);
    }
}