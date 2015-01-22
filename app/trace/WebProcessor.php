<?php

namespace Trace;

use App;
use Http\Route;
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

        $serverData = $this->getServerData();

        parent::__construct($serverData);
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
            'server_ip' => $this->request->server('SERVER_ADDR'));

        $this->unsetUrlForSensitiveUrls($serverData);

        return $serverData;
    }

    protected function unsetUrlForSensitiveUrls(& $serverData)
    {
        $sensitiveUrls = Route::getDoNotLogURLs();

        if (in_array($serverData['uri'], $sensitiveUrls))
        {
            unset(
                $serverData['uri'],
                $serverData['url']);

            $serverData['uri'] = 'payments/create/jsonp';
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
