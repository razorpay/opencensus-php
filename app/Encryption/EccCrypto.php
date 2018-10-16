<?php

namespace RZP\Encryption;

use RZP\Constants\HashAlgo;
use RZP\Exception\RuntimeException;

use GMP;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Curves\SecgCurve;
use Mdanter\Ecc\Math\GmpMathInterface;
use Mdanter\Ecc\Crypto\Signature\Signer;
use Mdanter\Ecc\Primitives\GeneratorPoint;
use Mdanter\Ecc\Crypto\Signature\SignHasher;
use Mdanter\Ecc\Random\RandomGeneratorFactory;
use Mdanter\Ecc\Serializer\Signature\DerSignatureSerializer;
use Mdanter\Ecc\Serializer\PublicKey\DerPublicKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\PemPublicKeySerializer;
use Mdanter\Ecc\Serializer\PrivateKey\PemPrivateKeySerializer;
use Mdanter\Ecc\Serializer\PrivateKey\DerPrivateKeySerializer;

class EccCrypto
{
    /**
     * Tells the adaptor to run in debug mode
     */
    const DEBUG         = 'debug';

    /**
     * Name of curve used
     */
    const CURVE         = 'curve';

    /**
     * Algorithm used in hashing and randomK
     */
    const ALGORITHM     = 'algorithm';

    /**
     * Base of keys could be 64 currently
     * Later: if required can be 16
     */
    const BASE          = 'base';

    /**
     * Randomization reduces the leak chances of private key
     */
    const DERANDOM_SIGN = 'derandom_sign';

    /**
     * EC Private key, used while signing
     */
    const PRIVATE_KEY   = 'private_key';

    /**
     * EC Public key, used while verifying
     */
    const PUBLIC_KEY    = 'public_key';

    /**
     * @var string
     */
    protected $pKey;

    /**
     * @var string
     */
    protected $pubKey;

    /**
     * @var GmpMathInterface
     */
    protected $adaptor;

    /**
     * @var GeneratorPoint
     */
    protected $generator;

    /**
     * Default values for allowed config keys
     *
     * @var array
     */
    protected $config = [
        self::DEBUG             => false,
        self::CURVE             => SecgCurve::NAME_SECP_256K1,
        self::ALGORITHM         => HashAlgo::SHA256,
        self::DERANDOM_SIGN     => true,
        self::BASE              => 64,
        self::PRIVATE_KEY       => null,
        self::PUBLIC_KEY        => null,
    ];

    /**
     * EccCrypto constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);

        $this->adaptor = $this->getNewAdaptor();

        $this->generator = $this->getNewGenerator();
    }

    /*------------------------------------------------------------
      --------------------------- SIGN ---------------------------
      ------------------------------------------------------------*/

    public function sign2Hex(string $text)
    {
        return bin2hex($this->sign($text));
    }

    public function sign2Base64(string $text)
    {
        return base64_encode($this->sign($text));
    }

    public function sign(string $text)
    {
        $hash = $this->generateHash($text);

        $randomK = $this->generateRandomK($hash);

        $signature = $this->generateSignature($hash, $randomK);

        return $signature;
    }

    /*------------------------------------------------------------
      ------------------------- VERIFY ---------------------------
      ------------------------------------------------------------*/

    public function verifyBase64(string $text, string $signature)
    {
        try
        {
            return $this->verify($text, base64_decode($signature));
        }
        catch (\Throwable $exception)
        {
            throw new RuntimeException('Failed to verify base64 encoded signature',
                [
                    'signature' => $signature,
                ],
                $exception);
        }
    }

    public function verifyHex(string $text, string $signature)
    {
        try
        {
            return $this->verify($text, hex2bin($signature));
        }
        catch (\Throwable $exception)
        {
            throw new RuntimeException('Failed to verify hex encoded signature',
                [
                    'signature' => $signature,
                ],
                $exception);
        }
    }

    public function verify(string $text, string $signature)
    {
        $sign = $this->parseSignature($signature);

        $hash = $this->generateHash($text);

        $signer = new Signer($this->adaptor);

        return $signer->verify($this->getPublicKey(), $sign, $hash);
    }

    /*------------------------------------------------------------
      ------------------------ PRIVATE ---------------------------
      ------------------------------------------------------------*/

    private function generateSignature(GMP $hash, GMP $randomK)
    {
        $signer = new Signer($this->adaptor);

        $signature = $signer->sign($this->getPrivateKey(), $hash, $randomK);

        $serializer = new DerSignatureSerializer();

        return $serializer->serialize($signature);
    }

    private function parseSignature(string $signature)
    {
        $serializer = new DerSignatureSerializer();

        return $serializer->parse($signature);
    }

    private function generateRandomK(GMP $hash)
    {
        if ($this->config[self::DERANDOM_SIGN] === true)
        {
            $random = RandomGeneratorFactory::getHmacRandomGenerator(
                          $this->getPrivateKey(),
                          $hash,
                          $this->config[self::ALGORITHM]);
        }
        else
        {
            $random = RandomGeneratorFactory::getRandomGenerator();
        }

        return $random->generate($this->generator->getOrder());
    }

    private function generateHash(string $text)
    {
        return (new SignHasher($this->config[self::ALGORITHM], $this->adaptor))->makeHash($text, $this->generator);
    }

    private function getNewAdaptor()
    {
        return EccFactory::getAdapter($this->config[self::DEBUG]);
    }

    private function getNewGenerator()
    {
        switch ($this->config[self::CURVE])
        {
            case SecgCurve::NAME_SECP_256K1:
                return EccFactory::getSecgCurves()->generator256k1();

            default:
                throw new RuntimeException('Invalid curve passed',
                    [
                        self::CURVE => $this->config[self::CURVE],
                    ]);
        }
    }

    private function getPrivateKey()
    {
        if (empty($this->pKey) === true)
        {
            if (empty($this->config[self::PRIVATE_KEY]) === true)
            {
                throw new RuntimeException('Config does not have private key');
            }

            $pem = new PemPrivateKeySerializer(new DerPrivateKeySerializer($this->adaptor));

            $this->pKey = $pem->parse($this->config[self::PRIVATE_KEY]);
        }

        return $this->pKey;
    }

    private function getPublicKey()
    {
        if (empty($this->pubKey) === true)
        {
            if (empty($this->config[self::PUBLIC_KEY]) === true)
            {
                throw new RuntimeException('Config does not have public key');
            }

            $pem = new PemPublicKeySerializer(new DerPublicKeySerializer($this->adaptor));

            $this->pubKey = $pem->parse($this->config[self::PUBLIC_KEY]);
        }

        return $this->pubKey;
    }

    private function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }
}
