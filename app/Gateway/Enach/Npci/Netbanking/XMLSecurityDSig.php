<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

use DOMDocument;
use RobRichards\XMLSecLibs;

class XMLSecurityDSig extends XMLSecLibs\XMLSecurityDSig
{
    const BASE_TEMPLATE = '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo><SignatureMethod /></SignedInfo></Signature>';

    public function __construct($prefix=null)
    {
        $template = self::BASE_TEMPLATE;
        $sigdoc = new DOMDocument();
        $sigdoc->loadXML($template);
        $this->sigNode = $sigdoc->documentElement;
    }
}
