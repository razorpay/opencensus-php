<?php

namespace RZP\Models\P2p\Mandate;

/**
 * Class Mode
 *
 * @package RZP\Models\P2p\Mandate
 */
class Mode
{
    const DEFAULT           = 'default';
    const QR_CODE           = 'qr_code';
    const SECURE_QR_CODE    = 'secure_qr_code';
    const INTENT            = 'intent';

    /**
     * Set of modes allowed
     *
     * @var string[]
     */
    public static $allowed = [
        self::DEFAULT,
    ];

    /**
     * Check if provided mode is valid
     *
     * @param string $key
     *
     * @return bool
     */
    public static function isValid(string $key): bool
    {
        return (defined(static::class.'::'.strtoupper($key)) === true);
    }
}
