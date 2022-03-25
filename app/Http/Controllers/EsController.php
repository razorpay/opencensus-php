<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Http\Request\Requests;
use ApiResponse;
use Illuminate\Http\Response;
use Response as ResponseFactory;
use Illuminate\Support\Facades\Artisan;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\EsClient;
use RZP\Models\Base\EsDao;
use RZP\Base\RuntimeManager;
use RZP\Jobs\EsSync;
use RZP\Jobs\EsSyncEntities;
use RZP\Models\Base\EsRepository;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;

class EsController extends Controller
{
    /**
     * Methods allowed for debug endpoint.
     * Must only contain read operations!
     *
     * @var array
     */
    const ALLOWED_DEBUG_METHODS = [
        'cat',
        'explain',
        'getAliases',
        'getMapping',
        'getSettings',
        // Notice! Intentionally search or mget like read actions are not
        // allowed. Documents like merchant contains pii e.g. balance, email etc.
    ];

    /**
     * Route intended for use by dev debugging(READ ONLY), exposed via internal auth.
     *
     * @param string $method
     *
     * @return ApiResponse
     * @throws \Throwable
     */
    public function debug(string $method)
    {
        $this->validateDebugMethod($method);

        $params = Request::all();

        try
        {
            $res = $this->getEsClient()->$method($params);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ES_DEBUG_FAILED,
                $this->getTracePayload());

            throw $e;
        }

        $resTracePayload = $this->getTracePayload(['response' => $res]);

        $this->trace->info(TraceCode::ES_DEBUG_RESPONSE, $resTracePayload);

        return ApiResponse::json($res);
    }

    public function syncPayoutsToES()
    {
        $input = Request::all();

        $this->trace->debug(TraceCode::ES_DEBUG_INPUT, [
            'input'  => $input,
        ]);

        $merchantIds = $input['merchantIds'];

        $batch = $input['batchSize'];

        $mode = $this->app['rzp.mode'];

        $merchantCount = 0;

        foreach ($merchantIds as $merchantId)
        {
            $skip = 0;

            $totalCount = 0;

            do {
                $payouts = $this->repo
                    ->payout
                    ->fetchPayoutsForMerchantIdWithSkip($merchantId, $skip, $batch);

                $count = count($payouts);

                $skip += $count;

                try {
                    EsSync::dispatch($mode,
                        EsRepository::UPDATE, 'payout',
                        $payouts)->delay(0.2);
                } catch (\Throwable $e) {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::ES_SYNC_PUSH_FAILED);
                }

                $this->trace->debug(TraceCode::ES_DEBUG_RESPONSE, [
                    'merchantId' => $merchantId,
                    'count' => $count,
                ]);

                $totalCount += $count;

            } while ($batch === $count);

            $this->trace->debug(TraceCode::ES_DEBUG_TOTAL_COUNT, [
                'totalPayouts'  => $totalCount,
                'merchantId'    => $merchantId,
            ]);

            $merchantCount++;
        }

        $this->trace->debug(TraceCode::ES_DEBUG_MERCHANT_COUNT, [
            'merchantCount'  => $merchantCount,
        ]);
    }

    public function syncTransactionsToES()
    {
        $input = Request::all();

        $this->trace->debug(TraceCode::ES_DEBUG_INPUT, [
            'input'  => $input,
        ]);

        $merchantIds = $input['merchantIds'];

        $batch = $input['batchSize'];

        $mode = $this->app['rzp.mode'];

        $merchantCount = 0;

        foreach ($merchantIds as $merchantId)
        {
            $skip = 0;

            $totalCount = 0;

            do
            {
                $transactions = $this->repo
                    ->transaction
                    ->fetchTransactionsForMerchantIdWithSkip($merchantId, $skip, $batch);

                $count = count($transactions);

                $skip += $count;

                try{
                    EsSync::dispatch($mode,
                        EsRepository::CREATE, 'transaction',
                        $transactions)->delay(0.2);
                } catch (\Throwable $e) {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::ES_SYNC_PUSH_FAILED);
                }

                $this->trace->debug(TraceCode::ES_DEBUG_RESPONSE, [
                    'merchantId' => $merchantId,
                    'count'  => $count,
                ]);

                $totalCount += $count;

            } while($batch === $count);

            $this->trace->debug(TraceCode::ES_DEBUG_TOTAL_COUNT, [
                'totalTransactions'  => $totalCount,
                'merchantId'         => $merchantId,
            ]);

            $merchantCount++;
        }

        $this->trace->debug(TraceCode::ES_DEBUG_MERCHANT_COUNT, [
            'merchantCount'  => $merchantCount,
        ]);
    }

    public function syncEntitiesToES()
    {
        $input = Request::all();

        $this->trace->debug(TraceCode::ES_DEBUG_INPUT, [
            'input'  => $input,
        ]);

        $batchSize = $input['batchSize'];

        $entityType = $input['entityType'];

        $startTime = $input['startTime'];

        $endTime = $input['endTime'];

        $merchantIds = $input['merchantIds'];

        $mode = $this->app['rzp.mode'];

        EsSyncEntities::dispatch(
            $mode,
            EsRepository::UPDATE,
            $entityType,
            $startTime,
            $endTime,
            $batchSize,
            $merchantIds);
    }

    // -------------------- Write endpoint starts -----------------------------

    public function postAliases()
    {
        $this->trace->info(TraceCode::ES_ALIASES_WRITE_OP_REQUEST, $this->getTracePayload());

        $params = Request::all();

        try
        {
            $res = $this->getEsClient()->postAliases($params);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::ES_ALIASES_WRITE_OP_FAILED,
                $this->getTracePayload());

            throw $e;
        }

        $resTracePayload = $this->getTracePayload(['response' => $res]);

        $this->trace->info(TraceCode::ES_ALIASES_WRITE_OP_RESPONSE, $resTracePayload);

        return ApiResponse::json($res);
    }

    /**
     * Creates index with set mappings for the entity. Equivalent of cli `php artisan rzp:index_create`.
     *
     * Request parameters -
     * - mode         : Application mode (test|live)
     * - entity       : Entity name (e.g. item|merchant)
     * - index_prefix : ES index prefix (e.g. 20171201_beta_api_)
     * - type_prefix  : ES type prefix (e.g. beta_api_)
     * - --pretend    : Whether to run the command in pretend mode?
     * - --reindex    : Whether to delete existing index?
     *
     * @return \Illuminate\Http\Response
     */
    public function postIndexCreate()
    {
        $this->trace->info(TraceCode::ES_INDEX_CREATE_REQUEST, $this->getTracePayload());

        $this->increaseAllowedSystemLimits();

        Artisan::call('rzp:index_create', Request::all());

        return [];
    }

    /**
     * Indexes entity into es for search purposes.. Equivalent of cli `php artisan rzp:index`.
     *
     * Request parameters -
     * mode           : Database & application mode the command will run in (test|live)
     * entity         : Entity name (eg. item|merchant)
     * --slave        : Whether to use slave or master db connection? (0|1) [default: "0"]
     * --index_prefix : ES new index prefix (eg. 20171201_beta_api_)
     * --skip         : Skip offset (eg. skip first 100 rows) [default: "0"]
     * --take         : Take count (eg. 1000 at a time) [default: "5000"]
     * --start_at     : Start value(epoch) for time range query
     * --end_at       : End value(epoch) for time range query
     *
     * @return \Illuminate\Http\Response
     */
    public function postIndex()
    {
        $this->trace->info(TraceCode::ES_INDEX_REQUEST, $this->getTracePayload());

        $this->increaseAllowedSystemLimits();

        Artisan::call('rzp:index', Request::all());

        return [];
    }

    public function proxy($path)
    {
        // Preparing request to be sent to elasticsearch.
        // Url itself should contain the query string as well.
        $endpoint = 'http://' . env('ES_AUDIT_HOST') . ':9200/' . $path . '?' . Request::getQueryString();
        // Symfony returns each header key as an array.
        $headers  = array_map(function($v) { return current($v); }, Request::header());
        $headers  = array_only($headers, ['content-type']);
        $method   = Request::method();
        // For Requests::request call expects raw body.
        $body     = Request::getContent();

        $this->trace->info(TraceCode::ES_PROXY_REQUEST, compact('endpoint', 'method', 'body'));

        $resp        = Requests::request($endpoint, $headers, $body, $method);
        $respCode    = $resp->status_code;
        $respBody    = $resp->body;
        $respHeaders = $resp->headers->getAll();

        $this->trace->info(TraceCode::ES_PROXY_RESPONSE, compact('respCode', 'respBody', 'respHeaders'));

        // We cannot send all/other headers for various reasons.
        return ResponseFactory::make($respBody, $respCode, array_only($respHeaders, 'content-type'));
    }

    // -------------------- Write endpoint ends -------------------------------

    // -------------------- Protected methods starts --------------------------

    protected function validateDebugMethod(string $method)
    {
        $allowed = in_array($method, self::ALLOWED_DEBUG_METHODS, true);

        if ($allowed === false)
        {
            $tracePayload = $this->getTracePayload();

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ES_DEBUG_METHOD_NOT_VALID,
                null,
                $tracePayload);
        }
    }

    protected function getEsClient(): EsClient
    {
        return (new EsDao)->getEsClient();
    }

    protected function getTracePayload(array $with = [])
    {
        $routeParameters = Request::route()->parameters();
        $input           = Request::all();

        $data = [
            'route_params' => $routeParameters,
            'input'        => $input,
        ];

        return $data + $with;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(3600);
    }

    // -------------------- Protected methods ends ----------------------------
}
