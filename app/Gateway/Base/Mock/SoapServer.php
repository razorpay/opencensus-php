<?php

namespace RZP\Gateway\Base\Mock;

use Exception;
use SoapServer as BaseSoapServer;

class SoapServer extends BaseSoapServer
{
    public function handle($soapRequest = null)
    {
        // Adding try-catch as a hack as handle
        // function prints response instead of
        // returning it. We are catching the header
        // sent exception and ignoring it
        try
        {
            ob_start();
            parent::handle($soapRequest);
        }
        catch (Exception $ex)
        {
            if (strstr($ex->getMessage(), 'headers already sent') === false)
            {
                ob_end_flush();
                throw $ex;
            }
        }

        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
