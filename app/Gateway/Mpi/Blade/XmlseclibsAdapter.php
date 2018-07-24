<?php

namespace RZP\Gateway\Mpi\Blade;

use DOMDocument;
use DOMNode;
use DomElement;
use DomXPath;
use Carbon\Carbon;
use RobRichards\XMLSecLibs\XMLSecEnc;
use RuntimeException;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use UnexpectedValueException;

/**
 * XmlDSig adapter based on "xmlseclibs" library
 *
 * http://code.google.com/p/xmlseclibs/
 */
class XmlseclibsAdapter
{
    /**
     * Algorithm identifiers
     *
     * @see http://www.w3.org/TR/xmldsig-core/#sec-AlgID
     */
    /* Digest */
    /** @var string SHA1 Digest Algorithm URI */
    const SHA1 = 'http://www.w3.org/2000/09/xmldsig#sha1';
    /* Signature */
    /** @var string DSA with SHA1 (DSS) Sign Algorithm URI */
    const DSA_SHA1 = 'http://www.w3.org/2000/09/xmldsig#dsa-sha1';
    /** @var string RSA with SHA1 Sign Algorithm URI */
    const RSA_SHA1 = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    /* Canonicalization */
    const REC_XML_C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    const XML_C14N = 'http://www.w3.org/2001/10/xml-exc-c14n#';
    /* Transform */
    const ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    /**
     * Private key
     *
     * @var string
     */
    protected $privateKey;

    /**
     * Public key
     *
     * @var string
     */
    protected $publicKey;

    /**
     * Signature algorithm URI. By default RSA with SHA1
     *
     * @var string
     */
    protected $keyAlgorithm = self::RSA_SHA1;

    /**
     * Digest algorithm URI. By default SHA1
     *
     * @var string
     * @see AdapterInterface::SHA1
     */
    protected $digestAlgorithm = self::SHA1;

    /**
     * Canonical algorithm URI. By default C14N
     *
     * @var string
     * @see AdapterInterface::XML_C14N
     */
    protected $canonicalMethod = self::XML_C14N;

    /**
     * Transforms list
     *
     * @var array
     * @see AdapterInterface::ENVELOPED
     */
    protected $transforms = [];

    protected $rootCertFingerprints = [];

    public function setPrivateKey($privateKey, $algorithmType = self::RSA_SHA1)
    {
        $this->privateKey   = $privateKey;
        $this->keyAlgorithm = $algorithmType;

        return $this;
    }

    public function setRootCertFingerprints(array $rootCertFingerprints)
    {
        $this->rootCertFingerprints = $rootCertFingerprints;
    }

    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getPublicKey(DOMNode $dom = null)
    {
        if ($dom)
        {
            $this->setPublicKeyFromNode($dom);
        }

        if ((!$this->publicKey) and ($this->privateKey))
        {
            $this->setPublicKeyFromPrivateKey($this->privateKey);
        }

        return $this->publicKey;
    }

    public function getKeyAlgorithm()
    {
        return $this->keyAlgorithm;
    }

    public function setDigestAlgorithm($algorithmType = self::SHA1)
    {
        $this->digestAlgorithm = $algorithmType;

        return $this;
    }

    public function setCanonicalMethod($methodType = self::XML_C14N)
    {
        $this->canonicalMethod = $methodType;

        return $this;
    }

    public function addTransform($transformType)
    {
        $this->transforms[] = $transformType;

        return $this;
    }

    public function sign(DOMDocument $data, DOMNode $appendTo = null, $options = ['force_uri' => true])
    {
        if (null === $this->privateKey)
        {
            throw new RuntimeException(
                'Missing private key. Use setPrivateKey to set one.');
        }

        $objKey = new XMLSecurityKey(
            $this->keyAlgorithm,
            [
                 'type' => 'private',
             ]
        );
        $objKey->loadKey($this->privateKey);

        if (empty($appendTo))
        {
            $appendTo = $data->documentElement;
        }

        $objXMLSecDSig = new XMLSecurityDSig();
        $objXMLSecDSig->addReference($data, $this->digestAlgorithm, $this->transforms, $options);
        $objXMLSecDSig->setCanonicalMethod($this->canonicalMethod);
        $objXMLSecDSig->sign($objKey, $appendTo);

        /* Add associated public key */
        if ($this->getPublicKey())
        {
            $objXMLSecDSig->add509Cert($this->getPublicKey());
        }
    }

    public function assert($bool, $msg = 'assertion failed')
    {
        if ($bool !== true)
        {
            throw new RuntimeException($msg);
        }
    }

    /**
     * This method is implemented using staticLocateKeyInfo
     * as the base code.
     * @param  DOMNode $node Signature node
     */
    protected function locateAllCerts(DomElement $signatureNode)
    {
        $doc = $signatureNode->ownerDocument;
        $this->assert($doc != null);

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('xmlsecenc', XMLSecEnc::XMLENCNS);
        $xpath->registerNamespace('xmlsecdsig', XMLSecurityDSig::XMLDSIGNS);

        $query = "./xmlsecdsig:KeyInfo";
        $keyInfoNode = $xpath->query($query, $signatureNode);

        $x509DataNode = $keyInfoNode->item(0);

        if (!$x509DataNode)
        {
            throw new \Exception("X509_CERTIFICATE_NOT_FOUND");
        }

        $certNodes = $x509DataNode->getElementsByTagName('X509Certificate');

        // TODO: Check VISA specs to get the real count
        $this->assert($certNodes->length > 1);

        // This is the keys array we will return
        $certs = [];

        foreach ($certNodes as $certNode)
        {
            $cert = new X509;

            // Parse the node into a PEM cert
            $certText = $this->parseCert($certNode);

            $cert->loadX509($certText);

            // We need both the cert object and PEM text
            // to validate certain things because of the
            // way the APIs are currently designed
            $certs[] = [
                'x509' => $cert,
                'pem'  => $certText
            ];
        }

        return $certs;
    }

    /**
     * This parses a X509Certificate node and returns
     * a PEM encoded certificate
     * @param  DOMNode $node X509Certificate Node
     * @return string PEM Encoded certificate
     */
    protected function parseCert(DOMNode $node)
    {
        $cert = $node->textContent;
        $cert = str_replace(array("\r", "\n"), "", $cert);
        return "-----BEGIN CERTIFICATE-----\n".
            chunk_split($cert, 64, "\n").
            "-----END CERTIFICATE-----\n";
    }

    public function verify($dom)
    {
        $objXMLSecDSig = new XMLSecurityDSig();

        // This holds the entire Digital Signature object with the entire DOM
        // This makes sure that there is a signature present in the PARes.
        $signatureNode = $objXMLSecDSig->locateSignature($dom);

        $this->assert($signatureNode !== null, 'Signature should be located');

        /**
         * Canonicalization is the process of making sure that the data we are
         * verifying is in the exact same format as the data that was signed.
         * XML Signatures implement this *very thoroughly* and weirdely.
         * The primary change is to ensure the encoding and newlines (CR/LF)
         * 3DSecure, thankfully does not include any whitespace in the response
         * which makes this easier. Canonicalization methods (there are many)
         * are provided in the SignatureInfo tag as well.
         */
        $objXMLSecDSig->canonicalizeSignedInfo();

        /**
         * This is where VISA kinda breaks the spec (or used to). We need
         * to tell our signing library to treat the `id` attribute inside
         * PARes specially so as to get the reference working (just like CSS)
         *
         * However, XML does not count id as special by default, which means
         * #id references don't work, hence we need to add this here
         */
        $objXMLSecDSig->idKeys = ['id'];

        /**
         * This does 2 things:
         * 1. Makes sure that the Content is referencable
         * 2. Makes sure that the Content Digest matches
         *
         * Content = Data that is being signed. This is figured out by the
         * URI="#id" attribute in the Signature/SignedInfo/Reference tag
         *
         * Provided that such a tag exists and can be referenced, the first
         * check passes. After that, we calculate a Digest of the entire
         * referenced content (in this case the entire PARes tag). We have
         * already done canonicalization so things like whitespace, newline
         * and encoding are taken care of. The Digest is just a sha1 hash
         * of the hex equivalent. Which means:
         *
         * <a>hello</a> is converted to 3c 61 3e 68 65 6c 6c 6f 3c 2f 61 3e
         * which is converted to uppercase 3C613E68656C6C6F3C2F613E without
         * whitespace and then hashed using sha1 to
         * dbc1a3a96faea45a41249db82484c356522bc6b6
         *
         * which is then converted to base64
         * ZGJjMWEzYTk2ZmFlYTQ1YTQxMjQ5ZGI4MjQ4NGMzNTY1MjJiYzZiNg==
         *
         * Which is considered the digest value. This digest value
         * is then matched against the DigestValue in the Signature:
         *
         * Signature/SignedInfo/Reference/DigestValue tag
         *
         * Both of these must match for the second check to pass.
         */
        $refValid = $objXMLSecDSig->validateReference();

        $this->assert($refValid, 'Reference should be valid');

        /**
         * There can be multiple types of signing keys in a XML signature. These
         * are all public keys and we try to locate a valid KeyInfo tag here.
         * This will include the algorithm and type of key being used.
         *
         * The type will be "public"
         *
         * Note that keys are not present with us at this point, just the algo
         * being used
         */
        $key = $objXMLSecDSig->locateKey();

        /**
         * At this point we know whether the signature is valid or not.
         * However, we have used the cert provided in the PARes itself
         * to verify the signature, which is not very secure. We need
         * to make sure that the cert is verifiable using our own
         * CERT STORE.
         */
        list($certVerify, $leaf) = $this->verifyCertStore($signatureNode);

        if ($certVerify === false)
        {
            return false;
        }

        /**
         * We iterate through the KeyInfo tag here and figure out what type
         * of key is being used. This is by the first child of KeyInfo, which
         * is X509Data in our case.
         *
         * We then look for valid X509Certificate tags and pick the first one
         * as the "Signing Certificate". The $key being passed is currently
         * only holding the "type=public" and the algorithm (RSA-SHA1) now.
         *
         * It will call the loadKey method on the $key internally, which
         * will populate the x509Certificate inside the key along with its
         * thumbprint (which is a sha1 of the entire cert).
         */
        $key->loadKey($leaf['pem'], false, true);
        // XMLSecEnc::staticLocateKeyInfo($key, $signatureNode);

        // Make sure that we have a valid key here.
        $this->assert($key->key !== null, "Key Locator should work");

        /**
         * This just calls openssl_verify internally, which needs:
         * $data, $signature, $public_key, $algo
         *
         * The data is calculated during canonicalization (PARes tag entirely)
         * The Signature is stored in SignatureValue tag
         * The publicKey is stored in $objKeyInfo
         * The algo we already know (from SignatureMethod tag)
         *
         * Using the public key, we sign the content and verify it against
         * the one we were provided in the PARes
         *
         * This returns an integer:
         *
         * 1 = verification passed
         * 0 = verification failed
         */
        $signatureVerifyResult = $objXMLSecDSig->verify($key);

        /**
         * Since the above internally calls openssl_verify, we are returned
         * 1 for success and 0 for failure instead of true/false
         */

        return ($signatureVerifyResult === 1 and $certVerify);
    }

    protected function verifyCertChain($start, $leaf, $intermediate, $root)
    {
        // Objects are passed by reference, and we need a copy to ensure
        // that they don't get tainted
        $leafCert = clone $leaf['x509'];
        $intermediateCert = clone $intermediate['x509'];

        $result = ($start and $this->verifyCertSignedBy($leafCert, $intermediate['pem']));
        return ($result and $this->verifyCertSignedBy($intermediateCert, $root['pem']));
    }

    /**
     * There are 3 parts to verifying a cert store:
     *
     * 1. Verify entire chain is signed properly
     *    ie C1 signs C2 which signs C3
     * 2. Verify the validity of each certificate
     *    by checking expiry with current date
     * 3. Verify the root certificate to be the same
     *    as the one given to us by VISA
     */
    protected function verifyCertStore(DomElement $signatureNode)
    {
        // An Array of X509 certificate objects
        $certs = $this->locateAllCerts($signatureNode);

        // TODO: Change to > MIN_CERT_VALUE?
        $this->assert(count($certs) == 3, "There should be 3 certs in the chain");

        foreach ($certs as $cert)
        {
            // if a single cert is invalid, return failure
            if ($this->verifySingleCert($cert['x509']) === false)
            {
                return [false, null];
            }
        }

        // Dump values for all signing combinations
        $verifyFingerprint = false;

        // find the root certificate
        foreach ($certs as $key => $cert)
        {
            $fp = XMLSecurityKey::getRawThumbprint($cert['pem']);

            if (in_array($fp, $this->rootCertFingerprints, true) === true)
            {
                $rootCert = $cert;

                $rootFingerprint = $fp;

                $verifyFingerprint = true;

                // remove the root certificate from the list of certificates,
                // we will permute over the remaining certificates
                unset($certs[$key]);
                break;
            }
        }

        $result = $verifyFingerprint;
        $leaf = null;

        if ($verifyFingerprint === true)
        {
            $certs = array_values($certs);

            // If either of these succeeds, we are good with the chain
            $verify1 = $this->verifyCertChain($result, $certs[0], $certs[1], $rootCert);
            $verify2 = $this->verifyCertChain($result, $certs[1], $certs[0], $rootCert);

            $leaf = ($verify1 === true) ? $certs[0] : $certs[1];

            $result = ($verify1 or $verify2);
        }

        return [$result, $leaf];
    }

    /**
     * Verify that $cert is signed by $signer
     * @param  X509   $cert   [description]
     * @param  string $signer PEM Encoded signer
     * @return boolean
     */
    protected function verifyCertSignedBy(X509 $cert, $signer)
    {
        $cert->loadCA($signer);

        // Ensure that we are verifying a direct signer
        // Instead of using the entire CA bundle
        $this->assert(count($cert->CAs) === 1);

        return $cert->validateSignature(false);
    }

    protected function verifySingleCert(X509 $cert)
    {
        return $cert->validateDate(Carbon::now());
    }

    /**
     * Try to extract the public key from DOM node
     *
     * Sets publicKey and keyAlgorithm properties if success.
     *
     * @see publicKey
     * @see keyAlgorithm
     * @param DOMNode $dom
     * @return bool `true` If public key was extracted or `false` if cannot be possible
     */
    protected function setPublicKeyFromNode(DOMNode $dom)
    {
        // try to get the public key from the certificate
        $objXMLSecDSig = new XMLSecurityDSig();
        $objDSig       = $objXMLSecDSig->locateSignature($dom);
        if (!$objDSig)
        {
            return false;
        }

        $objXMLSecDSig->canonicalizeSignedInfo();

        $objXMLSecDSig->idKeys = ['id'];

        $this->assert($objXMLSecDSig->validateReference());

        $objKey = $objXMLSecDSig->locateKey();
        if (!$objKey)
        {
            return false;
        }

        XMLSecEnc::staticLocateKeyInfo($objKey, $objDSig);

        $this->publicKey    = $objKey->getX509Certificate();
        $this->keyAlgorithm = $objKey->getAlgorith();

        return true;
    }

    /**
     * Try to extract the public key from private key
     *
     * @see publicKey
     * @param string $privateKey
     * @return bool `true` If public key was extracted or `false` if cannot be possible
     */
    protected function setPublicKeyFromPrivateKey($privateKey)
    {
        return openssl_pkey_export(
            openssl_pkey_get_public($privateKey),
            $this->publicKey
        );
    }
}
