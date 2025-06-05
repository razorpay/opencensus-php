<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService;

use App;
use Throwable;
use RZP\Exception;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\OneClickCheckout\Shopify;

class Service extends Base\Service
{
  const SHIPPING_OPTIONS_PATH = 'v1/shipping/options';
  const POLL_FOR_SHIPPING_RATES_PATH = 'v1/checkouts/shipping/poll';
  const UPDATE_SHIPPING_ADDRESS_PATH = 'v1/checkouts/address';
  const MAGIC_CHECKOUT_SERVICE_THEME_LIQUID_FILES_FETCH_PATH = 'v1/admin/shopify/theme/liquid_files';
  const SHOPIFY_COMPLETE_CHECKOUT_PATH       = 'v1/checkouts/shopify/complete';
  const SHOPIFY_POST_ORDER_PATH              = 'v1/shopify/handle/post_order_error';
  const CLEAR_MERCHANT_CONFIGS_FROM_CACHE    = 'v1/merchants/configs/cache/invalidate';
  const CHECK_SHOPIFY_COMPLETE_CHECKOUT_PATH = 'v1/checkouts/order_status';
  const TAX_DETAILS_AND_SHIPPING_OPTIONS_PATH = 'v1/internal/shipping/options';
  const GET_CUSTOMER_ADDRESS_PATH             = 'v1/magic/customer/address';
  const MERCHANT_CONFIGS_WRITE_PATH           = 'v1/magic/merchants/configs';
  const SHOPIFY_PUBLIC_APP_POST_INSTALLATION_COD_WORKFLOW_PATH = 'v1/integrations/shopify/cod/workflow';

  public function __construct()
  {
      parent::__construct();
  }

  // handleMerchantDashboardReq forwards requests from Rzp merchant dashboard to Magic Checkout svc.
  // This should NOT be used to handle requests from admin dashboard. In case of `GET` requests
  // the URL params passed are auto converted to $input['body']. These are directly sent to the requester
  // which converts it to url params internally. We do NOT need to construct a separate path.
  public function handleMerchantDashboardReq(array $input): array
  {
      $path = $this->transformPath($input['path']);
      $headers = [];
      if (empty($input['header']) === false)
      {
          $headers = $input['header'];
      }
      if (isset($input['file'])=== true)
      {
          return $this->app['magic_checkout_service_client']->sendRequest($path, $input, $input['method'], $headers);
      }
      return $this->app['magic_checkout_service_client']->sendRequest($path, $input['body'], $input['method'], $headers);
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
      $appName = (new Shopify\Service())->getShopifyAppName($input);
      if ($method === 'GET') {
          [$query, $headers] = (new Shopify\Service())->constructFetchQueryForMagicCheckoutService($merchantId, $appName);
          $path = $path . $query;

      } else {
          [$body, $headers] = (new Shopify\Service())->constructPayloadForMagicCheckoutService($merchantId, $body, $appName);
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

  public function getTaxDetailsAndShippingOptions(array $input): array
  {
        return $this->app['magic_checkout_service_client']->sendRequest(self::TAX_DETAILS_AND_SHIPPING_OPTIONS_PATH, $input, Requests::POST);
  }

  public function pollForShippingRates(array $input): array
  {
      return $this->app['magic_checkout_service_client']->sendRequest(self::POLL_FOR_SHIPPING_RATES_PATH, $input, Requests::POST);
  }

  public function updateShippingAddress(array $input): array
  {
      return $this->app['magic_checkout_service_client']->sendRequest(self::UPDATE_SHIPPING_ADDRESS_PATH, $input, Requests::POST);
  }

  public function completeShopifyCheckout(array $input): array
  {
    return $this->app['magic_checkout_service_client']->sendRequest(self::SHOPIFY_COMPLETE_CHECKOUT_PATH, $input, Requests::POST);
  }

  public function checkAndCompletePostShopifyOrderPlacementSteps(array $input): array
  {
    return $this->app['magic_checkout_service_client']->sendRequest(self::SHOPIFY_POST_ORDER_PATH, $input, Requests::POST);
  }

  public function clearMerchantConfigsFromCache(array $input): array
  {
      return $this->app['magic_checkout_service_client']->sendRequest(self::CLEAR_MERCHANT_CONFIGS_FROM_CACHE, $input, Requests::POST);
  }

  public function getCheckoutOrderStatus(array $input): array
  {
    return $this->app['magic_checkout_service_client']->sendRequest(self::CHECK_SHOPIFY_COMPLETE_CHECKOUT_PATH, $input, Requests::GET);
  }

  public function fetchCustomerAddress($contact)
  {
      $input = ['contact' => $contact];

      return $this->app['magic_checkout_service_client']->sendRequest(self::GET_CUSTOMER_ADDRESS_PATH, $input, Requests::GET);
  }

    public function postPublicAppInstallationWorkflow(array $input)
    {
        return $this->app['magic_checkout_service_client']->sendRequest(self::SHOPIFY_PUBLIC_APP_POST_INSTALLATION_COD_WORKFLOW_PATH, $input, Requests::POST);
    }

    // updateMerchantConfigs is being used to dual write upserts to MCS. Once we verify this flow is working
    // properly and start reading from MCS, we will directly hit MCS from merchant dashboard and bypass API.
    public function updateMerchantConfigs(array $input)
    {
        return $this->app['magic_checkout_service_client']->sendRequest(self::MERCHANT_CONFIGS_WRITE_PATH, $input, Requests::POST);
    }
}
