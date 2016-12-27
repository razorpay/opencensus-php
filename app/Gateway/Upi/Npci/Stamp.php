<?php

namespace RZP\Gateway\Upi\Npci;

use DOMXPath;
use Exception;
use DOMElement;
use RobRichards\XMLSecLibs;

/**
 * This class is extended from the Original
 * using code from https://github.com/robrichards/xmlseclibs/pull/75
 *
 * @license http://www.opensource.org/licenses/bsd-license.php  BSD License
 */
class Stamp extends XMLSecLibs\XMLSecurityDSig
{
    /**
     * @param DOMElement $parentRef
     * @param string $cert
     * @param bool $isPEMFormat
     * @param bool $isURL
     * @param null|DOMXPath $xpath
     * @param null|array $options
     * @throws Exception
     */
    public static function staticAddPublicKey($parentRef, $publicKey)
    {
        if (! $parentRef instanceof DOMElement) {
            throw new Exception('Invalid parent Node parameter');
        }
        $baseDoc = $parentRef->ownerDocument;
        if (empty($xpath)) {
            $xpath = new DOMXPath($parentRef->ownerDocument);
            $xpath->registerNamespace('secdsig', self::XMLDSIGNS);
        }
        $query = "./secdsig:KeyInfo";
        $nodeset = $xpath->query($query, $parentRef);
        $keyInfo = $nodeset->item(0);
        $dsig_pfx = '';
        if (! $keyInfo) {
            $pfx = $parentRef->lookupPrefix(self::XMLDSIGNS);
            if (! empty($pfx)) {
                $dsig_pfx = $pfx.":";
            }
            $inserted = false;
            $keyInfo = $baseDoc->createElementNS(self::XMLDSIGNS, $dsig_pfx.'KeyInfo');
            $query = "./secdsig:Object";
            $nodeset = $xpath->query($query, $parentRef);
            if ($sObject = $nodeset->item(0)) {
                $sObject->parentNode->insertBefore($keyInfo, $sObject);
                $inserted = true;
            }
            if (! $inserted) {
                $parentRef->appendChild($keyInfo);
            }
        } else {
            $pfx = $keyInfo->lookupPrefix(self::XMLDSIGNS);
            if (! empty($pfx)) {
                $dsig_pfx = $pfx.":";
            }
        }

        $publicKeyResource = openssl_pkey_get_public($publicKey);

        $pubkeyInfo = openssl_pkey_get_details($publicKeyResource);

        if ($pubkeyInfo['type'] === OPENSSL_KEYTYPE_RSA) {
            $keyValueNode = $baseDoc->createElementNS(self::XMLDSIGNS, $dsig_pfx.'KeyValue');
            $keyInfo -> appendChild($keyValueNode);
            $rsaKeyValueNode = $baseDoc->createElementNS(self::XMLDSIGNS, $dsig_pfx.'RSAKeyValue');
            $keyValueNode -> appendChild($rsaKeyValueNode);
            $rsaKeyValueNode -> appendChild($baseDoc->createElementNS(self::XMLDSIGNS, $dsig_pfx.'Modulus', base64_encode($pubkeyInfo["rsa"]["n"])));
            $rsaKeyValueNode -> appendChild($baseDoc->createElementNS(self::XMLDSIGNS, $dsig_pfx.'Exponent', base64_encode($pubkeyInfo["rsa"]["e"])));
        }
    }

    public function addKeyInfo($publicKey)
    {
        if ($xpath = $this->getXPathObjNew()) {
            static::staticAddPublicKey($this->sigNode, $publicKey);
        }
    }

    /**
     * Returns the XPathObj or null if xPathCtx is set and sigNode is empty.
     *
     * @return DOMXPath|null
     */
    protected function getXPathObjNew()
    {
        if (! empty($this->sigNode))
        {
            $xpath = new DOMXPath($this->sigNode->ownerDocument);

            $xpath->registerNamespace('secdsig', self::XMLDSIGNS);

            return $xpath;
        }

        return null;
    }
}
