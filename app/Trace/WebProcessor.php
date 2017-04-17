<?php

namespace RZP\Trace;

use App;
use RZP\Exception;

/**
 * Injects url/method and remote IP of the current web request in all records
 */
class WebProcessor extends \Monolog\Processor\WebProcessor
{
    protected $request;

    protected $console;

    protected $route;

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->request = $this->app['request'];

        $this->console = $this->app->runningInConsole();

        $this->route = $this->app['api.route'];

        $serverData = $this->getServerData();

        parent::__construct($serverData);
    }

    /**
     * @param  array $record
     * @return array
     * @throws Exception\LogicException
     */
    public function __invoke(array $record)
    {
        if (isset($this->serverData) === false)
        {
            throw new Exception\LogicException('Server data for trace logs not present');
        }

        $this->updateServerDataWithExtraInfo();

        $record['request'] = $this->serverData;

        return $record;
    }

    /**
     * Updates server data with other useful information.
     * Eg. Values from basicauth - merchant_id and dashboard headers.
     *
     * @return
     */
    protected function updateServerDataWithExtraInfo()
    {
        $this->serverData['merchant_id'] = $this->app['basicauth']->getMerchantIdOfKey();

        $this->serverData += $this->app['basicauth']->getDashboardHeaders();
    }

    public function getServerData()
    {
        $headers = $this->request->headers;

        $serverData = array(
            'request_id'    => $this->request->getId(),
            'uri'           => $this->request->path(),
            'url'           => $this->request->fullUrl(),
            'method'        => $this->request->method(),
            'ajax'          => $this->request->ajax(),
            'origin'        => $this->request->header('origin'),
            'application'   => $this->request->header('X-Razorpay-App'),
            'client_ip'     => $this->request->getRealClientIp(),
            'server_ip'     => $this->request->server('SERVER_ADDR'),
            'referer'       => $headers->get('referer'),
            'content_type'  => $headers->get('content-type'),
            'user_agent'    => $this->request->server('HTTP_USER_AGENT'),
            'console'       => $this->console,
            'merchant_id'   => null,
        );

        $this->unsetUrlForSensitiveUrls($serverData);

        return $serverData;
    }

    protected function unsetUrlForSensitiveUrls(& $serverData)
    {
        $sensitiveUrls = $this->route->getDoNotLogURLs();

        if (in_array($serverData['uri'], $sensitiveUrls))
        {
            $serverData['url'] = explode('?', $serverData['url'], 2)[0];
        }
    }
}
