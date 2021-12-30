<?php

namespace RZP\Http\BasicAuth;

use App;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestException;

/**
 * Purpose: A merchant should be able to use invoice, orders, subscriptions from
 * dashboard and be able to accept payments against these without need of
 * generating api key.
 *
 * Public routes (see Route::$public) are accessible only if key is passed
 * as http basic auth or in request input(query parameter or form data). We have
 * come to find that there are cases when we already have other identifiers(i.e.
 * id of invoice, order, subscription or payment etc) as part of route parameters
 * or in request input, so we can use that to set BasicAuth's merchant instance
 * and continue with the code flow as in case of normal public auth routes.
 *
 * If we do not find any above mentioned identifiers as part of route parameters
 * or in request input, we look for x_entity_id which contains a signed entity id
 * sent as part route param or query param or in request header and set
 * BasicAuth's merchant instance and continue with the code flow.
 *
 * As for mode we try with live mode first and then try test mode.
 */
final class KeylessPublicAuth
{
    const X_ENTITY_ID_QUERY_KEY  = 'x_entity_id';
    const X_ENTITY_ID_HEADER_KEY = 'X-Entity-Id';

    protected $request;
    protected $route;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->request = $app['request'];
        $this->repo    = $app['repo'];
    }

    /**
     * Approach:
     * We just assumes there would be some query or form data params
     * such as order_id, invoice_id using which we set the merchant.
     *
     * If we fail to find identidier from the request input, we try to look
     * and retrieve signed entity_id from the request either as a query param
     * or route param or in request header. We find the entity with obtained
     * entity_id and set the merchant of basic auth instance.
     *
     * At last if we are not able to do so, we just return null to caller(
     * BasicAuth) and there it'll follow expected 401 response.
     *
     * @return array [string|null, Merchant\Entity|null]
     * @throws BadRequestException
     */
    public function retrieveModeMerchantAndXEntityId(): array
    {
        list($entity, $signedId) = $this->retrieveEntityAndSignedId();

        $mode = null;

        $merchant = null;

        if (E::isExternalEntity($entity) === true)
        {
            $serviceClass = E::getEntityService($entity);

            list($mode, $merchant)  = (new $serviceClass)->getModeAndMerchant($signedId);
        }
        else
        {
            list($mode, $merchant)   = $this->retrieveModeAndMerchantForEntity($entity, $signedId);
        }

        return [$mode, $merchant, $signedId];
    }

    /**
     * @return array
     */
    protected function retrieveEntityAndSignedId(): array
    {
        $entity   = null;
        $signedId = null;

        // Tries to find X-Entity-Id in route, query or headers
        $signedId = $this->retrieveXEntityId();

        if ($signedId !== null)
        {
            $sign   = str_before($signedId, '_');
            $entity = E::getKeylessAllowedEntityFromSign($sign);
        }
        // Else tries using request input against available map
        else
        {
            $input = $this->request->all();

            foreach (E::KEYLESS_ALLOWED_ENTITIES as $allowedEntity)
            {
                $key = "{$allowedEntity}_id";

                if (array_key_exists($key, $input) === true)
                {
                    $entity   = $allowedEntity;
                    $signedId = $input[$key];

                    break;
                }
            }
        }

        return [$entity, $signedId];
    }

    /**
     * This function retrieves the signed entity_id from the request
     * - We check for x_entity_id key passed in route param
     * - Else we check for x_entity_id passed in query params
     * - Else we check for X-Entity-Id header in request headers
     *
     * At last if we are not able to find entityId from the above cases, we just return null
     *
     * @return string|null
     */
    protected function retrieveXEntityId()
    {
        // Fetch entity id from route param
        if ($this->request->route(self::X_ENTITY_ID_QUERY_KEY) !== null)
        {
            $signedId = $this->request->route(self::X_ENTITY_ID_QUERY_KEY);
        }
        // Fetch entity id from query param
        else if ($this->request->has(self::X_ENTITY_ID_QUERY_KEY) === true)
        {
            $signedId = $this->request->get(self::X_ENTITY_ID_QUERY_KEY);

            // Unset x_entity_id from query and/or request(post/form data)
            $this->request->query->remove(self::X_ENTITY_ID_QUERY_KEY);
            $this->request->request->remove(self::X_ENTITY_ID_QUERY_KEY);
        }
        // Fetch entity id from request header
        else
        {
            $signedId = $this->request->headers->get(self::X_ENTITY_ID_HEADER_KEY);
        }

        return $signedId;
    }


    /**
     * @param  string|null $entity
     * @param  string|null $signedId
     * @return array [string, Merchant\Entity|null]
     * @throws BadRequestException
     */
    protected function retrieveModeAndMerchantForEntity(string $entity = null, string $signedId = null): array
    {
        // Signed id is not available, just return null
        if ($signedId === null)
        {
            return [null, null];
        }

        // If signed id is available and $entity wasn't resolved, it's bad request(invalid id)
        if ($entity === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID, null, ['attributes' => $signedId]);
        }

        $entityClass = E::getEntityClass($entity);
        $entityId    = $entityClass::verifyIdAndSilentlyStripSign($signedId);

        if ($entity === E::ORDER)
        {
            try
            {
                $order = $this->repo->order->connection(Mode::LIVE)->findOrFail($entityId);

                return [Mode::LIVE, $order->merchant];
            }
            catch (\Throwable $e)
            {
                $order = $this->repo->order->connection(Mode::TEST)->findOrFail($entityId);

                return [Mode::TEST, $order->merchant];
            }
        }

        // Try to retrieve merchant using LIVE mode
        $mode     = Mode::LIVE;
        $merchant = optional($this->repo->$entity->connection($mode)->find($entityId))->merchant;

        // If we fail to retrieve merchant, try using TEST mode
        if ($merchant === null)
        {
            $mode     = Mode::TEST;
            $merchant = optional($this->repo->$entity->connection($mode)->find($entityId))->merchant;
        }

        if ($merchant === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID, null, ['attributes' => $entityId]);
        }

        return [$mode, $merchant];
    }
}
