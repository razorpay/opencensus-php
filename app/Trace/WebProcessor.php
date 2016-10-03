<?php

namespace RZP\Trace;

use App;
use RZP\Http\Route;
use Request;
use RZP\Exception;

/**
 * Injects url/method and remote IP of the current web request in all records
 */
class WebProcessor extends \Monolog\Processor\WebProcessor
{
    protected $request;

    protected $console;

    /**
     * @param mixed $serverData array or object w/ ArrayAccess that provides access to the $_SERVER data
     */
    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->request = $app['request'];

        $this->console = $app->runningInConsole();

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
            'request_id'    => $this->request->getId(),
            'uri'           => $this->request->path(),
            'url'           => $this->request->fullUrl(),
            'route'         => $this->request->route() ? $this->request->route()->getName() : null,
            'method'        => $this->request->method(),
            'ajax'          => $this->request->ajax(),
            'origin'        => $this->request->header('origin'),
            'client_ip'     => $this->request->getRealClientIp(),
            'server_ip'     => $this->request->server('SERVER_ADDR'),
            'referer'       => $this->request->headers->get('referer'),
            'user_agent'    => $this->request->server('HTTP_USER_AGENT'),
            'console'       => $this->console
        );

        $userData = array(
            'dashboard'     => $this->request->headers->get('X-Dashboard'),
            'merchant'      => $this->request->headers->get('X-Dashboard-Merchant'),
            'admin_user'    => $this->request->headers->get('X-Dashboard-Username'),
        );

        $serverData = array_merge($serverData, $userData);

        $this->unsetUrlForSensitiveUrls($serverData);

        $this->cleanSensitiveUrls($serverData);

        return $serverData;
    }

    protected function unsetUrlForSensitiveUrls(& $serverData)
    {
        $sensitiveUrls = Route::getDoNotLogURLs();

        if (in_array($serverData['uri'], $sensitiveUrls))
        {
            unset($serverData['url']);
        }
    }

    protected function cleanSensitiveUrls(& $serverData)
    {
        $sensitiveRoutes = Route::getSensitiveRoutes();

        if (in_array($serverData['route'], $sensitiveRoutes))
        {
            $serverData['url'] = explode('?', $serverData['url'], 2)[0];
        }
    }
}
