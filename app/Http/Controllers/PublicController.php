<?php

namespace RZP\Http\Controllers;

use Illuminate\Support\Str;
use RZP\Constants\Environment;
use View, Request, ApiResponse;
use Illuminate\Support\Facades\DB;

use RZP\Exception;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Services\EsClient;
use RZP\Http\Request\Requests;

class PublicController extends Controller
{
    public function getRoot()
    {
        $response['message'] = "Welcome to Razorpay API.";

        return ApiResponse::json($response);
    }

    public function getStatus()
    {
        $statusCode = 200;

        $okStatusRequired = [
            'd',
            'dr',
        ];

        $status = [
            // Database
            'd'      => $this->getDbStatus(),
            // Database read replica
            'dr'     => $this->getDbStatus('read'),
            // Redis
            'c'      => $this->getCacheStatus(),
            // sec redis
            'sc'     => $this->getCacheStatus('secure'),
            // Elastic search
            's'      => $this->getEsStatus(),
            'gnupg'  => $this->getGnupgStatus(),
        ];

        foreach ($okStatusRequired as $field)
        {
            if (($status[$field] !== 'ok'))
            {
                $statusCode = 503;

                break;
            }
        }

        return ApiResponse::json($status, $statusCode);
    }

    public function getGnupgStatus()
    {
        $user = posix_getpwuid(posix_getuid());

        return is_writable($user['dir']  . "/.gnupg");
    }

    public function getCatchAllRoute(string $uri = null)
    {
        return ApiResponse::routeNotFound();
    }

    public function getAccount()
    {
        $data = [
            'static'    => $this->config->get('url.cdn.production'),
            'api'       => $this->config->get('app.url'),
        ];

        return View::make('public.account', $data);
    }

    /**
     * Works on checkout onyx protocol.
     */
    public function postCallbackUrlWithParams()
    {
        $postParams = Request::instance()->request->all();
        $getParams = Request::query('data');

        // decode base64 string
        $getParams = str_replace(' ', '+', $getParams);
        $data = utf8_json_decode(base64_decode($getParams), true) ?? [];

        // Perform validation to prevent XSS
        $this->validateCallbackUrlParams($data);

        // Relevant info for re-directing to merchant url.

        $data['version'] = $data['version'] ?? 1;

        if (isset($postParams['razorpay_payment_id']))
        {
            $data['request']['method'] = $data['request']['method'] ?? 'GET';
            $data['request']['target'] = $data['request']['target'] ?? '_self';

            //
            // It's successful payment merge all post params
            // with already existing POST params that have been
            // defined by the merchant
            //
            $data['request']['content'] = array_filter(
                array_merge($data['request']['content'] ?? [], $postParams),
                static function ($value, $key) {
                    // Remove fields where keys (or) values contain injected JavaScript
                    // with `javascript:` pseudo protocol to prevent XSS
                    return ! (
                        Str::contains($key, 'javascript:') ||
                        Str::contains($value, 'javascript:')
                    );
                },
                ARRAY_FILTER_USE_BOTH
            );

            $data['retry'] = false;
        }
        else if (isset($postParams['error']))
        {
            assertTrue (isset($postParams['action']) === false);

            // just pass in error.
            $data['error'] = $postParams['error'];
            $data['retry'] = true;
        }
        else
        {
            throw new Exception\LogicException('Should not have reached here');
        }

        $checkout = $this->getCheckoutCommon();
        $data['checkout'] = $checkout['checkout'] . '/v1/checkout.js';

        return View::make('public.callback_params', $data);
    }

    public function getEmbeddedCommon($meta) {
        $script = $this->config->get('url.cdn.production') . '/static/hosted/embedded-entry.js';

        $merchant = $this->ba->authCreds->getMerchant();

        $meta['custom_code'] = $merchant->org->getCustomCode();

        $meta['checkout_logo_url'] = $merchant->org->getCheckoutLogo();

        $meta['custom_checkout_logo_enabled'] = $merchant->org->isFeatureEnabled(Feature\Constants::ORG_CUSTOM_CHECKOUT_LOGO);

        $app = \App::getFacadeRoot();

        if(((isset($meta['type']) === true) and
        ($meta['type'] === 'hdfcvas') and
        ($merchant->isFeatureEnabled(Feature\Constants::HDFC_CHECKOUT_2) === true)) or ($merchant->org->isFeatureEnabled(Feature\Constants::HDFC_CHECKOUT_2)))
        {
            $script = $this->config->get('url.cdn.production') . '/static/hosted/standard-vas.js';
        }

        $app['trace']->info(TraceCode::HDFC_CHECKOUT_2, [
            'merchantID' => $merchant['id'],
            'merchantFeaturePresent'=> $merchant->isFeatureEnabled(Feature\Constants::HDFC_CHECKOUT_2),
            'merchantOrgFeaturePresent'=> $merchant->org->isFeatureEnabled(Feature\Constants::HDFC_CHECKOUT_2),
            'script'        => $script
        ]);

        $options = [
            'key'          => $this->ba->getPublicKey(),
            'options'      => json_encode(Request::all(), JSON_FORCE_OBJECT),
            'meta'         => json_encode($meta, JSON_FORCE_OBJECT),
            'script'       => $script,
            'urls'         => "{}"
        ];

        return View::make('public.embedded', $options);
    }

    public function renderEmbedded()
    {
        $meta = [];

        if ($this->ba->authCreds->getMerchant()->getOrgId() === '6dLbNSpv5XbCOG') {
            $meta['type'] = 'hdfcvas';
        }

        $app = \App::getFacadeRoot();

        if($app['env'] === Environment::AXIS)
        {
            if ($this->ba->authCreds->getMerchant()->getOrgId() === 'HFRjdj3PKjhIM1') {
                $meta['type'] = 'hdfcvas';
            }
        }

        return $this->getEmbeddedCommon($meta);
    }

    public function renderHdfcVas()
    {
        return $this->getEmbeddedCommon([
            'type' => 'hdfcvas'
        ]);
    }

    public function renderCheckoutHosted()
    {
        $params = Request::all();

        $this->validateHostedParams($params);

        // place flashcheckout check here
        $showEmbeddedUi = false;
        $options     = json_encode($params['checkout'], JSON_FORCE_OBJECT);
        $urls        = json_encode($params['url'], JSON_FORCE_OBJECT);

        if ($showEmbeddedUi) {
            $key         = $params['checkout']['key'];
            $embeddedJsUrl = $this->config->get('url.cdn.production') . '/static/hosted/embedded-entry.js';
            $data = [
                'key'          => $key,
                'options'      => $options,
                'meta'         => "{}",
                'script'       => $embeddedJsUrl,
                'urls'         => $urls,
            ];

            return View::make('public.embedded', $data);
        } else {
            $checkout    = $this->getCheckoutCommon();
            $checkoutUrl = $checkout['checkout'] . '/v1/checkout.js';
            $data = [
                'options'      => $options,
                'checkout'     => $checkoutUrl,
                'urls'         => $urls,                      // used directly in JS side
                'url_callback' => $params['url']['callback'], // Used in PHP
                'retry'        => true,
            ];

            return View::make('public.hosted', $data);
        }


    }

    protected function validateHostedParams($params)
    {
        $rules = [
            'url'                   => 'required|array',
            'checkout'              => 'required|array',
            'url.cancel'            => 'sometimes|url',
            'url.callback'          => 'required|url',
            'checkout.key'          => 'required|string|min:23',
            'checkout.order_id'     => 'sometimes|string|size:20',
            'checkout.amount'       => 'required_without:checkout.order_id|integer',
            'checkout.image'        => 'sometimes|url',
            'retry'                 => 'sometimes',
        ];

        (new JitValidator)->rules($rules)->input($params)->validate();
    }

    /**
     * @param array $params
     */
    protected function validateCallbackUrlParams(array $params): void
    {
        $rules = [
            'request'        => 'sometimes|array',
            'request.url'    => 'required_with:request|url',
            'options'        => 'sometimes|json',
            'back'           => 'sometimes|url',
        ];

        (new JitValidator())->setStrictFalse()->rules($rules)->input($params)->validate();
    }

    protected function getDbStatus($replica = null)
    {
        $method = 'get' . ucfirst($replica) . 'Pdo';
        try
        {
            $conn = DB::connection()->{$method}();

            $conn->query('select 1')->fetch();

            if ($conn)
            {
                return 'ok';
            }
        }
        catch (\Throwable $e)
        {
            return 'error';
        }

        return 'error';
    }

    protected function getCacheStatus($connection = null)
    {
        try
        {
            if ($this->app['redis']->connection($connection)->info('Keyspace'))
            {
                return 'ok';
            }
        }
        catch (\Throwable $e)
        {
            return 'error';
        }

        return 'error';
    }

    /**
     * Makes call to get category count. ES state is connected if the result is not empty
     *
     * @return mixed
     */
    protected function getEsStatus()
    {
        try
        {
            $es = (new EsClient($this->app));

            $params = [
                'hosts' => [
                    $this->config->get('database.es_host')
                ],
            ];

            $es->setEsClient($params);

            $count = $es->catCount();

            return $count ? 'ok' : 'error';
        }
        catch (\Throwable $e)
        {
            return 'error';
        }

        return 'error';
    }
}
