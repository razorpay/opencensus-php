<?php

namespace RZP\Http\Middleware;

use Hash;
use Closure;
use ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;

use RZP\Http\Route;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\LogicException;
use RZP\Models\Feature\Constants;
use RZP\Models\IdempotencyKey\Entity;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\Entity as Payout;
use RZP\Models\Payout\Core as PayoutCore;
use \RZP\Constants\Entity as EntityConstants;

/**
 * Class MerchantIdempotencyHandler
 *
 * Handles an incoming request with Header RZP-Idempotent-Key to serve idempotent/identical/same
 * response. The RZP-Idempotent-Key value is stored in MySQL.
 *
 * @package RZP\Http\Middleware
 */
class MerchantIdempotencyHandler
{
    protected $app;

    protected $router;

    protected $trace;

    protected $mutex;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * @var BasicAuth
     */
    protected $basicauth;

    /**
     * @var Route
     */
    protected $route;

    /**
     * Lock wait timeout in seconds.
     *
     * 20 minutes
     */
    const MUTEX_LOCK_TTL = 1200;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->repo = $app['repo'];

        $this->basicauth = $app['basicauth'];

        $this->router = $this->app['router'];

        $this->route = $this->app['api.route'];

        $this->trace = $this->app['trace'];

        $this->mutex = $this->app['api.mutex'];
    }

    public function handle(Request $request, Closure $next)
    {
        //
        // Handling this only for strictly private auth requests for now with
        // exception of route direct transfer api.
        // Will explore handling this for others as well, later.
        // Update:
        // Vendor Payments app will be using Idempotency feature to
        // to make sure multiple payouts for the same req are not created.
        //
        if (($this->basicauth->isStrictPrivateAuth() === false) and
            ($this->basicauth->isVendorPaymentApp() === false) and
            ($this->basicauth->isPayoutLinkApp() === false) and
            ($this->basicauth->isAccountsReceivableApp() === false) and
            ($this->basicauth->isSettlementsApp() === false) and
            ($this->basicauth->isXPayrollApp() === false) and
            ($this->basicauth->isRouteDirectTransferRequest() === false))
        {
            return $next($request);
        }

        if ($this->route->isApplicableForMerchantIdempotency() === false)
        {
            return $next($request);
        }

        // TODO: Check if we have to take care of auth as well. If the same route is
        // hit from proxy or admin or internal auth, how should the behaviour be?

        $idempotencyHeader = $this->route->getHeaderKeyForIdempotencyRequest();

        $idempotencyKey = $request->headers->get($idempotencyHeader);

        if (empty($idempotencyKey) === true)
        {
            return $next($request);
        }

        $merchant = $this->basicauth->getMerchant();

        if (empty($merchant) === true)
        {
            return $next($request);
        }

        $mutexKey = $idempotencyKey . $merchant->getId();

        $errorCode = $this->getErrorCodeForMutexLock();

        $lockResponse = $this->mutex->acquireAndRelease(
            $mutexKey,
            function() use ($idempotencyKey, $request, $merchant, $next, $errorCode)
            {
                /** @var Entity $idempotencyEntity */
                $idempotencyEntity = $this->repo->idempotency_key->findByIdempotencyKeyAndMerchant($idempotencyKey,
                                                                                                   $merchant);

                if ($idempotencyEntity !== null)
                {
                    $this->trace->info(
                        TraceCode::DUPLICATE_IDEM_KEY_RECEIVED,
                        [
                            'idempotency_key'   => $idempotencyKey,
                            'idempotency_id'    => $idempotencyEntity->getId(),
                            'merchant_id'       => $merchant->getId(),
                        ]);

                    $response = $this->handleExistingIdempotencyKey($request, $idempotencyEntity);

                    if ($response !== null)
                    {
                        $this->trace->info(
                            TraceCode::DUPLICATE_IDEM_KEY_RESPONSE,
                            [
                                'idempotency_key'   => $idempotencyKey,
                                'idempotency_id'    => $idempotencyEntity->getId(),
                                'merchant_id'       => $merchant->getId(),
                                'response'          => $response,
                            ]);

                        return ApiResponse::json($response);
                    }
                }
                else
                {
                    $idempotencyEntity = $this->createIdempotencyKeyEntity($request, $idempotencyKey, $merchant);
                }

                $this->basicauth->setIdempotencyKeyId($idempotencyEntity->getId());

                return $next($request);
            },
            static::MUTEX_LOCK_TTL,
            $errorCode
        );

        return $lockResponse;
    }

    protected function getErrorCodeForMutexLock()
    {
        if ($this->route->httpStatusCodeConflictForIdempotency() === true)
        {
            return ErrorCode::BAD_REQUEST_CONFLICT_ANOTHER_OPERATION_PROGRESS_SAME_IDEM_KEY;
        }
        return ErrorCode::SERVER_ERROR_ANOTHER_OPERATION_PROGRESS_SAME_IDEM_KEY;
    }

    protected function createIdempotencyKeyEntity(Request $request, string $idempotencyKey, Merchant\Entity $merchant)
    {
        $sourceType = $this->route->getEntityForIdempotencyRequest();

        if (empty($sourceType) === true)
        {
            throw new LogicException(
                'Idempotency route and entity type mapping not done',
                ErrorCode::SERVER_ERROR_IDEM_KEY_ROUTE_ENTITY_MAPPING_ABSENT,
                [
                    'source_type'   => $sourceType,
                    'route_name'    => $this->router->currentRouteName(),
                ]);
        }

        $buildInput = [
            Entity::IDEMPOTENCY_KEY => $idempotencyKey,
            Entity::REQUEST_HASH    => $this->getHashOfRequestBody($request),
            // This is required to ensure that we update the correct entity_id
            // later in the flow in `Base/Repository`. Without this, we will
            // end up updating the first entity that gets saved in the flow.
            Entity::SOURCE_TYPE     => $sourceType,
        ];

        $idempotencyEntity = (new Entity)->build($buildInput);

        $idempotencyEntity->merchant()->associate($merchant);

        $this->repo->saveOrFail($idempotencyEntity);

        $this->trace->info(
            TraceCode::IDEM_KEY_ENTITY_CREATED,
            [
                'idempotency_entity'    => $idempotencyEntity->toArray()
            ]);

        return $idempotencyEntity;
    }

    protected function handleExistingIdempotencyKey(Request $request, Entity $idempotencyEntity)
    {
        $requestBodyHash = $this->getHashOfRequestBody($request);

        $storedRequestHash = $idempotencyEntity->getRequestHash();

        if (hash_equals($storedRequestHash, $requestBodyHash) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SAME_IDEM_KEY_DIFFERENT_REQUEST,
                null,
                [
                    'request_hash'          => $requestBodyHash,
                    'idem_key_request_hash' => $idempotencyEntity->getRequestHash(),
                    'idempotency_key'       => $idempotencyEntity->getId(),
                ]);
        }

        $responseEntity = $idempotencyEntity->source;

        //
        // We were able to create the idempotency entity but before we could save the entity_id or the entity itself,
        // the request failed. The app could have crashed or the DB connection could have broken or could happen because
        // of any other reason. In that case, the idempotency entity will be saved without the entity details or it will
        // have entity details but the actual entity itself won't be saved. We will then treat it as a brand new
        // request.
        //
        if (empty($responseEntity) === true)
        {
            $this->trace->info(
                TraceCode::DUPLICATE_IDEM_KEY_NO_ENTITY_ASSOC,
                [
                    'idempotency_key'   => $idempotencyEntity->getIdempotencyKey(),
                    'idempotency_id'    => $idempotencyEntity->getId(),
                    'merchant_id'       => $idempotencyEntity->getMerchantId(),
                ]);

            $payoutServiceResponse = $this->getPayoutServicePayoutResponseIfApplicable($idempotencyEntity);

            if (empty($payoutServiceResponse) === false)
            {
                return $payoutServiceResponse;
            }

            return null;
        }

        // TODO: Will not work for things like contact and Fund Account since we have different status codes
        // for duplicates. Explore storing the response in the idempotency table instead and using that directly.
        // Storing the response at the middleware layer has other issues. Check other comments in this file.
        return $responseEntity->toArrayPublic();
    }

    protected function getHashOfRequestBody(Request $request): string
    {
        $requestBody = $request->all();

        ksort($requestBody);

        //
        // Using json_encode to provide a hashing seed is not safe. For example, if
        // the array you are using with json_encode has a non UTF-8 character,
        // json_encode will return false, this will make all your hashes the same.
        // But, this should ideally never happen and is safe to use in this case.
        // If it all it happens, we'll fail the request and look at how to go about it then.
        //
        $encodedRequestBody = json_encode($requestBody);

        if ($encodedRequestBody === false)
        {
            throw new LogicException(
                'json_encode returned false in the idempotency flow!',
                ErrorCode::SERVER_ERROR_JSON_ENCODE_FALSE
            );
        }

        $hash = hash('sha256', $encodedRequestBody);

        return $hash;
    }

    protected function getPayoutServicePayoutResponseIfApplicable(Entity $idempotencyEntity)
    {
        $sourceType = $this->route->getEntityForIdempotencyRequest();

        $psIdempotencyEntity = null;

        if ($sourceType === EntityConstants::PAYOUT)
        {
            $merchant = $idempotencyEntity->merchant;

            $isPayoutServiceIdempotencyFeatureEnabled =
                $merchant->isAtLeastOneFeatureEnabled(Constants::PAYOUT_SERVICE_IDEMPOTENCY_KEY_FEATURES);

            if ($isPayoutServiceIdempotencyFeatureEnabled === false)
            {
                return null;
            }

            $psIdempotencyEntity = null;

            /** @var Entity $psIdempotencyEntity */
            $psIdempotencyEntity = (new PayoutCore)->getAPIModelIdempotencyKeyFromPayoutService(
                $idempotencyEntity->getIdempotencyKey(),
                $merchant->getId());

            if (empty($psIdempotencyEntity) === true)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_IDEM_KEY_NOT_FOUND,
                    [
                        'idempotency_key' => $idempotencyEntity->getIdempotencyKey(),
                        'idempotency_id'  => $idempotencyEntity->getId(),
                        'merchant_id'     => $idempotencyEntity->getMerchantId(),
                    ]);

                return null;
            }

            $psSourceId = $psIdempotencyEntity->getSourceId();

            if (empty($psSourceId) === true)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_IDEM_KEY_NO_ENTITY_ASSOC,
                    [
                        'idempotency_key'       => $idempotencyEntity->getIdempotencyKey(),
                        'idempotency_id'        => $idempotencyEntity->getId(),
                        'merchant_id'           => $idempotencyEntity->getMerchantId(),
                        'ps_idempotency_key_id' => $psIdempotencyEntity->getId(),
                    ]);

                throw new LogicException(
                    'Payout service idempotency key has no source mapped',
                    ErrorCode::SERVER_ERROR_PAYOUT_SERVICE_IDEM_KEY_SOURCE_UNMAPPED,
                    [
                        'idempotency_key'       => $idempotencyEntity->getIdempotencyKey(),
                        'idempotency_id'        => $idempotencyEntity->getId(),
                        'merchant_id'           => $idempotencyEntity->getMerchantId(),
                        'ps_idempotency_key_id' => $psIdempotencyEntity->getId(),
                    ]);
            }

            /** @var Payout $psPayout */
            $psPayout = (new PayoutCore)->getAPIModelPayoutFromPayoutService($psSourceId);

            if (empty($psPayout) === true)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_IDEM_KEY_ASSOC_ENTITY_NOT_FOUND,
                    [
                        'idempotency_key'       => $idempotencyEntity->getIdempotencyKey(),
                        'idempotency_id'        => $idempotencyEntity->getId(),
                        'merchant_id'           => $idempotencyEntity->getMerchantId(),
                        'ps_idempotency_key_id' => $psIdempotencyEntity->getId(),
                        'source_id'             => $psSourceId,
                    ]);

                throw new LogicException(
                    'Payout service idempotency key source not found in payouts db',
                    ErrorCode::SERVER_ERROR_PAYOUT_SERVICE_IDEM_KEY_SOURCE_NOT_FOUND,
                    [
                        'idempotency_key'       => $idempotencyEntity->getIdempotencyKey(),
                        'idempotency_id'        => $idempotencyEntity->getId(),
                        'merchant_id'           => $idempotencyEntity->getMerchantId(),
                        'ps_idempotency_key_id' => $psIdempotencyEntity->getId(),
                        'source_id'             => $psSourceId,
                    ]);
            }

            // Keeping in try catch because even if it fails, we want to show the response to the merchant.
            try
            {
                // If payout id is not stored in idempotency_keys source_id, we do that so that when payout is dual
                // written on api, it can fetch details from there itself. The source_id could have been left empty if
                // payout creation call to ps timed out or some issue happened while updating idempotency key entity
                // post that.
                if (empty($idempotencyEntity->getSourceId()) === true)
                {
                    $idempotencyEntity->source()->associate($psPayout);

                    $this->repo->idempotency_key->saveOrFail($idempotencyEntity);
                }
            }
            catch (\Throwable $throwable)
            {
                $this->trace->error(
                    TraceCode::PAYOUT_SERVICE_IDEM_KEY_SOURCE_ASSOCIATION_FAILED,
                    [
                        'ps_payout'       => $psPayout->toArrayPublic(),
                        'idempotency_key' => $idempotencyEntity->toArray(),
                    ]);
            }

            return $psPayout->toArrayPublic();
        }

        return null;
    }
}
