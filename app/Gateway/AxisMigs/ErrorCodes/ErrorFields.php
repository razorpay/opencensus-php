<?php

namespace RZP\Gateway\AxisMigs\ErrorCodes;

class ErrorFields
{
    const VPC_CSCRESULTCODE   = 'vpc_CSCResultCode';

    const VPC_TXNRESPONSECODE = 'vpc_TxnResponseCode';

    const VPC_AVSRESPONSECODE = 'vpc_AVSResultCode';

    const VPC_ACQRESPONSECODE = 'vpc_AcqResponseCode';

    const VPC_MESSAGE         = 'vpc_Message';

    public static $errorCodeMap = [
        self::VPC_CSCRESULTCODE     => 'cscErrorCodeMap',
        self::VPC_AVSRESPONSECODE   => 'avsErrorCodeMap',
        self::VPC_TXNRESPONSECODE   => 'txnErrorCodeMap',
        self::VPC_MESSAGE           => 'vpcErrorCodeMap',
        self::VPC_ACQRESPONSECODE   => 'errorCodeMap',
    ];

    public static $errorDescriptionMap = [
        self::VPC_CSCRESULTCODE     => 'cscErrorDescriptionMap',
        self::VPC_AVSRESPONSECODE   => 'avsErrorDescriptionMap',
        self::VPC_TXNRESPONSECODE   => 'txnErrorDescriptionMap',
        self::VPC_MESSAGE           => 'vpcErrorDescriptionMap',
        self::VPC_ACQRESPONSECODE   => 'errorDescriptionMap',
    ];

    public static function getErrorCodeFields()
    {
        // This array tells the priority of the error code. Here VPC_MESSAGE is
        // the first priority because it provides most granular data among others
        return [
            self::VPC_MESSAGE,
            self::VPC_ACQRESPONSECODE,
            self::VPC_TXNRESPONSECODE,
            self::VPC_AVSRESPONSECODE,
            self::VPC_CSCRESULTCODE,
        ];
    }
}
