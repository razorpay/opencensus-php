<?php

namespace RZP\Traits;

use App;

use RZP\Constants\Mode;
use RZP\Models\Merchant\RazorxTreatment;

trait TrimSpace
{
    protected static $trimSpacesRazorxRetryCount = 2;

    public function trimSpacesIfMerchantEnabled($data, $merchantId)
    {
        $this->app = App::getFacadeRoot();

        $treatment = $this->app->razorx->getTreatment(
            $merchantId,
            RazorxTreatment::BLOCKED_MERCHANT_FOR_TRIM_SPACE,
            Mode::LIVE,
            self::$trimSpacesRazorxRetryCount
        );

        if ($treatment !== 'on')
        {
            return $this->trimSpaces($data);
        }

        return $data;
    }

    public function trimSpaces($data)
    {
        if (is_array($data) === true)
        {
            $trimmedArray = [];

            foreach ($data as $key => $value)
            {
                $key = $this->trimSpaces($key);

                $value = $this->trimSpaces($value);

                $trimmedArray[$key] = $value;
            }

            return $trimmedArray;
        }
        else if (is_string($data) === true)
        {
            return trim($data);
        }

        return $data;
    }
}
