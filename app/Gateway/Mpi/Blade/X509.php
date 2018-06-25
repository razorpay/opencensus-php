<?php

namespace RZP\Gateway\Mpi\Blade;

use phpseclib\File\X509 as BaseX509;

class X509 extends BaseX509
{
    /**
     * Validate a signature
     *
     * Works on X.509 certs, CSR's and CRL's.
     * Returns true if the signature is verified, false if it is not correct or null on error
     *
     * By default returns false for self-signed certs. Call validateSignature(false) to make this support
     * self-signed.
     *
     * The behavior of this function is inspired by {@link http://php.net/openssl-verify openssl_verify}.
     *
     * @param bool $caonly optional
     * @access public
     * @return mixed
     */
    function validateSignature($caonly = true)
    {
        if (!is_array($this->currentCert) || !isset($this->signatureSubject))
        {
            return null;
        }

        /* TODO:
           "emailAddress attribute values are not case-sensitive (e.g., "subscriber@example.com" is the same as "SUBSCRIBER@EXAMPLE.COM")."
            -- http://tools.ietf.org/html/rfc5280#section-4.1.2.6

           implement pathLenConstraint in the id-ce-basicConstraints extension */

        switch (true)
        {
            case isset($this->currentCert['tbsCertificate']):
                // self-signed cert
                switch (true)
                {
                    case !defined('FILE_X509_IGNORE_TYPE') && $this->currentCert['tbsCertificate']['issuer'] === $this->currentCert['tbsCertificate']['subject']:
                    case defined('FILE_X509_IGNORE_TYPE') && $this->getIssuerDN(self::DN_STRING) === $this->getDN(self::DN_STRING):
                        $authorityKey = $this->getExtension('id-ce-authorityKeyIdentifier');
                        $subjectKeyID = $this->getExtension('id-ce-subjectKeyIdentifier');
                        switch (true)
                        {
                            case !is_array($authorityKey):
                            case is_array($authorityKey) && isset($authorityKey['keyIdentifier']) && $authorityKey['keyIdentifier'] === $subjectKeyID:
                                $signingCert = $this->currentCert; // working cert
                        }
                }

                if (!empty($this->CAs))
                {
                    for ($i = 0; $i < count($this->CAs); $i++)
                    {
                        // even if the cert is a self-signed one we still want to see if it's a CA;
                        // if not, we'll conditionally return an error

                        $ca = $signingCert = $this->CAs[$i];

                        switch (true)
                        {
                            case !defined('FILE_X509_IGNORE_TYPE') && $this->currentCert['tbsCertificate']['issuer'] === $ca['tbsCertificate']['subject']:
                            case defined('FILE_X509_IGNORE_TYPE') && $this->getDN(self::DN_STRING, $this->currentCert['tbsCertificate']['issuer']) === $this->getDN(self::DN_STRING, $ca['tbsCertificate']['subject']):
                                $authorityKey = $this->getExtension('id-ce-authorityKeyIdentifier');
                                $subjectKeyID = $this->getExtension('id-ce-subjectKeyIdentifier', $ca);
                                switch (true)
                                {
                                    case !is_array($authorityKey):
                                    case is_array($authorityKey) && isset($authorityKey['keyIdentifier']) && $authorityKey['keyIdentifier'] === $subjectKeyID:
                                        $signingCert = $ca; // working cert
                                        break 3;
                                }
                        }
                    }

                    if (count($this->CAs) == $i && $caonly)
                    {
                        return false;
                    }

                }
                elseif (!isset($signingCert) || $caonly)
                {
                    return false;
                }

                return $this->_validateSignature(
                    $signingCert['tbsCertificate']['subjectPublicKeyInfo']['algorithm']['algorithm'],
                    $signingCert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'],
                    $this->currentCert['signatureAlgorithm']['algorithm'],
                    substr(base64_decode($this->currentCert['signature']), 1),
                    $this->signatureSubject
                );
            case isset($this->currentCert['certificationRequestInfo']):
                return $this->_validateSignature(
                    $this->currentCert['certificationRequestInfo']['subjectPKInfo']['algorithm']['algorithm'],
                    $this->currentCert['certificationRequestInfo']['subjectPKInfo']['subjectPublicKey'],
                    $this->currentCert['signatureAlgorithm']['algorithm'],
                    substr(base64_decode($this->currentCert['signature']), 1),
                    $this->signatureSubject
                );
            case isset($this->currentCert['publicKeyAndChallenge']):
                return $this->_validateSignature(
                    $this->currentCert['publicKeyAndChallenge']['spki']['algorithm']['algorithm'],
                    $this->currentCert['publicKeyAndChallenge']['spki']['subjectPublicKey'],
                    $this->currentCert['signatureAlgorithm']['algorithm'],
                    substr(base64_decode($this->currentCert['signature']), 1),
                    $this->signatureSubject
                );
            case isset($this->currentCert['tbsCertList']):
                if (!empty($this->CAs))
                {
                    for ($i = 0; $i < count($this->CAs); $i++)
                    {
                        $ca = $this->CAs[$i];
                        switch (true)
                        {
                            case !defined('FILE_X509_IGNORE_TYPE') && $this->currentCert['tbsCertList']['issuer'] === $ca['tbsCertificate']['subject']:
                            case defined('FILE_X509_IGNORE_TYPE') && $this->getDN(self::DN_STRING, $this->currentCert['tbsCertList']['issuer']) === $this->getDN(self::DN_STRING, $ca['tbsCertificate']['subject']):
                                $authorityKey = $this->getExtension('id-ce-authorityKeyIdentifier');
                                $subjectKeyID = $this->getExtension('id-ce-subjectKeyIdentifier', $ca);
                                switch (true) {
                                    case !is_array($authorityKey):
                                    case is_array($authorityKey) && isset($authorityKey['keyIdentifier']) && $authorityKey['keyIdentifier'] === $subjectKeyID:
                                        $signingCert = $ca; // working cert
                                        break 3;
                                }
                        }
                    }
                }

                if (!isset($signingCert))
                {
                    return false;
                }

                return $this->_validateSignature(
                    $signingCert['tbsCertificate']['subjectPublicKeyInfo']['algorithm']['algorithm'],
                    $signingCert['tbsCertificate']['subjectPublicKeyInfo']['subjectPublicKey'],
                    $this->currentCert['signatureAlgorithm']['algorithm'],
                    substr(base64_decode($this->currentCert['signature']), 1),
                    $this->signatureSubject
                );

            default:
                return false;
        }
    }
}
