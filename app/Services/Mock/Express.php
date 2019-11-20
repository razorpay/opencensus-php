<?php


namespace RZP\Services\Mock;

use Requests;
use RZP\Trace\TraceCode;
use RZP\Services\Express as BaseExpress;

class Express extends BaseExpress
{
    protected function sendRequest(string $path, string $content, string $method = Requests::POST): \Requests_Response
    {
        $this->trace->info(TraceCode::EXPRESS_SERVICE_REQUEST, [
            self::REQUEST => [
                self::URL           => $path,
                self::CONTENT       => $content,
            ],
        ]);

        $response = new \Requests_Response();

        $response->status_code = 200;

        $response->headers['request-id'] = '123456';

        $response->body = '{"data":{"eventSubType":null,"eventTime":"2019-21-10T04:50:03+00:00","eventType":"PAYMENT_TRANSACTION.COMPLETED","resourceExternalId":null,"resourceId":"pay_DWauYvtJ2KeSut","resourceType":"payment"}}';

        return $response;
    }
}
