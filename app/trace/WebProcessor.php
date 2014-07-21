<?php

namespace Trace;

use Request;

/**
 * Injects url/method and remote IP of the current web request in all records
 */
class WebProcessor extends \Monolog\Processor\WebProcessor
{
    /**
     * @param mixed $serverData array or object w/ ArrayAccess that provides access to the $_SERVER data
     */
    public function __construct()
    {
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
        $record['extra']['request'] = $this->serverData;

        return $record;
    }

    public function getServerData()
    {
        $serverData = array(
            'uri' => Request::path(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'ajax' => Request::ajax(),
            'origin' => Request::header('origin'),
            'client_ip' => Request::getClientIp(),
            'server_ip' => Request::server('SERVER_ADDR'));

        $this->unsetUrlForSensitiveUrls($serverData);

        return $serverData;
    }

    protected function unsetUrlForSensitiveUrls(& $serverData)
    {
        $sensitiveUrls = \Http\URL::getDoNotLogURLs();

        if (in_array($serverData['url'], $sensitiveUrls))
        {
            unset(
                $server['uri'],
                $server['url']);
        }
    }
}
