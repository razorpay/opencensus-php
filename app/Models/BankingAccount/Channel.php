<?php

namespace RZP\Models\BankingAccount;

use App;

use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Models\Settlement\Channel as FTAChannel;
use RZP\Exception\BadRequestValidationFailureException;

class Channel
{
    const YESBANK = FTAChannel::YESBANK;
    const RBL     = FTAChannel::RBL;
    const ICICI   = FTAChannel::ICICI;
    const KOTAK   = FTAChannel::KOTAK;

    public static $directTypeChannels = [
        self::RBL,
        self::ICICI,
    ];

    protected static $channels = [
        self::YESBANK,
        self::RBL,
        self::ICICI,
    ];

    protected static $defaultSharedTypeChannels = [
        self::ICICI,
        self::KOTAK,
        self::YESBANK,
    ];

    public static function isValid(string $channel = null): bool
    {
        $key = __CLASS__ . '::' . strtoupper($channel);

        return ((defined($key) === true) and (constant($key) === $channel));
    }

    public static function isValidDirectTypeChannel(string $channel = null): bool
    {
        self::validateChannel($channel);

        return (in_array($channel, self::$directTypeChannels, true) === true);
    }

    public static function validateChannel(string $channel = null)
    {
        if (self::isValid($channel) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid channel: ' . $channel,
                Entity::CHANNEL,
                [Entity::CHANNEL => $channel]);
        }
    }

    public static function getAll(): array
    {
        return self::$channels;
    }

    public static function isValidSharedChannel(string $channel): bool
    {
        $allowedChannels = self::getAllowedSharedChannels();

        return (in_array($channel, $allowedChannels) === true);
    }

    public static function getAllowedSharedChannels(): array
    {
        $allowedChannels = (new Admin\Service)->getConfigKey(
            ['key' => Admin\ConfigKey::RX_SHARED_ACCOUNT_ALLOWED_CHANNELS]);

        if (empty($allowedChannels) === true)
        {
            $allowedChannels = self::$defaultSharedTypeChannels;
        }

        // Todo: Need to change terminals
        $app = \App::getFacadeRoot();

        if ($app['rzp.mode'] === Mode::TEST)
        {
            $allowedChannels = array_merge($allowedChannels, [self::YESBANK]);
        }

        return $allowedChannels;
    }
}
