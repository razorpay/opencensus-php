<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService;

use App;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;

class Service extends Base\Service
{

  protected $app;

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
}
