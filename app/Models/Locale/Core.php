<?php


namespace RZP\Models\Locale;

use App;
use RZP\Models\Base;
use RZP\Models\Payment\Config as PaymentConfig;


class Core extends Base\Core
{
    //default language code for merchant
    const DEFAULT_LANGUAGE_CODE           = 'en';

    public static function setLocale($input, $merchantId)
    {
        $languageCode = self::DEFAULT_LANGUAGE_CODE;

        if (isset($input['language_code']) === true)
        {
            $languageCode = $input['language_code'];
        }
        
        else
        {
            $config = (new PaymentConfig\Repository())->fetchDefaultConfigByMerchantIdAndType($merchantId, PaymentConfig\Type::LOCALE);

            if (isset($config) === true)
            {
                $locale = json_decode($config->config);

                $languageCode = $locale->language_code;
            }
        }

        if (((new PaymentConfig\Validator())->validateLanguageCode('validate', $languageCode)) === true)
        {
            App::setLocale($languageCode);
        }
        else
        {
            App::setLocale(self::DEFAULT_LANGUAGE_CODE);
        }

        return App::getLocale();
    }
}
