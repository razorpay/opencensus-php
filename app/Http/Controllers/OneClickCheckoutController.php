<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Trace\TraceCode;
use RZP\Constants\Environment;
use RZP\Models\Merchant\OneClickCheckout\Shopify;

class OneClickCheckoutController extends Controller
{

    /**
     * Creates and returns the Rzp order_id for a Shopify checkout
     * For APIs from merchant website, the content type is "text/plain"
     */
    public function shopifyCreateCheckout()
    {
        $rawContents = Request::getContent();
        $headers = Request::header();
        $contentType = $headers['content-type'][0];
        $bodyJSON = [];

        if ($contentType === 'text/plain')
        {
            $bodyJSON = $this->parseToJSONIfApplicable($rawContents, $contentType);
        }
        else
        {
            $bodyJSON = Request::all();
        }

        $result = (new Shopify\Service)->shopifyCreateCheckout($bodyJSON);

        $response = ApiResponse::json($result, 200);

        $this->addCorsHeaders($response, 'POST, OPTIONS');

        return $response;
    }

    public function shopifyGetCheckoutOptions()
    {
        $input = Request::all();

        $result = (new Shopify\Service)->shopifyGetCheckoutOptions($input);

        $response = ApiResponse::json($result, 200);

        $this->addCorsHeaders($response, 'GET, OPTIONS');

        return $response;
    }

    public function shopifyCompleteCheckout()
    {
        $rawContents = Request::getContent();
        $headers = Request::header();
        $bodyJSON = $this->parseToJSONIfApplicable($rawContents, $headers['content-type'][0]);

        $result = (new Shopify\Service())->completeCheckoutWithLock($bodyJSON);

        $response = ApiResponse::json($result, 200);

        $this->addCorsHeaders($response, 'POST, OPTIONS');

        return $response;
    }

    public function shopifyOAuthRedirect()
    {
        $input = Request::all();

        $response = (new Shopify\Service)->shopifyOAuthRedirect($input);

        return ApiResponse::json($response, 200);
    }

    public function shopifyUpdateCheckout()
    {
        $rawContents = Request::getContent();
        $headers = Request::header();
        $bodyJSON = $this->parseToJSONIfApplicable($rawContents, $headers['content-type'][0]);

        $result = (new Shopify\Service)->updateCheckout($bodyJSON);
        $response = ApiResponse::json($result, 200);

        $this->addCorsHeaders($response, 'POST, OPTIONS');

        return $response;
    }

    public function shopifyUpdateCheckoutUrl()
    {
        $rawContents = Request::getContent();
        $headers = Request::header();
        $bodyJSON = $this->parseToJSONIfApplicable($rawContents, $headers['content-type'][0]);

        $result = (new Shopify\Service)->updateCheckoutUrl($bodyJSON);

        $response = ApiResponse::json($result, 200);

        $this->addCorsHeaders($response, 'POST, OPTIONS');

        return $response;
    }

    public function allowCors(string $methods = '')
    {
        $methods = $methods === '' ? 'OPTIONS' : $methods;

        $response = ApiResponse::json([], 200);

        $this->addCorsHeaders($response, $methods);

        return $response;
    }

    protected function addCorsHeaders($response, string $methods): void
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        $response->headers->set('Access-Control-Allow-Methods', $methods);
    }

    /**
     * Simple requests will send payload as a string so we need to parse it
     * @param array|string $body - Raw contents from the request
     * @param string $contentType - contentType from the header
     * @return array $body - Body as an array
     */
    protected function parseToJSONIfApplicable($body, string $contentType): array
    {
        $result = 'skip';
        if (gettype($body) === 'string')
        {
            $result = 'success';
            $body = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE)
            {
                $result = 'fail';
            }
        }
        $this->trace->info(
          TraceCode::SHOPIFY_1CC_PARSE_REQUEST_BODY_RESULT,
          [
            'type'         => 'parse_request_body',
            'input'        => $body,
            'result'       => $result,
            'content_type' => $contentType,
          ]);
        return $body;
    }
}
