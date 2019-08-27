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

      $this->requestToExcelStore = new Guzzle();

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

  /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request)
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

        $requestUrl = $this->getRequestUrl($path);

        $incomingRequestContentType = $request->header('Content-Type');

        $outgoingRequestContentType = $this->getOutgoingRequestContentType(
          $incomingRequestContentType
        );

        $this->options['headers']['Content-Type'] = $outgoingRequestContentType;

        $input = $request->all();

        $this->options = array_merge(
          $this->options,
          $this->insertInputIntoOptions($input, $outgoingRequestContentType)
        );

        $method = $request->method();

        $response = $this->sendRequestAndParseResponse($requestUrl, $method);

        return $response;

    }

    protected function getRequestUrl($path)
    {
      $excelStoreUrl = $this->config['base_url'];

      $requestUrl = $excelStoreUrl . $path;

      return $requestUrl;
    }

    protected function getOutgoingRequestContentType($incomingRequestContentType)
    {

        if(strpos($incomingRequestContentType, self::CONTENT_TYPE_MULTIPART) !== false)
        {
            return self::CONTENT_TYPE_EXCEL;
        }

        return self::CONTENT_TYPE_JSON;
    }

    protected function insertInputIntoOptions($input, $outgoingRequestContentType)
    {
        switch($outgoingRequestContentType)
        {
            case self::CONTENT_TYPE_JSON:
              return $this->processJsonInput($input);

            case self::CONTENT_TYPE_EXCEL:
              return $this->processFileInput($input);
        }
    }

    protected function processJsonInput($input)
    {
      return [
        'json'    => $input,
      ];
    }

    protected function processFileInput($input)
    {
      $file = $input['file'];

      if (!($file instanceof \SplFileInfo))
      {
          throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_EXCEL_STORE_FILE_PARAM
          );
      }

      $filePath = $file->getRealPath();

      return [
        'body'    => fopen($filePath, 'r'),
      ];

    }

    protected function sendRequestAndParseResponse(
      $requestUrl,
      $method)
    {

        try
        {
            $response = $this->requestToExcelStore
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

        // excel store does not send response in items array
        // according to standard practise reponse containing collecitons
        // are sent in [items] key
        if(is_associative_array($body) === false)
        {
            $data = ['items' => $body];
        }

        return ApiResponse::json($data, $code);
    }
}
