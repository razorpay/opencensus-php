<?php

namespace RZP\Services\Mock;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\ServerErrorException;
use RZP\Services\OffersEngine as BaseOffers;

Class OffersEngine extends BaseOffers
{
    public function avail(string $merchantId, $input)
    {
        print("**********avialing on offers engine***********");

        $this->checkForDefaultValues($input);

        $this->checkBenefitIsNotNull($input);

        if ($input['offer_id'] === 'offer_failed')
        {
            throw new ServerErrorException(PublicErrorDescription::SERVER_ERROR, ErrorCode::SERVER_ERROR);
        }
        return [];
    }

    public function redeem(string $merchantId, $input)
    {
        print("**********redeeming on offers engine***********");
        $this->checkForDefaultValues($input);
        return [];
    }

    public function failPayment(string $merchantId, $input)
    {
        print("**********failing on offers engine***********");
        $this->checkForDefaultValues($input);
        return [];
    }

    public function checkForDefaultValues($input)
    {
        if (isset($input['offer_id']) && isset($input['transaction_id']) && isset($input['channel'])){
            return;
        }

        throw new ServerErrorException(PublicErrorDescription::SERVER_ERROR, ErrorCode::SERVER_ERROR);

    }

    public function validateOffer(string $merchantId, array $input)
    {

    }
    public function checkBenefitIsNotNull($input)
    {
    }


}
?>
