<?php

namespace RZP\Models\Payout;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Exception\BadRequestException;

class Mode
{
    const RTGS  = 'RTGS';
    const IMPS  = 'IMPS';
    const NEFT  = 'NEFT';
    const IFT   = 'IFT';
    const UPI   = 'UPI';

    protected static $allSupportedModes = [
        self::RTGS,
        self::IMPS,
        self::NEFT,
        self::IFT,
        self::UPI,
    ];

    public static function validateMode(string $mode)
    {
        if (self::isValid($mode) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_INVALID_MODE,
                null,
                [
                    'mode' => $mode,
                ]);
        }
    }

    protected static function getAllSupportedPayoutChannelsWithModes()
    {
        return [
            Settlement\Channel::YESBANK   => [
                //Constants\Entity::VPA           =>  [
                //    self::UPI,
                //],
                Constants\Entity::BANK_ACCOUNT  =>  [
                    //self::RTGS,
                    self::IMPS,
                    self::NEFT,
                    //self::IFT,
                ],
                Constants\Entity::CARD          =>  [
                    self::IMPS,
                    //self::UPI,
                    //self::NEFT,
                ]
            ],
            Settlement\Channel::CITI      => [
                Constants\Entity::BANK_ACCOUNT  =>  [
                    self::RTGS,
                    self::IMPS,
                    self::NEFT,
                    self::IFT,
                ],
                Constants\Entity::CARD          =>  [
                    self::IMPS,
                    self::NEFT,
                ]
            ],
            Settlement\Channel::ICICI     => [
                Constants\Entity::BANK_ACCOUNT  =>  [
                    self::IMPS,
                    self::NEFT,
                ],
                Constants\Entity::CARD          =>  [
                    self::IMPS,
                ]
            ],
            Settlement\Channel::RBL       => [
                Constants\Entity::BANK_ACCOUNT  =>  [
                    self::RTGS,
                    self::IMPS,
                    self::NEFT,
                    self::IFT,
                ],
            ]
        ];
    }

    protected static function isValid(string $mode): bool
    {
        return (in_array($mode, self::$allSupportedModes, true) === true);
    }

    public static function validateChannelAndModeForPayouts(string $channel = null,
                                                            string $destinationType = null,
                                                            string $mode = null) : bool
    {
        Settlement\Channel::validate($channel);

        $allChannelsWithModes = self::getAllSupportedPayoutChannelsWithModes();

        $modesSupportedForChannel = $allChannelsWithModes[$channel][$destinationType] ?? [];

        return (in_array($mode, $modesSupportedForChannel, true));
    }
}
