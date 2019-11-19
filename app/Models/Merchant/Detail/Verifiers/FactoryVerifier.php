<?php

namespace RZP\Models\Merchant\Detail\Verifiers;

use App;

use RZP\Models\Merchant\Detail\Constants;

class FactoryVerifier
{
    public static function getPoiVerifier(array $input)
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['applications.mozart.mock'];

        if ($mock === true)
        {
            $panVerifiedMock = new PanVerifierMock($input);

            // this config is not defined in application config , this is used in test case only
            $mockStatus = $app['config']['applications.mozart.pan_authentication'] ?? Constants::SUCCESS;

            $panVerifiedMock->setMockStatus($mockStatus);

            return $panVerifiedMock;
        }

        return new PanVerifier($input);
    }

    public static function getPoaVerifier(array $input)
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['applications.mozart.mock'];

        if ($mock === true)
        {
            $poaVerifiedMock = new PoaVerifierMock($input);

            // this config is not defined in application config , this is used in test case only
            $mockStatus = $app['config']['applications.mozart.poa_ocr_response_type'] ?? Constants::AADHAR_FRONT;

            $poaVerifiedMock->setMockStatus($mockStatus);

            return $poaVerifiedMock;
        }

        return new PoaVerifier($input);
    }
}
