<?php

namespace RZP\Models\Payment\Config;

class Type
{
    const LATE_AUTH              = 'late_auth';
    const CHECKOUT               = 'checkout';
    const LOCALE                 = 'locale';
    const RISK                   = 'risk';
    const DCC                    = 'dcc';
    const CONVENIENCE_FEE        = 'convenience_fee';
    const DCC_RECURRING          = 'dcc_recurring';
    const MCC_MARKDOWN           = 'mcc_markdown';

    protected static $supportedConfigType = [
      self::LATE_AUTH, self::CHECKOUT, self::LOCALE, self::RISK, self::DCC, self::CONVENIENCE_FEE, self::DCC_RECURRING, self::MCC_MARKDOWN
    ];

    public function isConfigTypeSupported($type)
    {
        if (array_search($type, self::$supportedConfigType) === false)
        {
            return false;
        }

        return true;
    }

    protected static $internationalMarkupAndMarkdownConfigs = [
        self::DCC, self::DCC_RECURRING, self::MCC_MARKDOWN
    ];

    public function isInternationalMarkupOrMarkdownConfig($type){
        if (array_search($type, self::$internationalMarkupAndMarkdownConfigs) === false)
        {
            return false;
        }

        return true;
    }
}
