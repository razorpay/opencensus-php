<?php


namespace RZP\Mail\Base;

use App;
use Razorpay\Trace\Logger;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Constants\Product;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

/**
 * Class Stork
 * @package RZP\Mail\Base
 *
 * @see \RZP\Services\Stork
 */
class Stork
{
    protected $app;

    /**
     * @var string
     */
    protected $mode;

    /**
     * Product value is used to figure out service value to use for stork communication.
     * @var string
     */
    protected $product;

    /**
     * @var \RZP\Services\Stork
     */
    protected $service;

    /**
     * @var Logger
     */
    protected $trace;

    const SEND_EMAIL_ROUTE = '/twirp/rzp.stork.email.v1.EmailAPI/Send';
    const GET_PRESIGNED_URL_ROUTE = '/twirp/rzp.stork.attachment.v1.AttachmentAPI/GetPresignedURL';

    public function __construct(string $mode = Mode::LIVE, string $product = Product::PRIMARY)
    {

        $this->app     = App::getFacadeRoot();
        $this->mode    = $mode;
        $this->product = $product;
        $this->service = app('stork_service');
        $this->trace   = app('trace');

        $this->service->init($this->mode, $this->product);
    }

    public function sendEmail(array $payload): array
    {
        $payload['service'] = $this->service->service;

        // adding defaults
        if (empty($payload['from']) === true)
        {
            $mailConfig = $this->app->make('config')->get('mail');
            $payload['from'] = $mailConfig['from'];
        }
        $payload['owner_id']   = (empty($payload['owner_id']) === true)   ? '10000000000000' : $payload['owner_id'];
        $payload['owner_type'] = (empty($payload['owner_type']) === true) ? 'merchant'       : $payload['owner_type'];

        $res = $this->service->request(self::SEND_EMAIL_ROUTE, $payload);

        return json_decode($res->body, true);
    }

    protected function getPresignedURL(array $payload): array
    {
        $payload['service'] = $this->service->service;

        // adding defaults
        $payload['channel']   = (empty($payload['channel']) === true)   ? 'email' : $payload['channel'];
        $payload['owner_id']   = (empty($payload['owner_id']) === true)   ? '10000000000000' : $payload['owner_id'];
        $payload['owner_type'] = (empty($payload['owner_type']) === true) ? 'merchant'       : $payload['owner_type'];
        $payload['url_count'] = (empty($payload['url_count']) === true) ? "1"       : $payload['url_count'];

        $res = $this->service->request(self::GET_PRESIGNED_URL_ROUTE, $payload);

        return json_decode($res->body, true);
    }

    public function getFileId($paramsPayload,$path)
    {
        $app   = \App::getFacadeRoot();
        $trace = $app['trace'];
        $response = [];

        try
        {
            $response = $this->getPresignedURL($paramsPayload);
        }
        catch (\Throwable $e)
        {
            $trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::GET_PRESIGNED_URL_EXCEPTION,
                ['params' => $paramsPayload]
            );
            return null; // Don't proceed if failed
        }
        return  $this->service->sendRequest($response,$path);
    }
}
