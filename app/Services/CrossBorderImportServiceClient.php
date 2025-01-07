<?php

namespace RZP\Services;
use GuzzleHttp\Exception\RequestException;
use Request;
use GuzzleHttp\RequestOptions;
use RZP\Constants\Mode;
use RZP\Http\RequestHeader;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\App;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Models\Payment\Service as PaymentService;
use RZP\Error\ErrorCode;

class CrossBorderImportServiceClient
{
    const CONTENT_TYPE        = 'content-type';
    const CONTENT_TYPE_JSON   = 'application/json';
    const X_TASK_ID           = 'X-Razorpay-TaskId';
    const X_MERCHANT_ID       = 'X-Merchant-ID';
    const X_INTERNAL_APP      = 'X-Internal-App';
    const X_RAZORPAY_MODE     = 'X-Razorpay-Mode';
    const CROSS_BORDER_IMPORT = 'CrossBorderImport';

    //validate payment url
    const VALIDATE_PAYMENT = 'twirp/rzp.cross_border_import.import_payments.v1.ImportPaymentService/ValidateAndSavePayment';

    const GET = 'GET';
    const POST = 'POST';

    protected $client;

    protected $options = [];

    protected $trace;

    protected $config;

    protected $mode;

    protected $app;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.cross_border_import_service');

        $this->client = new Guzzle([
            'base_uri' => $this->config['url'][$this->mode],
            'auth'     => [
                $this->config['username'],
                $this->config['password'],
            ]]);
    }

    /**
     *
     * @return \WpOrg\Requests\Response
     * @throws \WpOrg\Requests\Exception
     */
    public function makeRequest($path, $method, $payload=[], $headers = [])
    {
        $url = $this->config['url'][$this->mode] . $path;

        $headers = array_merge($headers, $this->getRequestHeaders());

        $this->options = [
            'headers' => $headers,
        ];
        if (isset($payload))
        {
            if ($method === Requests::GET)
            {
                $url = $url . '?' . http_build_query($payload);
            }
            else
            {
                $this->options[RequestOptions::JSON] = $payload;
            }
        }


        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
            'url'           => $url,
            'service'       => self::CROSS_BORDER_IMPORT,
            'payload'       => $payload,
            'headers'       => $this->options['headers'],
        ]);

        try
        {
            $response = $this->client->request($method, $url, $this->options);

            return $this->formatResponse($response);
        }
        catch (RequestException $e) {

            if ($e->hasResponse())
            {
                if ($e->getResponse()->getStatusCode() == '400')
                {
                    $resp = $this->formatResponse($e->getResponse());
                    $this->trace->error(TraceCode::CROSS_BORDER_IMPORT_INTEGRATION_ERROR, [
                        'error_message' => $resp,
                        'url'           => $url,
                    ]);

                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null,
                        $resp['meta']['description']
                    );
                }
            }

            throw $e;
        }
    }

    private function getRequestHeaders()
    {
        $headers = [
            self::CONTENT_TYPE      => self::CONTENT_TYPE_JSON,
            self::X_RAZORPAY_MODE   => $this->mode
        ];

        if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))){
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

        return $headers;
    }

    private function formatResponse($response)
    {
        $responseArray = json_decode($response->getBody(), true);

        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_RESPONSE, [
            'response'  => $responseArray,
            'service'   => self::CROSS_BORDER_IMPORT,
        ]);

        return $responseArray;
    }

    public function validateImportPayment($input)
    {
        $url = self::VALIDATE_PAYMENT;

        $params = [
            'amount' => $input['amount'],
            'customer_id' => $input['customer_id'],
            'currency' => $input['currency'],
            'international' => $input['international'],
            'method' => $input['method'],
            'notes' => $input['notes'],
            'order_id' => $input['order_id'],
            'merchant_id' => $input['merchant_id'],
            'payment_id' => $input['id'],
            'recurring' => $input['recurring'],
            'library' => (new PaymentService)->getLibraryFromPayment($input)
        ];

        try {
            return $this->makeRequest($url, self::POST, $params);
        } catch (\Throwable $e) {
            $this->trace->info(TraceCode::CROSS_BORDER_IMPORT_VALIDATE_PAYMENT_ERROR,[
                'error' => $e,
            ]);

            throw $e;
        }
    }

}


