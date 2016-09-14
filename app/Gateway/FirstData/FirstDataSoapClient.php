<?php

namespace RZP\Gateway\FirstData;

use App;
use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class FirstDataSoapClient
{
    const URI                       = 'uri';
    const LOCATION                  = 'location';

    protected $auth;

    protected $curl;

    public function __construct($url, $request)
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];

        $body = $this->wrapSoap($request);

        $this->curl = curl_init($url);
        // Request type is post
        curl_setopt($this->curl, CURLOPT_POST, 1);
        // Content type is text/xml
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
        // Authorization method is BASIC
        curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // Fill request body
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
        s($body);
        //
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

        $this->setCredentials($this->curl);
    }

    protected function setCredentials(&$curl)
    {
        // Verify the server certificate:
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        // supplying your credentials:
        curl_setopt($curl, CURLOPT_USERPWD, "WS3344000025._.2:3aU8bXkb9f");
        // setting the path where cURL can find the client certificate:
        curl_setopt($curl, CURLOPT_SSLCERT, "/Users/harmansingh/Documents/API credentials/IPG_Certificate_WS3344000025._.2/WS3344000025._.2.p12");
        // setting the path where cURL can find the client certificate’s private key:
        curl_setopt($curl, CURLOPT_SSLKEY, "/Users/harmansingh/Documents/API credentials/IPG_Certificate_WS3344000025._.2/WS3344000025._.2.key");
        // setting the key password:
        curl_setopt($curl, CURLOPT_SSLKEYPASSWD, "ivveETWHk2");
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);
    }

    protected function wrapSoap($content)
    {
        $soapWrapper = "<?xml version='1.0' encoding='UTF-8'?><SOAP-ENV:Envelope xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/'><SOAP-ENV:Body><ipgapi:IPGApiOrderRequest xmlns:ipgapi='http://ipg-online.com/ipgapi/schemas/ipgapi' xmlns:v1='http://ipg-online.com/ipgapi/schemas/v1'>".$content."</ipgapi:IPGApiOrderRequest></SOAP-ENV:Body></SOAP-ENV:Envelope>";

        return $soapWrapper;
    }

    public function execute()
    {
        $output = curl_exec($this->curl);
        if ( $output == FALSE )
        {
            s(curl_error($this->curl));
            s(curl_errno($this->curl));
        }
        curl_close($this->curl);
        return $output;
    }
}