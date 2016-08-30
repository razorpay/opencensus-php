<?php

namespace RZP\Gateway\FirstData;

use App;
use SoapClient;
use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class FirstDataSoapClient extends SoapClient
{
    const URI                       = 'uri';
    const LOCATION                  = 'location';

    protected $auth;

    public function __construct($url, $namespace, $auth)
    {
        parent::__construct(null, array(
                                            self::URI       => $namespace,
                                            self::LOCATION  => $url,
                                            'trace'         => true,
                                            'typemap'       => array(
                                                    array(
                                                            "type_ns"  => "http://ipg-online.com/ipgapi/schemas/ipgapi",
                                                            "type_name" => "ipgapi",
                                                        ),
                                                    array(
                                                            "type_ns"  => "http://ipg-online.com/ipgapi/schemas/v1",
                                                            "type_name" => "v1",
                                                        ),
                                                )
                                        ));

        $this->auth = $auth;
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
    }

    public function __doRequest($request, $location, $action, $version)
    {

        s($request);
        s($location);
        s($action);
        sd($version);

        return parent::__doRequest($request, $location, $action, $version);
    }
}