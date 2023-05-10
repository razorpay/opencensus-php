<?php

namespace RZP\Models\Settlement;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Mode;

class Channel
{
    const KOTAK     = 'kotak';
    const ATOM      = 'atom';
    const ICICI     = 'icici';
    const AXIS      = 'axis';
    const YESBANK   = 'yesbank';
    const HDFC      = 'hdfc';
    const RBL       = 'rbl';
    const AXIS2     = 'axis2';
    const AXIS3     = 'axis3';
    const ICICI2    = 'icici2';
    const CITI      = 'citi';
    const M2P       = 'm2p';
    const AMAZONPAY = 'amz_pay';
    const AMAZONPAY_FTS = 'amazon_pay'; // FTS has "amazon_pay" as value where as FTA has "amz_pay" due to DB limitations
    const ICICIEXP = "iciciexp"; // settlement has "icici_opgsp_export" as value where api db has "iciciexp" due to DB limitations
    const ICICI_OPGSP_EXPORT = "icici_opgsp_export";

    // Exclusively for Internal VA to VA transfers in razorpayX using creditTransfer entity
    const RZPX = 'rzpx';

    // This channel signifies transfer initiated through masterCard send
    const MCS  = 'mcs';

    // This channel is for malaysain merchant transaction
    const RHB = 'rhb';

    public static $gateways = [
        self::KOTAK => [
            Payment\Gateway::AMEX,
            Payment\Gateway::BILLDESK,
            Payment\Gateway::HDFC,
            Payment\Gateway::MOBIKWIK,
            Payment\Gateway::PAYTM,
            Payment\Gateway::NETBANKING_HDFC,
            Payment\Gateway::WALLET_PAYZAPP,
            Payment\Gateway::WALLET_PAYUMONEY,
            Payment\Gateway::WALLET_OLAMONEY,
            Payment\Gateway::WALLET_FREECHARGE,
            Payment\Gateway::WALLET_JIOMONEY,
            Payment\Gateway::WALLET_OPENWALLET,
            Payment\Gateway::WALLET_RAZORPAYWALLET,
        ],
        self::ATOM  => [
            Payment\Gateway::ATOM
        ],
        self::ICICI => [
            Payment\Gateway::FIRST_DATA,
            Payment\Gateway::NETBANKING_ICICI,
            Payment\Gateway::UPI_ICICI,
        ],
        self::AXIS  => [
            Payment\Gateway::AXIS_MIGS,
            Payment\Gateway::AXIS_GENIUS,
        ],
    ];

    public static $channelToNodalGatewayMap = [
        self::YESBANK => Payment\Gateway::NODAL_YESBANK,
        self::ICICI   => Payment\Gateway::NODAL_ICICI,
        self::M2P     => Payment\Gateway::M2P
    ];

    public static function getChannels()
    {
        return [
            self::KOTAK,
            self::YESBANK,
            self::AXIS,
            self::ICICI,
            self::HDFC,
            self::RBL,
            self::AXIS2,
            self::ICICI2,
            self::CITI,
            self::M2P,
            self::AMAZONPAY,
            self::ICICI_OPGSP_EXPORT,
            self::AXIS3,
            self::RZPX,
            self::MCS,
            self::RHB,
        ];
    }

    /**
     * Gives list of channels for which recon is mocked
     *
     * @return array
     */
    public static function getChannelsWithReconMock()
    {
        return [
            self::KOTAK,
            self::AXIS,
            self::ICICI,
            self::HDFC,
        ];
    }

    /**
     * Gives list of channels which support API based settlement and recon
     *
     * @return array
     */
    public static function getApiBasedChannels()
    {
        return [
            self::RBL,
            self::YESBANK,
            self::ICICI2,
        ];
    }

    /**
     * Gives list of channels which support API based beneficiary registration
     *
     * @return array
     */
    public static function getChannelsWithOnlineBeneficiaryRegistration()
    {
        return [
            self::YESBANK
        ];
    }

    /**
     * Gives list of channels which support API based beneficiary verification
     *
     * @return array
     */
    public static function getChannelsWithOnlineBeneficiaryVerification()
    {
        return [
            self::YESBANK
        ];
    }

    /**
     * Gives list of channels which support file based settlement and recon
     *
     * @return array
     */
    public static function getFileBasedChannels()
    {
        return [
            self::KOTAK,
            self::AXIS,
            self::ICICI,
            self::HDFC,
            self::AXIS2,
        ];
    }

    /**
     * Gives list of channels which support 24x7 settlements
     *
     * @return array
     */
    public static function get24x7Channels(): array
    {
        return [
            self::YESBANK,
            self::ICICI2,
        ];
    }

    /**
     * Channels for which balance API is available
     *
     * @return array
     */
    public static function getChannelsWithFetchBalance(): array
    {
        return [
            self::KOTAK,
        ];
    }

    /**
     * Gives list of channels which support instant payouts
     *
     * @return array
     */
    public static function getInstantPayoutChannels()
    {
        return [
            self::YESBANK,
            self::ICICI2,
        ];
    }

    /**
     * Givens list of channels which has healthCheck implemented
     *
     * @return array
     */
    public static function getChannelsWithHealthCheck(): array
    {
        return [
            self::YESBANK,
        ];
    }

    public static function getGateways($channel)
    {
        return self::$gateways[$channel];
    }

    public static function getNodalGatewayFromChannel(string $channel): string
    {
        return self::$channelToNodalGatewayMap[$channel];
    }

    public static function getChannelFromGateway(string $gateway): string
    {
        foreach (self::$gateways as $channel => $gatewayList)
        {
            if (in_array($gateway, $gatewayList, true) === true)
            {
                return $channel;
            }
        }

        throw new Exception\LogicException('Channel not found for gateway ' . $gateway);
    }

    public static function exists($channel)
    {
        return defined(get_class() . '::' . strtoupper($channel));
    }

    public static function validate(string $channel = null)
    {
        if (in_array($channel, self::getChannels(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid channel name: ' . $channel);
        }
    }

    /**
     * Supported FTA (API based) channels for razorpayX payouts
     * @return array
     */
    public static function getFTASupportedPayoutChannels()
    {
        return [
            self::YESBANK,
        ];
    }

    public static function getNonTransactionChannels()
    {
        return [
            self::RBL,
            self::ICICI,
            self::AXIS,
            self::YESBANK,
        ];
    }

    public static function getPreferredModeSupportedChannels()
    {
        return [
            self::RBL,
            self::YESBANK,
            self::ICICI,
            self::CITI,
        ];
    }

    /**
     * Used for retrying the stuck transfers on FTS supported channels
     * @return array
     */
    public static function getFtsSupportedChannels()
    {
        return [
            self::RBL,
            self::ICICI,
            self::CITI,
            self::YESBANK,
            self::AXIS,
            self::AMAZONPAY_FTS,
            self::M2P,
            self::MCS
        ];
    }

    public static function validateChannelAndMode(string $channel = null,
                                                  string $destinationType = null,
                                                  string $mode = null) : bool
    {
        self::validate($channel);

        $allChannelsWithModes = self::getAllSupportedChannelsWithModes();

        $modesSupportedForChannel = $allChannelsWithModes[$channel][$destinationType] ?? [];

        return (in_array($mode, $modesSupportedForChannel, true));
    }

    protected static function getAllSupportedChannelsWithModes()
    {
        return [
            self::YESBANK   => [
                Constants\Entity::VPA           =>  [
                    Mode::UPI,
                ],
                Constants\Entity::BANK_ACCOUNT  =>  [
                    Mode::RTGS,
                    Mode::IMPS,
                    Mode::NEFT,
                    Mode::IFT,
                ],
                Constants\Entity::CARD          =>  [
                    Mode::IMPS,
                    Mode::UPI,
                    Mode::NEFT,
                ],
                Constants\Entity::WALLET_ACCOUNT  =>  [
                    Mode::FTS_WALLET_TRANSFERS_MODE,
                ]
            ],
            self::CITI      => [
                Constants\Entity::BANK_ACCOUNT  =>  [
                    Mode::RTGS,
                    Mode::IMPS,
                    Mode::NEFT,
                    Mode::IFT,
                ],
                Constants\Entity::CARD          =>  [
                    Mode::IMPS,
                    Mode::NEFT,
                ],
                Constants\Entity::WALLET_ACCOUNT  =>  [
                    Mode::FTS_WALLET_TRANSFERS_MODE,
                ]
            ],
            self::ICICI     => [
                Constants\Entity::VPA           =>  [
                    Mode::UPI,
                ],
                Constants\Entity::BANK_ACCOUNT  =>  [
                    Mode::IMPS,
                    Mode::NEFT,
                    Mode::RTGS,
                    Mode::IFT,
                ],
                Constants\Entity::CARD          =>  [
                    Mode::IMPS,
                    Mode::UPI,
                    Mode::NEFT,
                ],
                Constants\Entity::WALLET_ACCOUNT  =>  [
                    Mode::FTS_WALLET_TRANSFERS_MODE,
                ]
            ],
            self::RBL       => [
                Constants\Entity::VPA           =>  [
                    Mode::UPI,
                ],
                Constants\Entity::BANK_ACCOUNT  =>  [
                    Mode::RTGS,
                    Mode::IMPS,
                    Mode::NEFT,
                    Mode::IFT,
                ],
                Constants\Entity::CARD          =>  [
                    Mode::IMPS,
                    Mode::NEFT,
                    Mode::UPI,
                ],
                Constants\Entity::WALLET_ACCOUNT  =>  [
                    Mode::FTS_WALLET_TRANSFERS_MODE,
                ]
            ],
            self::M2P       => [
                Constants\Entity::CARD          =>  [
                    Mode::CT,
                ]
            ],
            self::MCS       => [
                Constants\Entity::CARD          =>  [
                    Mode::CT,
                ]
            ],
            self::RZPX       => [
                Constants\Entity::BANK_ACCOUNT  =>  [
                    Mode::IFT,
                ],
            ],
            self::AXIS       => [
                Constants\Entity::VPA           =>  [
                    Mode::UPI,
                ],
                Constants\Entity::BANK_ACCOUNT => [
                    Mode::NEFT,
                    Mode::RTGS,
                    Mode::IMPS,
                    Mode::IFT,
                ],
                Constants\Entity::CARD          =>  [
                    Mode::IMPS,
                    Mode::UPI,
                    Mode::NEFT,
                ],
            ],
        ];
    }
}
