<?php
namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Http\Request\Requests;

class Onboarding extends Base\Core
{

  protected $app;
  const CREATE_SHOPIFY_ACCOUNT_ENDPOINT = "v1/merchants/shopify_accounts";


  public function __construct($app = null)
  {
      parent::__construct();

      $this->app = App::getFacadeRoot();
  }

  // createNewShopifyAccount proxies the API request to Magic Checkout Service. This is the first step to onboard a new
  // Shopify merchant.
  public function createNewShopifyAccount($input): array
  {
      (new Validator)->setStrictFalse()->validateInput('createNewShopifyAccount', $input);

      // Explicitly setting the body for readability.
      $body = [
        'merchant_id'       => $input['merchant_id'],
        'shop_id'           => $input['shop_id'],
        'client_id'         => $input['client_id'],
        'client_secret'     => $input['client_secret'],
        'installation_link' => $input['installation_link'],
      ];

      $body = $this->app['magic_checkout_service_client']->sendRequest(self::CREATE_SHOPIFY_ACCOUNT_ENDPOINT, $body, Requests::POST);

      return $body;
  }
}
