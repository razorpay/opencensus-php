<?php

class ExtendedClient extends SoapClient 
{
    protected $user;
    protected $password;

    public function __construct($wsdl, $options = null, $auth) 
    {
        parent::__construct($wsdl, $options);

        $this->user = $auth['username'];

        $this->password = $auth['password'];
    }
 
// This section inserts the UsernameToken information in the outgoing SOAP message.
    public function __doRequest($request, $location, $action, $version, $one_way = 0) 
    {
        $user = $this->user;
        $password = $this->password;

        $soapHeader = "<SOAP-ENV:Header xmlns:SOAP-ENV=\"http://schemas.xmlsoap.".
                        "org/soap/envelope/\" xmlns:wsse=\"http://docs.oasis-open".
                        ".org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.".
                        "xsd\"><wsse:Security SOAP-ENV:mustUnderstand=\"1\"><wsse".
                        ":UsernameToken><wsse:Username>$user</wsse:Username><wsse".
                        ":Password Type=\"http://docs.oasis-open.org/wss/2004/01/".
                        "oasis-200401-wss-username-token-profile-1.0#PasswordText".
                        "\">$password</wsse:Password></wsse:UsernameToken></wsse:".
                        "Security></SOAP-ENV:Header>";

        $requestDOM = new DOMDocument('1.0');
        $soapHeaderDOM = new DOMDocument('1.0');

        try 
        {

            $requestDOM->loadXML($request);
  	        $soapHeaderDOM->loadXML($soapHeader);

          	$node = $requestDOM->importNode($soapHeaderDOM->firstChild, true);
          	$requestDOM->firstChild->insertBefore(
                   	$node, $requestDOM->firstChild->firstChild);

            $request = $requestDOM->saveXML();

  	        // printf( "Modified Request:\n*$request*\n" );

        }
        catch (DOMException $e) 
        {
            throw new Exception("Error Processing Request", 1);
           
        }

        return parent::__doRequest($request, $location, $action, $version);
    }
}