<?php

namespace RZP\Http\BasicAuth;

use App;

use RZP\Http\Route;
use RZP\Constants\Mode;
use RZP\Models\Invoice;
use RZP\Models\Payment;
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
    /**
     * Map of input parameter and respective entity.
     * Ref: retrieveMerchantByDefaultLogic() for usage.
     */
    const INPUT_ENTITY_MAP = [
        Payment\Entity::ORDER_ID        => E::ORDER,
        Payment\Entity::INVOICE_ID      => E::INVOICE,
        Invoice\Entity::PAYMENT_ID      => E::PAYMENT,
        Invoice\Entity::SUBSCRIPTION_ID => E::SUBSCRIPTION,
    ];

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
     * Sets mode and retrieves merchant entity to be set in BasicAuth for the request context.
     *
     * Approach:
     * - We check if there is a handler defined for the route, we call that.
     * - Else, we call a method with some default logic to find the merchant
     *
     * @param $mode string
     *
     * @return Merchant\Entity|null
     *
     * @throws \RZP\Exception\BadRequestException
     */
    public function setModeAndRetrieveMerchant(string $mode)
    {
        $this->ba->setModeAndDbConnection($mode);

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
     * Approach:
     * - If the route falls in pattern of </some-entity-name-in-plural/{id}/>,
     *   we just find the entity with given id and set the merchant.
     * - Else for routes like /checkout/preferences etc. we just assumes there
     *   would be some query or form data params such as order_id, invoice_id
     *   using which we set the merchant.
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
        $route           = $this->route->getCurrentRouteName();
        $routeParameters = Route::getApiRoute($route);
        $matches         = [];

        if (preg_match(
                '/([a-zA-Z_]+)(\/)({id})(.*)/',
                $routeParameters[1],
                $matches) === 1)
        {
            $id = $this->request->route('id');
            $entity = str_singular($matches[1]);

            return $this->retrieveMerchantForEntity($entity, $id);
        }

        $input = $this->request->all();

        foreach (self::INPUT_ENTITY_MAP as $key => $entity)
        {
            if (array_key_exists($key, $input) === true)
            {
                return $this->retrieveMerchantForEntity($entity, $input[$key]);
            }
        }
    }

    protected function retrieveMerchantForEntity(
        string $entity,
        string $id)
    {
        if (E::isValidEntity($entity) === false)
        {
            return null;
        }

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
            /**
             * As we will be querying in the LIVE mode first and then in the TEST mode,
             * catch exception for bad request invalid id incase of LIVE mode and
             * throw same exception incase of TEST mode or any other exception.
             */
            if (($ex->getCode() !== ErrorCode::BAD_REQUEST_INVALID_ID) or
                ($this->ba->getMode() === Mode::TEST))
            {
                throw $ex;
            }
        }
    }
}
