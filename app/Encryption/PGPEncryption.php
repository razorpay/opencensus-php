<?php

namespace RZP\Encryption;

use RZP\Exception;

class PGPEncryption extends Encryption
{
    const PUBLIC_KEY  = 'public_key';
    const PRIVATE_KEY = 'private_key';
    const PASSPHRASE  = 'passphrase';
    const USE_ARMOR   = 'use_armor';

    protected $publicKey;
    protected $privateKey;
    protected $passphrase = '';
    protected $useArmor   = 0;

    public function __construct(array $params)
    {
        parent::__construct($params);

        $this->publicKey = $params[self::PUBLIC_KEY] ?? null;

        $this->privateKey = $params[self::PRIVATE_KEY] ?? null;

        $this->passphrase = $params[self::PASSPHRASE] ?? null;

        $this->useArmor   = isset($params[self::USE_ARMOR]) ?? 0;

        $this->setupEnvironment();
    }

    protected function setupEnvironment()
    {
        $user = posix_getpwuid(posix_getuid());

        $gnuPgDirectory = $user['dir']  . '/.gnupg';

        putenv('GNUPGHOME=' . $gnuPgDirectory);

        if (file_exists($gnuPgDirectory) === false)
        {
            mkdir($gnuPgDirectory, 0777, true);
        }

        if (is_writable($gnuPgDirectory) === false)
        {
            throw new Exception\RuntimeException(
                'Unable to write gpg config',
                [
                    'path' => $gnuPgDirectory
                ]);
        }

        $gnupgConfig = $gnuPgDirectory . '/gpg.conf';

        if (file_exists($gnupgConfig) === false)
        {
            file_put_contents($gnupgConfig, 'pinentry-mode loopback');
        }
    }

    public function encrypt(string $data) : string
    {
        $res = gnupg_init();

        if ($this->useArmor === 1)
        {
            gnupg_setarmor($res, 1);
        }

        $imp = gnupg_import($res, $this->publicKey);

        gnupg_addencryptkey($res, $imp['fingerprint']);

        $enc = gnupg_encrypt($res, $data);

        if ($enc === false)
        {
            throw new Exception\LogicException(gnupg_geterror($res));
        }

        return $enc;
    }

    public function encryptSign(string $data) : string
    {
        $res = gnupg_init();

        $publicImp = gnupg_import($res, $this->publicKey);

        $privateImp = gnupg_import($res, $this->privateKey);

        gnupg_addencryptkey($res, $publicImp['fingerprint']);

        gnupg_addsignkey($res, $privateImp['fingerprint'], $this->passphrase);

        $enc = gnupg_encryptsign($res, $data);

        if ($enc === false)
        {
            throw new Exception\LogicException(gnupg_geterror($res));
        }

        return $enc;
    }

    public function decrypt(string $data) : string
    {
        $res = gnupg_init();

        $imp = gnupg_import($res, $this->privateKey);

        gnupg_adddecryptkey($res, $imp['fingerprint'], $this->passphrase);

        $dec = gnupg_decrypt($res, $data);

        if ($dec === false)
        {
            throw new Exception\LogicException('PGP Decryption Failed');
        }

        return $dec;
    }

    public function decryptVerify(string $data) : string
    {
        $res = gnupg_init();

        $privateImp = gnupg_import($res, $this->privateKey);

        $publicImp = gnupg_import($res, $this->publicKey);

        gnupg_adddecryptkey($res, $privateImp['fingerprint'], $this->passphrase);

        gnupg_addsignkey($res, $publicImp['fingerprint']);

        $dec = '';
        $success = gnupg_decryptverify($res, $data, $dec);

        if ($success === false)
        {
            throw new Exception\LogicException('PGP Decryption Failed');
        }

        return $dec;
    }

    protected function validateParams(array $params)
    {
        (new Validator)->validateInput('pgp_encryption', $params);
    }
}
