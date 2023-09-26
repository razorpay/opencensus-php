<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService;

use App;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\OneClickCheckout\Shopify;

class Service extends Base\Service
{

  const SHIPPING_OPTIONS_PATH = 'v1/shipping/options';

  protected $app;

  const MAGIC_CHECKOUT_SERVICE_THEME_LIQUID_FILES_FETCH_PATH = 'v1/admin/shopify/theme/liquid_files';

  public function __construct()
  {
      parent::__construct();
      $this->app = App::getFacadeRoot();
  }

  // handleMerchantDashboardReq forwards requests from Rzp merchant dashboard to Magic Checkout svc.
  // This should NOT be used to handle requests from admin dashboard. In case of `GET` requests
  // the URL params passed are auto converted to $input['body']. These are directly sent to the requester
  // which converts it to url params internally. We do NOT need to construct a separate path.
  public function handleMerchantDashboardReq(array $input): array
  {
      $path = $this->transformPath($input['path']);
      return $this->app['magic_checkout_service_client']->sendRequest($path, $input['body'], $input['method']);
  }

  // transformPath is used to map an endpoint in monolith to Magic Checkout svc.
  // For now we simply strip the 1cc prefix but this can support additional transformations as required.
  protected function transformPath(string $path): string
  {
      return str_replace('1cc/', '', $path);
  }


 /**
  * @param $addressId
  * @param $customerId
  * @return array|mixed
  * @throws BadRequestException
  * @throws IntegrationException
  */
  public function updateAddressUsageToMagicCheckoutService($addressId, $customerId) {
      $path = "v1/magic/addresses";
      $input = ['address_id' => $addressId, 'customer_id' => $customerId];
      return (new Client)->sendRequest($path, $input, Requests::POST);
  }

  public function handleAdminDashboardThemeAutomationReq(array $input): array
  {
      $routeName = $this->app['router']->currentRouteName();
      $params = $this->app['router']->current()->parameters();
      $merchantId = $params['id'];
      $path = $this->getPathFromRouteName($routeName);
      $method = $input['method'];
      $body = $input['body'];
      if ($method === 'GET') {
          [$query, $headers] = (new Shopify\Service())->constructFetchQueryForMagicCheckoutService($merchantId);
          $path = $path . $query;

      } else {
          [$body, $headers] = (new Shopify\Service())->constructPayloadForMagicCheckoutService($merchantId, $body);
      }
      return $this->app['magic_checkout_service_client']->sendRequest($path, $body, $method, $headers);
  }

  protected function getPathFromRouteName(string $route): string
  {
      switch ($route) {
          case '1cc_shopify_fetch_liquid_files' :
          case '1cc_shopify_update_liquid_files' :
              return self::MAGIC_CHECKOUT_SERVICE_THEME_LIQUID_FILES_FETCH_PATH;
          default:
              throw new Exception\BadRequestException(
                  ErrorCode::BAD_REQUEST_ERROR,
                  null,
                  null,
                  "Not a valid request");
      }
   }

  public function getShippingOptions(array $input): array
  {
      return $this->app['magic_checkout_service_client']->sendRequest(self::SHIPPING_OPTIONS_PATH, $input, Requests::POST);
  }
}
