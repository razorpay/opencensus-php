<?php

namespace RZP\Http\Middleware;

use Closure;
use ApiResponse;
use Requests_Session;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class ExcelStoreProxy
{
    public function __construct(Application $app)
    {

      $this->app = $app;

      $this->trace = $this->app['trace'];

      $this->route = $this->app['api.route'];

      $this->request = $this->initRequest();

    }

    protected function initRequest(): Requests_Session
    {
      $config = config('services.excel_store');

      $excelStoreUrl = $config['base_url'];

      $excelStoreAuthToken = $config['secret'];

      $headers = [
        'Content-Type'      => 'application/json',
        'Authorization'     => 'Bearer ' . $excelStoreAuthToken,
      ];

      $request = new Requests_Session($excelStoreUrl, $headers);

      return $request;

    }

  /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->trace->info(TraceCode::EXCEL_STORE_REQUEST, [
          'request'       => $request->path(),
          'method'        => $request->method(),
          'body'          => $request->post(),
          'query_string'  => $request->getQUeryString(),
        ]);

        $res = $this->forwardExcelStoreRequest($request);

        return $res;
    }

    protected function  forwardExcelStoreRequest($request)
    {
      $url = str_replace('v1/excel-store/', '', $request->path());

      if($request->post() !== null)
      {
        $body = $request->post();
      }

      $method = $request->method();

      $response = $this->sendRequestAndParseResponse($url, $method, $body);

      return $response;

    }

    protected function sendRequestAndParseResponse(
      $url,
      $method,
      $body = [],
      $headers = [])
    {
        try
        {
          $response = $this->request->request(
            $url,
            $headers,
            $body,
            $method);
        }
        catch(\Request_Exception $e)
        {
          throw new Exception\ServerErrorException(
            $e->getMessage(),
            ErrorCode::SERVER_ERROR_EXCEL_STORE_FAILURE);
        }

        return $this->parseResponse($response);
    }

    protected function parseResponse($response)
    {
      $code = $response->status_code;
      $body = json_decode($response->body, true);

      $this->trace->info(
        TraceCode::EXCEL_STORE_RESPONSE,
        [ 'code' => $code, 'body' => $body ]);

        $data = $body;

      if(is_associative_array($body) === false)
      {
        $data = ['items' => $body];
      }

      return ApiResponse::json($data, $code);
    }
}
