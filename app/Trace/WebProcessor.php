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

        $this->context = App::make('config')->get('app.context');

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
        $serverData = array(
            'uri'       => $this->request->path(),
            'url'       => $this->request->fullUrl(),
            'method'    => $this->request->method(),
            'ajax'      => $this->request->ajax(),
            'origin'    => $this->request->header('origin'),
            'client_ip' => $this->getClientIp(),
            'server_ip' => $this->request->server('SERVER_ADDR'),
            'context'   => $this->context);

        return $serverData;
    }

    protected function getUserData()
    {
        $headers = ApiRequest::getHeaders();

        return array_filter($headers, function($key)
        {
            return (substr($key, 0, 2) === "X-");
        }, ARRAY_FILTER_USE_KEY);
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
