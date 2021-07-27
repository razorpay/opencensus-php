<?php


namespace RZP\Models\Merchant\Detail\Report;

use RZP\Models\Merchant\Entity as MEntity;

class Constants
{
    const MODE              = 'mode';
    const TEMPLATE          = 'template';
    const DATA_PROCESSOR    = 'data_processor';
    const REPORT            = 'report';
    const SUBJECT           = 'subject';

    /*
     * Processor modes
     */
    const EMAIL = 'email';

    /*
     * Processor Types
     */
    const ACTIVATION_ACKNOWLEDGEMENT = "activation_acknowledgement";

    const REPORT_TYPES = [
        MEntity::AXIS_ORG_ID    => [
            self::ACTIVATION_ACKNOWLEDGEMENT
        ]
    ];

    const REPORT_CONFIG = [
        self::ACTIVATION_ACKNOWLEDGEMENT    => [
            self::MODE              => self::EMAIL,
            self::TEMPLATE          => 'emails.merchant.report.axis_activation_acknowledgement',
            self::SUBJECT           => 'Axis Activation Acknowledgement Report',
            self::DATA_PROCESSOR    => AxisActivationAcknowledgement::class
        ]
    ];

    const ADMIN_EMAIL_LIST = [
        'suhas.ghule@razorpay.com'
    ];
}
