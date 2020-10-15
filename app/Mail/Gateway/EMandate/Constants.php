<?php

namespace RZP\Mail\Gateway\EMandate;

use RZP\Constants\MailTags;
use RZP\Mail\Base;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\File\Constants as GatewayFileConstants;

class Constants extends Base\Constants
{
    const REGISTER  = 'register';
    const DEBIT     = 'debit';

    const RECIPIENT_EMAILS_MAP = [
        Gateway::NETBANKING_HDFC                  . '_' . self::REGISTER     => ['hdfc.emandate@razorpay.com'],
        Gateway::NETBANKING_HDFC                  . '_' . self::DEBIT        => ['hdfc.emandate@razorpay.com'],
        Gateway::NETBANKING_AXIS                  . '_' . self::DEBIT        => ['axis.emandate@razorpay.com'],
        Gateway::ENACH_RBL                        . '_' . self::DEBIT        => ['rbl.emandate@razorpay.com'],
        Gateway::ENACH_RBL                        . '_' . self::REGISTER     => ['rbl.emandate@razorpay.com'],
        Gateway::NETBANKING_SBI                   . '_' . self::DEBIT        => ['sbi.emandate@razorpay.com'],
        GatewayFileConstants::ENACH_NB_ICICI      . '_' . self::DEBIT        => ['icici-npci-nb.emandate@razorpay.com'],
    ];

    const HEADER_MAP = [
        Gateway::NETBANKING_HDFC                  . '_' . self::REGISTER     => 'HDFC EMandate Register',
        Gateway::NETBANKING_HDFC                  . '_' . self::DEBIT        => 'HDFC EMandate Debit',
        Gateway::NETBANKING_AXIS                  . '_' . self::DEBIT        => 'Axis EMandate Debit',
        Gateway::ENACH_RBL                        . '_' . self::DEBIT        => 'RBL ENach Debit',
        Gateway::ENACH_RBL                        . '_' . self::REGISTER     => 'RBL ENach Register',
        Gateway::NETBANKING_SBI                   . '_' . self::DEBIT        => 'SBI EMandate Debit',
        GatewayFileConstants::ENACH_NB_ICICI      . '_' . self::DEBIT        => 'ICICI Nach Debit',
    ];

    const SUBJECT_MAP = [
        Gateway::NETBANKING_HDFC                  . '_' . self::REGISTER     => 'HDFC EMandate Register File for ',
        Gateway::NETBANKING_HDFC                  . '_' . self::DEBIT        => 'HDFC EMandate Debit File for ',
        Gateway::NETBANKING_AXIS                  . '_' . self::DEBIT        => 'Axis EMandate Debit File for ',
        Gateway::ENACH_RBL                        . '_' . self::DEBIT        => 'RBL ENach Debit File for ',
        Gateway::ENACH_RBL                        . '_' . self::REGISTER     => 'RBL ENach Register File for ',
        Gateway::NETBANKING_SBI                   . '_' . self::DEBIT        => 'SBI EMandate Debit File for ',
        GatewayFileConstants::ENACH_NB_ICICI      . '_' . self::DEBIT        => 'Razorpay Transaction File For Settlement Date ',
    ];

    const MAILTAG_MAP = [
        Gateway::NETBANKING_HDFC                  . '_' . self::REGISTER     => MailTags::HDFC_EMANDATE_REGISTER_MAIL,
        Gateway::NETBANKING_HDFC                  . '_' . self::DEBIT        => MailTags::HDFC_EMANDATE_DEBIT_MAIL,
        Gateway::NETBANKING_AXIS                  . '_' . self::DEBIT        => MailTags::AXIS_EMANDATE_DEBIT_MAIL,
        Gateway::ENACH_RBL                        . '_' . self::DEBIT        => MailTags::RBL_ENACH_DEBIT_MAIL,
        Gateway::ENACH_RBL                        . '_' . self::REGISTER     => MailTags::RBL_ENACH_REGISTER_MAIL,
        Gateway::NETBANKING_SBI                   . '_' . self::DEBIT        => MailTags::SBI_EMANDATE_DEBIT_MAIL,
        GatewayFileConstants::ENACH_NB_ICICI      . '_' . self::DEBIT        => MailTags::ICICI_ENACH_DEBIT_MAIL,
    ];

    const BODY_MAP = [
        Gateway::NETBANKING_HDFC                  . '_' . self::REGISTER => 'PFA EMandate Register request file.',
        Gateway::NETBANKING_HDFC                  . '_' . self::DEBIT    => 'PFA EMandate Debit request file.',
        Gateway::NETBANKING_AXIS                  . '_' . self::DEBIT    => 'PFA EMandate Debit request file.',
        Gateway::ENACH_RBL                        . '_' . self::DEBIT    => 'PFA ENach Debit request file.',
        Gateway::ENACH_RBL                        . '_' . self::REGISTER => 'PFA ENach Register request file.',
        Gateway::NETBANKING_SBI                   . '_' . self::DEBIT    => 'PFA EMandate Debit request file.',
        GatewayFileConstants::ENACH_NB_ICICI      . '_' . self::DEBIT    => 'EMandate Debit request file',
    ];

    const MAIL_TEMPLATE_MAP = [
        Gateway::NETBANKING_HDFC                  . '_' . self::REGISTER     => 'emails.message',
        Gateway::NETBANKING_HDFC                  . '_' . self::DEBIT        => 'emails.message',
        Gateway::NETBANKING_AXIS                  . '_' . self::DEBIT        => 'emails.message',
        Gateway::ENACH_RBL                        . '_' . self::DEBIT        => 'emails.message',
        Gateway::ENACH_RBL                        . '_' . self::REGISTER     => 'emails.message',
        Gateway::NETBANKING_SBI                   . '_' . self::DEBIT        => 'emails.message',
        GatewayFileConstants::ENACH_NB_ICICI      . '_' . self::DEBIT        => 'emails.admin.icici_enach_npci',
    ];
}
