<?php

namespace RZP\Http\Middleware;

use Request;
use Closure;
use ApiResponse;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Foundation\Application;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class ExcelStoreProxy
{
    const CONTENT_TYPE_MULTIPART = 'multipart/form-data';
    const CONTENT_TYPE_JSON      = 'application/json';
    const CONTENT_TYPE_EXCEL     = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct(Application $app)
    {

      $this->app = $app;

      $this->trace = $this->app['trace'];

      $this->route = $this->app['api.route'];

      $this->config = config('services.excel_store');

      $this->request = $this->initRequest();

      $this->options = [
        'headers' => $this->getDefaultHeaders($this->config)
      ];

    }

    protected function getDefaultHeaders(): array
    {
      $excelStoreAuthToken = $this->config['secret'];

      return [
        'Accept'            => 'application/json',
        'Authorization'     => 'Bearer ' . $excelStoreAuthToken,
      ];
    }

    protected function initRequest(): Guzzle
    {

      $excelStoreUrl = $this->config['base_url'];

      $request = new Guzzle();

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

        $response = $this->forwardExcelStoreRequest($request);

        return $response;
    }

    protected function  forwardExcelStoreRequest($request)
    {
        // removing /v1/excel-store/ from path
        $path = str_replace('v1/excel-store/', '', $request->path());

        $this->options['headers']['Content-Type'] = $this->getContentType($request);

        $this->processInput($request);

        $method = $request->method();

        $response = $this->sendRequestAndParseResponse($path, $method);

        return $response;

    }

    protected function getContentType($request)
    {
        $requestContentType = $request->header('Content-Type');

        if(strpos($requestContentType, self::CONTENT_TYPE_MULTIPART) !== false)
        {
            return self::CONTENT_TYPE_EXCEL;
        }

        return self::CONTENT_TYPE_JSON;
    }

    protected function processInput($request)
    {
        $contentTypeToBeForwarded = $this->options['headers']['Content-Type'];

        $input = $request->all();

        switch($contentTypeToBeForwarded)
        {
            case self::CONTENT_TYPE_JSON:
                $this->options['json'] = $input;

                break;

            case self::CONTENT_TYPE_EXCEL:
                $file = $input['file'];

                if(!($file instanceof \SplFileInfo))
                {
                    // need to raise an exception
                }

                $filePath = $file->getRealPath();

                $this->options['body'] = fopen($filePath, 'r');

                break;
        }

    }

    protected function sendRequestAndParseResponse(
      $path,
      $method)
    {
        $excelStoreUrl = $this->config['base_url'];

        $requestUrl = $excelStoreUrl . $path;

        try
        {
            $response = $this->request
                             ->request($method, $requestUrl, $this->options);

            return $this->parseResponse($response);
        }
        catch(\Request_Exception $e)
        {
          throw new Exception\ServerErrorException(
            $e->getMessage(),
            ErrorCode::SERVER_ERROR_EXCEL_STORE_FAILURE);
        }

    }

    protected function parseResponse($response)
    {
        $code = $response->getStatusCode();

        $body = json_decode(((string) $response->getBody()), true);

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
