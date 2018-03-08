<?php

namespace RZP\Http\BasicAuth;

use App;

use RZP\Http\Route;
use RZP\Constants\Mode;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Order;
use RZP\Models\Plan\Subscription;
use RZP\Models\Merchant;
use RZP\Constants\Entity as E;
use RZP\Error\ErrorCode;
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
        $this->route   = $app['api.route'];
        $this->ba      = $app['basicauth'];
    }

    /**
     * This function returns the entity name from sign
     * Currently we handle only the following entities [Order, Invoice, Payment, Subscription]
     *
     * @param string $sign
     *
     * @return $entity|null
     */
    protected function getEntityFromSign(string $sign)
    {
        $entity = null;

        if ($sign === Order\Entity::getSign())
        {
            $entity = E::ORDER;
        }
        else if ($sign === Invoice\Entity::getSign())
        {
            $entity = E::INVOICE;
        }
        else if ($sign === Payment\Entity::getSign())
        {
            $entity = E::PAYMENT;
        }
        else if ($sign === Subscription\Entity::getSign())
        {
            $entity = E::SUBSCRIPTION;
        }

        return $entity;
    }

    /**
     * Retrieves merchant entity to be set in BasicAuth for the request context.
     *
     * Approach:
     * - We check if there is a handler defined for the route, we call that.
     * - Else, we call a method with some default logic to find the merchant
     *
     * @return Merchant\Entity|null
     *
     * @throws \RZP\Exception\BadRequestException
     */
    public function retrieveMerchant()
    {
        $route = $this->route->getCurrentRouteName();

        $handler = 'retrieveMerchantFor' . studly_case($route);

        if (method_exists($this, $handler) === true)
        {
            return $this->$handler();
        }
        else
        {
            return $this->retrieveMerchantByDefaultLogic();
        }
    }

    /**
     * This function retrieves the signed entity_id from the request
     * - We check for x_entity_id key passed in query params
     * - Else we check for x_entity_id passed as route param
     * - Else we check for X-Entity-Id header in request headers
     *
     * At last if we are not able to find entityId from the above cases, we just return null
     *
     * @return $entityId|null
     */
    protected function retrieveSignedEntityId()
    {
        // fetch entity id from query param
        if ($this->request->has(self::X_ENTITY_ID_QUERY_KEY) === true)
        {
            $entityId = $this->request->get(self::X_ENTITY_ID_QUERY_KEY);

            $this->request->query->remove(self::X_ENTITY_ID_QUERY_KEY);
            $this->request->request->remove(self::X_ENTITY_ID_QUERY_KEY);
        }
        // fetch entity id from route param
        else if (is_null($this->request->route(self::X_ENTITY_ID_QUERY_KEY)) === false)
        {
            $entityId = $this->request->route(self::X_ENTITY_ID_QUERY_KEY);
        }
        // fetch entity id from request header
        else
        {
            $entityId = $this->request->headers->get(self::X_ENTITY_ID_HEADER_KEY);

            $this->request->headers->remove(self::X_ENTITY_ID_HEADER_KEY);
        }

        return $entityId;
    }

    /**
     * Approach:
     * We try to retrieve signed entity_id from the request either as a
     * query param or route param or in request header.
     * We find the entity with obtained entity_id and set the merchant of basic auth instance.
     *
     * As for mode we try with live mode first and then try test mode.
     *
     * At last if we are not able to do so, we just return null to caller(
     * BasicAuth) and there it'll follow expected 401 response.
     *
     * @return Merchant\Entity|null
     *
     * @throws \RZP\Exception\BadRequestException
     */
    protected function retrieveMerchantByDefaultLogic()
    {
        $signedEntityId = $this->retrieveSignedEntityId();

        if (is_null($signedEntityId) === true)
        {
            return null;
        }

        // Ideal retrieved signed entityId will be of format entitySign_{entityId}
        // E.g. inv_{invoiceId}, pay_{paymentId}.
        $entityInfo = explode('_', $signedEntityId);

        $entity = $this->getEntityFromSign($entityInfo[0]);

        $entityId = $entityInfo[1] ?? null;

        if ((E::isValidEntity($entity) === false) or
            (is_null($entityId) === true))
        {
            return null;
        }

        // Try to retrieve merchant using LIVE mode
        $merchant = $this->setModeAndRetrieveMerchantForEntity($entity, $entityId, Mode::LIVE);

        // If we fail to retrieve merchant, try using TEST mode
        if (is_null($merchant) === true)
        {
            $merchant = $this->setModeAndRetrieveMerchantForEntity($entity, $entityId, Mode::TEST);
        }

        return $merchant;
    }

    protected function setModeAndRetrieveMerchantForEntity(
        string $entity,
        string $id,
        string $mode)
    {
        $this->ba->setModeAndDbConnection($mode);

        $entityClass = E::getEntityClass($entity);
        $repoClass   = E::getEntityRepository($entity);

        $repo = new $repoClass;

        $entityClass::verifyIdAndSilentlyStripSign($id);

        try
        {
            $entity = $repo->findOrFailPublic($id);

            if (($entity->relationLoaded(E::MERCHANT) == true) or
                (method_exists($entity, E::MERCHANT) === true))
            {
                return $entity->merchant;
            }
        }
        catch (BadRequestException $ex)
        {
            // As we will be querying in the LIVE mode first and then in the TEST mode,
            // catch exception for bad request invalid id incase of LIVE mode and
            // throw same exception incase of TEST mode or any other exception.

            if (($ex->getCode() !== ErrorCode::BAD_REQUEST_INVALID_ID) or
                ($this->ba->getMode() === Mode::TEST))
            {
                throw $ex;
            }
        }
    }
}
