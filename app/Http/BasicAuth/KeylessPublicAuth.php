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

    /**
     * Map of input parameter and respective entity.
     * Ref: retrieveMerchant() for usage.
     */
    const INPUT_ENTITY_MAP = [
        Payment\Entity::ORDER_ID        => E::ORDER,
        Payment\Entity::INVOICE_ID      => E::INVOICE,
        Invoice\Entity::PAYMENT_ID      => E::PAYMENT,
        Invoice\Entity::SUBSCRIPTION_ID => E::SUBSCRIPTION,
    ];

    protected $request;
    protected $route;
    protected $ba;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->request = $app['request'];
        $this->route   = $app['api.route'];
        $this->ba      = $app['basicauth'];
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
     * @return Merchant\Entity|null
     *
     * @throws \RZP\Exception\BadRequestException
     */
    public function retrieveMerchant()
    {
        $input = $this->request->all();

        foreach (self::INPUT_ENTITY_MAP as $key => $entity)
        {
            if (array_key_exists($key, $input) === true)
            {
                return $this->retrieveMerchantForEntity($entity, $input[$key]);
            }
        }

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

        return $this->retrieveMerchantForEntity($entity, $entityId);
    }

    /**
     * This function retrieves the signed entity_id from the request
     * - We check for x_entity_id key passed in route param
     * - Else we check for x_entity_id passed in query params
     * - Else we check for X-Entity-Id header in request headers
     *
     * At last if we are not able to find entityId from the above cases, we just return null
     *
     * @return $entityId|null
     */
    protected function retrieveSignedEntityId()
    {
        // fetch entity id from route param
        if (is_null($this->request->route(self::X_ENTITY_ID_QUERY_KEY)) === false)
        {
            $entityId = $this->request->route(self::X_ENTITY_ID_QUERY_KEY);
        }
        // fetch entity id from query param
        else if ($this->request->has(self::X_ENTITY_ID_QUERY_KEY) === true)
        {
            $entityId = $this->request->get(self::X_ENTITY_ID_QUERY_KEY);

            // unset x_entity_id from query param
            $this->request->query->remove(self::X_ENTITY_ID_QUERY_KEY);
            $this->request->request->remove(self::X_ENTITY_ID_QUERY_KEY);
        }
        // fetch entity id from request header
        else
        {
            $entityId = $this->request->headers->get(self::X_ENTITY_ID_HEADER_KEY);
        }

        return $entityId;
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
     * This function validates and retrieves merchant for entity.
     * As for mode we try with live mode first and then try test mode.
     *
     * @param $entity
     * @param $id
     *
     * @return $merchant|null
     *
     * @throws \RZP\Exception\BadRequestException
     */
    protected function retrieveMerchantForEntity($entity, $id)
    {
        if ((E::isValidEntity($entity) === false) or
            (is_null($id) === true))
        {
            return null;
        }

        $entityClass = E::getEntityClass($entity);

        $entityClass::verifyIdAndSilentlyStripSign($id);

        // Try to retrieve merchant using LIVE mode
        $merchant = $this->setModeAndRetrieveMerchantForEntity($entity, $id, Mode::LIVE);

        // If we fail to retrieve merchant, try using TEST mode
        if (is_null($merchant) === true)
        {
            $merchant = $this->setModeAndRetrieveMerchantForEntity($entity, $id, Mode::TEST);
        }

        return $merchant;
    }

    /**
     * This function sets mode and retrieves merchant for entity.
     *
     * @param string $entity
     * @param string $id
     * @param string $mode
     *
     * @return $merchant|null
     *
     * @throws \RZP\Exception\BadRequestException
     */
    protected function setModeAndRetrieveMerchantForEntity(
        string $entity,
        string $id,
        string $mode)
    {
        $this->ba->setModeAndDbConnection($mode);

        $repoClass = E::getEntityRepository($entity);

        $repo = new $repoClass;

        try
        {
            $entity = $repo->findOrFailPublic($id);

            if (is_null($entity->getMerchantId()) === false)
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
