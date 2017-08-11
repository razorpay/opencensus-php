<?php

namespace RZP\Reconciliator\VirtualAccKotak;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    /*******************
     * Row Header Names
     ******************/

    const TXN_DATE                = 'txn_date';
    const TXN_REF_NO              = 'txn_ref_no';
    const E_COLL_AC_NO            = 'e_coll_ac_no';
    const DEALER_NAME             = 'dealer_name';
    const MASTER_AC_NO            = 'master_ac_no';
    const AMOUNT                  = 'amount';
    const BENE_CUST_ACNAME        = 'bene_cust_acname';
    const SEND_CUST_ACNAME        = 'send_cust_acname';
    const SEND_CUST_AC_NO         = 'send_cust_ac_no';
    const REMITT_INFO             = 'remitt_info';
    const SND_BRN_IFSC            = 'snd_brn_ifsc';
    const CUSTOMER_CODE           = 'customer_code';
    const REF2                    = 'ref2';
    const REF3                    = 'ref3';
    const CREDIT_TIME             = 'credit_time';

    const HEADERS = [
        self::TXN_DATE,
        self::TXN_REF_NO,
        self::E_COLL_AC_NO,
        self::DEALER_NAME,
        self::MASTER_AC_NO,
        self::AMOUNT,
        self::BENE_CUST_ACNAME,
        self::SEND_CUST_ACNAME,
        self::SEND_CUST_AC_NO,
        self::REMITT_INFO,
        self::SND_BRN_IFSC,
        self::CUSTOMER_CODE,
        self::REF2,
        self::REF3,
        self::CREDIT_TIME,
    ];

    /**
     * Virtual Account MIS files contain only payment info
     *
     * @param string $fileName
     * @return string
     */
    protected function getTypeName()
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return self::HEADERS;
    }
}
