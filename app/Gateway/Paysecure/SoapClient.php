<?php

namespace RZP\Gateway\Paysecure;

class SoapClient extends \SoapClient
{
    function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $namespace = 'https://paysecure/merchant.soap/';

        $request = str_replace( 'SOAP-ENV:', 'soapenv:', $request );
        $request = str_replace( 'xmlns:SOAP-ENV', 'xmlns:soapenv', $request );

        $request = str_replace( 'ns1:', '', $request );
        $request = str_replace( 'ns2:', '', $request );

        $request = str_replace( 'xmlns:ns1', 'xmlns:mer1', $request );
        $request = str_replace( 'xmlns:ns2', 'xmlns:mer', $request );

        // The xmlns attribute must then be added to EVERY function called by this script.
        $request = str_replace( '<CallPaySecure', '<CallPaySecure xmlns="' . $namespace . '"', $request );
        $request = str_replace(
            '<RequestorCredentials',
            '<RequestorCredentials xmlns="https://paysecure/merchant.soap.header/"',
            $request
        );

        $this->__last_request = $request;

        return parent::__doRequest( $request, $location, $action, $version, $one_way = 0 );
    }
}
