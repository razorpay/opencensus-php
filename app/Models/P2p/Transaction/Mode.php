<?php

namespace RZP\Models\P2p\Transaction;

class Mode
{
    const DEFAULT           = 'default';
    const QR_CODE           = 'qr_code';
    const SECURE_QR_CODE    = 'secure_qr_code';
    const INTENT            = 'intent';
    const SECURE_INTENT     = 'secure_intent';
    const NFC               = 'nfc';
    const BLE               = 'ble';
    const UHF               = 'uhf';

    public static $allowed = [
        self::DEFAULT,
        self::QR_CODE,
        self::SECURE_QR_CODE,
        self::INTENT,
        self::SECURE_INTENT,
        self::NFC,
        self::BLE,
        self::UHF,
    ];

    public static function isValid(string $key): bool
    {
        return (defined(static::class.'::'.strtoupper($key)));
    }
}
