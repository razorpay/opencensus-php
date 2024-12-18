<?php

namespace RZP\Services;

use App;
use Request;
use RZP\Constants\Mode;
use RZP\Error\Error;
use RZP\Error\ErrorClass;
use RZP\Exception;
use RZP\Models\Offer;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Http\RequestHeader;
use RZP\Models\Offer\Constants;
use RZP\Models\Offer\Metric;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;

class OffersEngine
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $mode;

    protected $request;

    protected $headers;

    protected $auth;

    protected $userType;

    protected $merchantId;

    private $offersEngine;

    // Headers
    const ACCEPT            = 'Accept';
    const CONTENT_TYPE      = 'Content-Type';
    const X_TASK_ID         = 'X-Task-Id';
    const X_PASSPORT_JWT_V1 = 'X-Passport-JWT-V1';

    const ADMIN = 'admin';

    const DEFAULT_REQUEST_TIMEOUT   = 60;

    // Offers Engine APIs
    const OffersEngineCreateOffer = 'v1/offers';

    const OffersEngineAdminCreateOffer = 'v1/admin/offers';

    const OffersEngineUpdateOffer = 'v1/offers/%s';

    const OffersEngineAdminUpdateOffer = 'v1/admin/offers/%s';

    const OffersEngineGetOfferByID = 'v1/offers/%s';

    const OffersEngineAdminGetOffers = 'v1/admin/offers';

    const OffersEngineGetOffers = 'v1/offers';

    const OffersEngineAvail = 'v1/offers/txn/avail';

    const OffersEngineRedeem = 'v1/offers/txn/redeem';

    const OffersEngineFailed = 'v1/offers/txn/failed';

    const ValidateOffer = 'v1/offers/validate';

    // Requests/responses will be logged by default or if value for path mentioned here is true.
    const REQUEST_LOGGER_MAP = [
        Requests::POST => true,

        Requests::GET => false,

        Requests::PATCH => true,
    ];

    const RESPONSE_LOGGER_MAP = [];

    /*
     * This map stores routes and their methods for which
     * user_type and user_id would be set as admin
     * before sending a request to Offers Engine
     */
    const ADMIN_ROUTES = [
        self::OffersEngineAdminGetOffers => Requests::GET,
    ];

    /**
     * Offers Engine constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.offers_engine');

        $this->mode = (isset($app['rzp.mode']) === true) ? $app['rzp.mode'] : Mode::LIVE;

        $this->baseUrl = $this->config['base_url'][$this->mode];

        $this->request = $app['request'];

        $this->key = $this->config['offers_engine_username'][$this->mode];

        $this->secret = $this->config['offers_engine_password'][$this->mode];

        $this->auth = app('basicauth');

        $this->offersEngine = new \RZP\Models\Offer\OffersEngine();

        $this->setHeaders();

        $this->userType = 'advertiser';
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @param int $timeout
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     * @throws \Throwable
     */
    public function sendRequest(
        string $endpoint,
        string $method,
        array $data = [],
        bool $throwExceptionOnFailure = true,
        int $timeout = self::DEFAULT_REQUEST_TIMEOUT)
    {
        $request = $this->generateRequest($endpoint, $method, $data, $timeout);

        return $this->sendOffersEngineRequest($request, $endpoint, $throwExceptionOnFailure);
    }

    public function shouldLogResponse(string $endpoint, string $method) :bool
    {
        $mapKey = $method.'_'.$endpoint;
        $logResponse = true;
        if(isset(self::RESPONSE_LOGGER_MAP[$mapKey]))
        {
            $logResponse = self::RESPONSE_LOGGER_MAP[$mapKey];
        }
        return $logResponse;
    }

    /**
     * Function used to set headers for the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]        = 'application/json';
        $headers[self::CONTENT_TYPE]  = 'application/json';
        $headers[self::X_TASK_ID]     = $this->app['request']->getTaskId();

        $headers['X-User-Id'] = $this->merchantId;

        $headers['X-User-Type'] = $this->userType;

        $headers['X-Api-Decomp'] = 'shadow';

        if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))){
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

        $this->headers = $headers;
    }

    /**
     * @param array $request
     * @param string $endpoint
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     * @throws \Throwable
     */
    protected function sendOffersEngineRequest(array $request, string $endpoint, bool $throwExceptionOnFailure = true)
    {
        $statusCode = null;

        $this->traceRequest($request);

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);

            $parsedResponse = $this->parseAndReturnResponse($response);

            $statusCode = $response->status_code;

            $logResponse = $this->shouldLogResponse($endpoint, $request['method']);

            if($logResponse === true)
            {
                $this->trace->info(TraceCode::OFFERS_ENGINE_RESPONSE,
                    [
                        "response" => $parsedResponse ?? [],
                        "statusCode" => $statusCode,
                    ]);
            }

            return $this->checkAndParseError($parsedResponse, $response->status_code, $throwExceptionOnFailure);
        }
        catch(\Throwable $e)
        {
            $this->trace->count(
                Metric::OFFERS_ENGINE_REQUEST_FAILURE, [
                'route'       => app('api.route')->getCurrentRouteName(),
                'endpoint'    => $endpoint,
                'status_code' => $statusCode,
            ]);

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::OFFERS_ENGINE_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage(),
                    'url'  => $request['url'],
                ]);

            throw $e;
        }
    }

    /**
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    public function checkAndParseError($response, $statusCode, bool $throwExceptionOnFailure = false, bool $nullResponseAllowed = false): array
    {
        if ($statusCode === 200)
        {
            if (($response === null && $nullResponseAllowed) || ($response !== null))
            {
                return $response;
            }
            else
            {
                throw new Exception\ServerErrorException("Offers Engine Response cannot be null. Mode: $this->mode",
                    ErrorCode::SERVER_ERROR_OFFERS_ENGINE_SERVICE_FAILURE);
            }

        }

        if (in_array($response->status_code, [503], true) === true)
        {
            throw new Exception\ServerErrorException("Offers Engine Service is unreachable. Mode: $this->mode",
                ErrorCode::SERVER_ERROR_OFFERS_ENGINE_SERVICE_FAILURE);
        }


        $formattedResponse = [
            'Mode' => $this->mode
        ];

        if (isset($response['message']))
        {
            $error = [
                'internal_error_code' => $response['message'],
                'code' => $response['details'][0]['code']
            ];

            $formattedResponse['error'] = $error;
        }

        if ($throwExceptionOnFailure === false)
        {
            return $formattedResponse;
        }

        if ($statusCode === 400)
        {
            if ($response['message'] === "BAD_REQUEST_ENTITY_NOT_FOUND")
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_EXTERNAL_OFFER_NOT_FOUND, null, $formattedResponse);
            }
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, $formattedResponse);
        }

        else if ($statusCode > 400)
        {
            $traceCode = TraceCode::OFFERS_ENGINE_REQUEST_FAILURE;

            if ($response['message'] === "SERVER_ERROR_DB_FETCH_ERROR")
            {
                $traceCode = TraceCode::OFFERS_ENGINE_ID_NOT_FOUND;
            }

            throw new ServerErrorException(
                $traceCode,
                ErrorCode::SERVER_ERROR,
                $formattedResponse
            );
        }

        return $formattedResponse;
    }

    protected function parseAndReturnResponse($response)
    {
        $responseArray = json_decode($response->body, true);

        return $responseArray ?? [];
    }

    /**
     * @param array $request
     */
    protected function traceRequest(array $request)
    {
        $logRequest = $this->shouldLogRequest($request['method']);

        if($logRequest === true)
        {
            $traceRequest = $request;

            $traceRequest['mode'] = $this->mode;

            unset($traceRequest['options']['auth']);

            unset($traceRequest['headers'][self::X_PASSPORT_JWT_V1]);

            $this->trace->info(TraceCode::OFFERS_ENGINE_REQUEST, $traceRequest);
        }
    }

    public function shouldLogRequest(string $method) :bool
    {
        $logRequest = false;

        $mapKey = $method;

        if(isset(self::REQUEST_LOGGER_MAP[$mapKey]))
        {
            $logRequest = self::REQUEST_LOGGER_MAP[$mapKey];
        }

        return $logRequest;
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $data, int $timeout): array
    {
        $url = $this->baseUrl . $endpoint;

        // json encode if data is must, else ignore
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $options = [
            'timeout' => $timeout,
            'auth'    => [
                $this->key,
                $this->secret
            ],
        ];

        $this->setHeaders();

        $headers = $this->headers;

        foreach (self::ADMIN_ROUTES as $adminRoute => $adminMethod)
        {
            if ((str_starts_with($endpoint, $adminRoute) === true) and
                ($method === $adminMethod))
            {
                $headers['X-User-Id']   = self::ADMIN;
                $headers['X-User-Type'] = self::ADMIN;

                break;
            }
        }

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $headers,
            'options'   => $options,
            'content'   => $data
        ];
    }

    /**
     * @throws \Exception|\Throwable
     */
    public function createOffer(array $input)
    {
        $this->userType = 'advertiser';
        $this->merchantId = $input['offer']['metadata']['advertiser_id'];
        return $this->sendRequest(self::OffersEngineCreateOffer, Requests::POST, $input);
    }

    public function adminCreateOffer(array $input)
    {
        $this->userType = 'advertiser';
        $this->merchantId = $input['offer']['metadata']['advertiser_id'];
        return $this->sendRequest(self::OffersEngineAdminCreateOffer, Requests::POST, $input);
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function updateOffer($id, array $input)
    {
        $this->userType = 'advertiser';
        $this->merchantId = $input['offer']['metadata']['advertiser_id'];
        $endpoint = sprintf(self::OffersEngineUpdateOffer, $id);

        return $this->sendRequest($endpoint, Requests::PATCH, $input);
    }

    public function adminUpdateOffer($id, array $input)
    {
        $this->userType = 'advertiser';
        $this->merchantId = $input['offer']['metadata']['advertiser_id'];
        $endpoint = sprintf(self::OffersEngineAdminUpdateOffer, $id);

        return $this->sendRequest($endpoint, Requests::PATCH, $input);
    }

    /**
     * @param string $id
     * @param string $merchantId
     * @param array $input
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     * @throws \Throwable
     */
    public function fetch(string $entity, string $id, string $merchantId, array $input)
    {
        $this->userType = 'publisher';

        $this->merchantId = 'rzp.merchant.' . $merchantId;

        $endpoint = sprintf(self::OffersEngineGetOfferByID, $id);

        $endpoint = $endpoint . '?publisher_id=' . $this->merchantId;

        $response = $this->sendRequest($endpoint, Requests::GET);

        if (empty($response) === false)
        {
            // GetOfferByID has response key offer_publishers
            $response[Constants::PUBLISH] = $response[Constants::OFFER_PUBLISHERS][0];

            return $this->offersEngine->convertOffersEngineResponseToEntityOffer($response)[Constants::OFFER];
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID, null, [
            'id'         => $id,
            'merchant_id' => $this->merchantId
        ]);

    }

    /*
     * This function retrieves offer and the latest associated offer publisher from the
     * offer engine based on the parameters provided in the request.
     * This API operates without requiring publisher or advertiser context in the request but
     * relies on the admin context to interact with the Offers engine.
     */
    public function fetchAdminOfferById(string $id, array $input)
    {
        $endpoint = self::OffersEngineAdminGetOffers;

        $input['channel'] = Constants::CHANNEL_RZP_CHECKOUT;

        $input['page_size'] = $input['page_size'] ?? 1;

        $input['page'] = $input['page'] ?? 1;

        if (empty($input) === false)
        {
            $endpoint .= "?".http_build_query($input);
        }

        if (empty($id) === false)
        {
            $endpoint .= '&offer_ids=' . 'offer_' . Offer\Entity::silentlyStripSign($id);
        }

        $response = $this->sendRequest($endpoint, Requests::GET);

        if (empty($response) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID, null, [
                'id'         => $id,
            ]);
        }

        $offerEntity = null;

        foreach ($response[Constants::OFFERS] as $offer)
        {
            $convertedResponse = [];
            $filter = false;
            foreach ($response[Constants::OFFER_PUBLISHERS] as $offerPublisher)
            {
                if ('offer_' . $offerPublisher[Constants::OFFER_ID] === $offer[Constants::METADATA][Constants::OFFER_ID])
                {
                    $filter = true;

                    $convertedResponse = $this->offersEngine->convertOffersEngineResponseToEntityOffer(
                        [
                            Constants::OFFER   => $offer,
                            Constants::PUBLISH => $offerPublisher,
                        ]);
                    // prefer the first offer_publisher associated with the offer.
                    break;
                }
            }
            if ($filter === true)
            {
                $offerEntity = $convertedResponse[Constants::OFFER];
            }
            break;
        }

        // Possibility of a misconfigured offer which doesn't have a offer publisher in offers engine
        if ($offerEntity === null)
        {
            $this->trace->error(TraceCode::OFFER_MISCONFIGURED_IN_OFFERS_ENGINE, [
                'offer_id' => $id,
            ]);

            app('trace')->count(Offer\Metric::OFFERS_ENGINE_FETCH_BY_ID_INVALID_RESPONSE, [
                'route' => app('api.route')->getCurrentRouteName(),
            ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID, null, [
                'id' => $id,
            ]);
        }

        return $offerEntity;
    }

    /*
     * This function retrieves latest offers and the aggregated data of offer publishers from the
     * offer engine based on the parameters provided in the request.
     * This API operates without requiring publisher or advertiser context in the request but
     * relies on the admin context to interact with the Offers engine.
     */
    public function fetchAdminOfferBulk(array $input)
    {
        if (isset($input['merchant_id']) === true)
        {
            $input['publisher_id'] = 'rzp.merchant.' . $input['merchant_id'];

            unset($input['merchant_id']);
        }

        $input['channel'] = Constants::CHANNEL_RZP_CHECKOUT;

        $input['page_size'] = $input['page_size'] ?? 20;

        $input['page'] = $input['page'] ?? 1;

        $endpoint = self::OffersEngineAdminGetOffers;

        if (empty($input) === false)
        {
            $endpoint .= "?".http_build_query($input);
        }

        $response = $this->sendRequest($endpoint, Requests::GET);

        if (empty($response) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY, null, [
                'input'       => $input
            ]);
        }

        $convertedOffers = [];

        // for each offer<>offer_publisher pair, convert into API offer and filter based on input
        foreach ($response[Constants::OFFERS] as $offer)
        {
            $convertedResponse = [];
            $filter = false;
            foreach ($response[Constants::OFFER_PUBLISHERS] as $key => $offerPublisher)
            {
                if ('offer_' . $offerPublisher[Constants::OFFER_ID] === $offer[Constants::METADATA][Constants::OFFER_ID])
                {
                    $filter = true;

                    $convertedResponse = $this->offersEngine->convertOffersEngineResponseToEntityOffer(
                        [
                            Constants::OFFER   => $offer,
                            Constants::PUBLISH => $offerPublisher,
                        ]);

                    unset($response[Constants::OFFER_PUBLISHERS][$key]);

                    break;
                }
            }
            if ($filter === true)
            {
                $convertedOffers[] = $convertedResponse[Constants::OFFER];
            }
        }

        return $convertedOffers;
    }

    /**
     * @param string $merchantId
     * @param array $ids
     * @param array $input
     * @return array
     * @throws \Throwable
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    public function fetchBulk(string $merchantId, array $ids = [], array $input = [])
    {
        $this->userType = 'publisher';

        $this->merchantId = 'rzp.merchant.' . $merchantId;

        $input['publisher_id'] = $this->merchantId;

        $input['channel'] = Constants::CHANNEL_RZP_CHECKOUT;

        // set pages if no ids in input
        if (sizeof($ids) === 0)
        {
            $input['page_size'] = 200;
            $input['page'] = 1;
        }


        $endpoint = self::OffersEngineGetOffers;

        if (empty($input) === false)
        {
            $endpoint .= "?".http_build_query($input);
        }

        if (empty($ids) === false)
        {
            foreach ($ids as $id)
            {
                $endpoint .= '&offer_ids=' . 'offer_' . \RZP\Models\Offer\Entity::silentlyStripSign($id);
            }
        }

        $response = $this->sendRequest($endpoint, Requests::GET);

        if (empty($response) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY, null, [
                'ids'         => $ids,
                'merchant_id'  => $this->merchantId,
                'input'       => $input
            ]);
        }

        $convertedOffers = [];

        // for each offer<>offer_publisher pair, convert into API offer and filter based on input
        foreach ($response[Constants::OFFERS] as $offer)
        {
            $convertedResponse = [];
            $filter = false;
            foreach ($response[Constants::OFFER_PUBLISHERS] as $offerPublisher)
            {
                if ('offer_' . $offerPublisher[Constants::OFFER_ID] === $offer[Constants::METADATA][Constants::OFFER_ID])
                {
                    $filter = true;
                    $convertedResponse = $this->offersEngine->convertOffersEngineResponseToEntityOffer([
                        Constants::OFFER => $offer,
                        Constants::PUBLISH => $offerPublisher,
                    ]);
                    // no need to check other offer_publishers
                    break;
                }
            }
            if ($filter === true)
            {
                $convertedOffers[] = $convertedResponse[Constants::OFFER];
            }

        }
        return $convertedOffers;
    }
    public function avail(string $merchantId, $input)
    {
        $this->userType = 'publisher';

        $this->merchantId = 'rzp.merchant.' . $merchantId;

        return $this->sendRequest(self::OffersEngineAvail, Requests::POST, $input);

    }

    public function redeem(string $merchantId, $input)
    {
        $this->userType = 'publisher';

        $this->merchantId = 'rzp.merchant.' . $merchantId;

        return $this->sendRequest(self::OffersEngineRedeem, Requests::POST, $input);
    }

    public function failPayment(string $merchantId, $input)
    {
        $this->userType = 'publisher';

        $this->merchantId = 'rzp.merchant.' . $merchantId;

        return $this->sendRequest(self::OffersEngineFailed, Requests::POST, $input);
    }

    /**
     * @throws \Throwable
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    public function validateOffer(string $merchantId, array $input)
    {
        $this->merchantId = 'rzp.merchant.' . $merchantId;

        $this->userType = 'publisher';

        $input['publisher_id'] = $this->merchantId;

        $input['channel'] = Constants::CHANNEL_RZP_CHECKOUT;

        $response = $this->sendRequest(self::ValidateOffer, Requests::POST, $input, false);

        if (isset($response['error']))
        {
            if (in_array($response["error"]["internal_error_code"],
                    ["BAD_REQUEST_NO_ACTIVE_OFFERS_FOUND",
                    "BAD_REQUEST_MISSING_FACT"], true) === true ||
            $response['error']['description'] === "No Active offers found")
            {
                return $response;
            }
            else
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE, null, [
                    'merchant_id' => $this->merchantId,
                    'input'       => $input,
                    'error'       => $response['error']['internal_error_code']
                ]);
            }
        }

        return [
            'offer_id' => $input['offer_id'],
            'offer_benefits' => $response['offer']['offer_benefits'][0],
            'calculated_benefits' => $response['offer']['calculated_benefits'][0],
        ];
    }
}
