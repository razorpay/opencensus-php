<?php

namespace RZP\Gateway\Paysecure;

class SoapClient extends \SoapClient
{
    function __doRequest($request, $location, $action, $version, $one_way = 0)
    {

        $namespace = 'https://paysecure/merchant.soap/';
        $newNamespace = 'https://PaySecure/merchant.soap/';

        $request = str_replace( 'SOAP-ENV:', 'soapenv:', $request );
        $request = str_replace( 'xmlns:SOAP-ENV', 'xmlns:soapenv', $request );

        $request = str_replace( 'ns1:', '', $request );
        $request = str_replace( 'ns2:', '', $request );

        $request = str_replace( 'xmlns:ns1', 'xmlns:mer1', $request );
        $request = str_replace( 'xmlns:ns2', 'xmlns:mer', $request );

        // The xmlns attribute must then be added to EVERY function called by this script.
        $request = str_replace( '<CallPaySecure', '<CallPaySecure xmlns="' . $namespace . '"', $request );

//        $dom = new \DOMDocument("1.0");
//        $dom->preserveWhiteSpace = false;
//        $dom->formatOutput = true;
//        $dom->loadXML($request);
//        echo "<pre>".htmlentities($dom->saveXML())."</pre>";
//        die;

        $this->__last_request = $request;

        return parent::__doRequest( $request, $location, $action, $version, $one_way = 0 );
    }
}
