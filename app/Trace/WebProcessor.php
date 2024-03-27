<?php

namespace App\Trace;

use App;
use Http\Route;
use Razorpay\Api\Request as ApiRequest;
use Request;
use Uuid;

/**
 * Injects url/method and remote IP of the current web request in all records
 */
class WebProcessor extends \Monolog\Processor\WebProcessor
{
    protected $request;

    // Attributes that should not be logged
    protected $BLACK_LISTED_HEADERS = ['X-Dashboard-User-Session-Id'];

    /**
     * @param mixed $serverData array or object w/ ArrayAccess that provides access to the $_SERVER data
     */
    public function __construct()
    {
        $this->request = App::make('request');

        $this->env = App::make('config')->get('app.env');

        $data = $this->getServerData() + $this->getUserData();

        parent::__construct($data);
    }

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record) : array
    {
        if (isset($this->serverData) === false)
        {
            throw new Exception\LogicException('Server data for trace logs not present');
        }

        $record['request'] = $this->serverData;

        return $record;
    }

    public function getServerData()
    {

        if(!$this->request->hasHeader('X-Razorpay-Request-Id')) {
            $this->request->headers->set('X-Razorpay-Request-Id','Api-'.Uuid::generate());
        }
        $serverData = [
            'request_id'        => $this->request->getId(),
            'uri'               => $this->request->path(),
            'url'               => App\Constants\Tracing::maskUrl(($this->request->fullUrl())),
            'method'            => $this->request->method(),
            'ajax'              => $this->request->ajax(),
            'origin'            => $this->request->header('origin'),
            'client_ip'         => $this->getClientIp(),
            'server_ip'         => $this->request->server('SERVER_ADDR'),
            'env'               => $this->env,
            'user_agent'        => $this->request->header('User-Agent'),
            'x_amzn_trace_id'   => $this->request->header('X-Amzn-Trace-Id'),
            'x-razorpay-request-id' => $this->request->header('X-Razorpay-Request-Id'),
            'x-dashboard-user-id' => $this->request->header('x-dashboard-user-id'),
            'x-dashboard-merchant-id' => $this->request->header('x-dashboard-merchant-id'),
            'X-edge-user-jti' => $this->request->header('X-Edge-User-Jti')                     // dashboard bypass via edge
        ];

        return $serverData;
    }

    protected function getUserData()
    {
        $headers = ApiRequest::getHeaders();

        // Filter the $headers array to only include headers that start with "X-"
        // and are not present in the $this->BLACK_LISTED_HEADERS array.
        $data = array_filter($headers, function($key)
        {
            return (substr($key, 0, 2) === "X-")  && !in_array($key, $this->BLACK_LISTED_HEADERS);
        }, ARRAY_FILTER_USE_KEY);

        $app = App::getFacadeRoot();

        $user = $app['session']->get('dashboard_user_payload');

        if(empty($user) === false)
        {
            $data['user_id'] = $user->id ?? null;
        }

        $data['merchant_id']  = $app['session']->get('current_merchant_id') ?? null;

        return $data;
    }

    protected function unsetUrlForSensitiveUrls(& $serverData)
    {
        $sensitiveUrls = Route::getDoNotLogURLs();

        if (in_array($serverData['uri'], $sensitiveUrls))
        {
            unset($serverData['url']);
        }
    }

    protected function getClientIp()
    {
        $request = $this->request;

        $clientIp = $request->headers->get('X_FORWARDED_FOR');

        if ($clientIp === null)
        {
            $clientIp = $request->getClientIp();
        }

        return $clientIp;
    }
}
