<?php

define( 'MERCHANT_ID', 'hdfc_89050055' );
define( 'TRANSACTION_KEY', 'bGD1hiHgIIWXP6ywu7vxOUDJB0BB8qoT3vvcAe2h5rdZNR21LPtx0SFfyjg6x9uoM1Fsmzc3cDtZ+pMu+07LRsrxwxgDBTXVueNBPxH/BzoKXUIIKbdWtjvnnQJrmfCBv4RFY5xRrFr0RjIZoxXO0syJyTbeVxYumG5JYlksfDX2Z79rhpNs17ILA+I19BaRYdJu9ja/r8hwuqSMQBD11EENYWLlMqe78XL589Xvi9yjO8qQWfjdeunUZ2iw1DGDnk7w3SYmDE4i7UB4y6Yhrjja0PsbHwT0CyisQ3f/ue12r1Z/FWw5fNkzJ1KEYMob8u103yqhReILdV3bck9L1Q==' );
//define( 'WSDL_URL', 'https://ics2wstest.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.78.wsdl' );
const WSDL_URL = 'https://ics2wstest.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.78.wsdl';


class ExtendedClient extends SoapClient {

   function __construct($wsdl, $options = null) {
     parent::__construct($wsdl, $options);
   }

// This section inserts the UsernameToken information in the outgoing SOAP message.
   function __doRequest($request, $location, $action, $version, $one_way = 0) {

     $user = MERCHANT_ID;
     $password = TRANSACTION_KEY;

     $soapHeader = "<SOAP-ENV:Header xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:wsse=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd\"><wsse:Security SOAP-ENV:mustUnderstand=\"1\"><wsse:UsernameToken><wsse:Username>$user</wsse:Username><wsse:Password Type=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText\">$password</wsse:Password></wsse:UsernameToken></wsse:Security></SOAP-ENV:Header>";

     $requestDOM = new DOMDocument('1.0');
     $soapHeaderDOM = new DOMDocument('1.0');

     try {

         $requestDOM->loadXML($request);
	 $soapHeaderDOM->loadXML($soapHeader);

	 $node = $requestDOM->importNode($soapHeaderDOM->firstChild, true);
	 $requestDOM->firstChild->insertBefore(
         	$node, $requestDOM->firstChild->firstChild);

         $request = $requestDOM->saveXML();

	 // printf( "Modified Request:\n*$request*\n" );

     } catch (DOMException $e) {
         die( 'Error adding UsernameToken: ' . $e->code);
     }

     return parent::__doRequest($request, $location, $action, $version);
   }
}