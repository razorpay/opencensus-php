<?php

namespace RZP\Gateway\Billdesk\Mock;

use \WpOrg\Requests\Response;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Billdesk;

class Gateway extends Billdesk\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    protected function getContentAfterChecksumVerification($responseBody, $action = null)
    {
        /**
         *  Check if Bank is Andhra Bank, if yes make the response invalid
         */
        $fieldData = explode('|', $responseBody);

        if ($fieldData[7] === 'UBI')
        {
            $responseBody = $this->getInvalidVerifyData();
        }

        return parent::getContentAfterChecksumVerification($responseBody, $action);
    }

    protected function getInvalidVerifyData()
    {
        // @codingStandardsIgnoreLine
        return '<HTML><HEAD><TITLE>Error</TITLE></HEAD><BODY>An error occurred while processing your request.<p>Reference&#32;&#35;97&#46;44367c68&#46;1482720567&#46;ec59760</BODY></HTML>';
    }
}
