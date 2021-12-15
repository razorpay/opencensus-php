<?php

namespace App\Trace;

use App;
use Http\Route;
use Razorpay\Api\Request as ApiRequest;
use Request;

/**
 * Injects url/method and remote IP of the current web request in all records
 */
class WebProcessor extends \Monolog\Processor\WebProcessor
{
    protected $request;

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
    public function __invoke(array $record)
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
        $serverData = [
            'request_id'        => $this->request->getId(),
            'uri'               => $this->request->path(),
            'url'               => $this->request->fullUrl(),
            'method'            => $this->request->method(),
            'ajax'              => $this->request->ajax(),
            'origin'            => $this->request->header('origin'),
            'client_ip'         => $this->getClientIp(),
            'server_ip'         => $this->request->server('SERVER_ADDR'),
            'env'               => $this->env,
            'user_agent'        => $this->request->header('User-Agent'),
            'x_amzn_trace_id'   => $this->request->header('X-Amzn-Trace-Id')
        ];

        return $serverData;
    }

    protected function getUserData()
    {
        $headers = ApiRequest::getHeaders();

        $data = array_filter($headers, function($key)
        {
            return (substr($key, 0, 2) === "X-");
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
