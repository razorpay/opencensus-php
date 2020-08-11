<?php

namespace RZP\Models\Gateway\Terminal\GatewayProcessor\Worldline;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Models\Mpan;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Constants\Environment;
use RZP\Models\Gateway\Terminal\Constants; 
use RZP\Models\Mpan\Repository as MpanRepo;


class Validator extends Base\Validator
{
    protected static $gatewayInputRules = [
        Constants::MPAN                           => 'bail|required|array',
        Constants::MPAN.'.'.Constants::MASTERCARD => 'required|string|size:16',
        Constants::MPAN.'.'.Constants::VISA       => 'required|string|size:16',
        Constants::MPAN.'.'.Constants::RUPAY      => 'required|string|size:16',
    ];

    protected static $gatewayInputValidators = [
        'mpansOwner'
    ];

    protected function validateMpansOwner($input)
    {
        $app = App::getFacadeRoot();

        if ( ($app['rzp.mode'] === Mode::LIVE) or ($app['env'] === Environment::TESTING))
        {
            $partnerMerchantId = $app['basicauth']->getPartnerMerchantId();

            $mcMpan     = $app['mpan.cardVault']->tokenize(['secret' => $input[Constants::MPAN][Constants::MASTERCARD]]);
            $visaMpan   = $app['mpan.cardVault']->tokenize(['secret' => $input[Constants::MPAN][Constants::VISA]]);
            $rupayMpan  = $app['mpan.cardVault']->tokenize(['secret' => $input[Constants::MPAN][Constants::RUPAY]]);

            $issuedMpans = (new MpanRepo())->findByMerchantIdMpans($partnerMerchantId, [$mcMpan, $visaMpan, $rupayMpan]);

            if ($issuedMpans->count() != 3)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_MPAN);
            }

            $issuedMcMpan = $issuedMpans->firstWhere(Mpan\Entity::NETWORK, Mpan\Constants::MASTERCARD);
            $issuedVisaMpan = $issuedMpans->firstWhere(Mpan\Entity::NETWORK, Mpan\Constants::VISA);
            $issuedRupayMpan = $issuedMpans->firstWhere(Mpan\Entity::NETWORK, Mpan\Constants::RUPAY);

            // If any of the issuedMpan is null, it means wrong network is used for atlead one of the issued mpan
            if (($issuedMcMpan === null) or ( $issuedVisaMpan === null) or ($issuedRupayMpan === null) or 
                ($issuedMcMpan->getMpan() !== $mcMpan) or 
                ($issuedVisaMpan->getMpan()  !== $visaMpan) or 
                ($issuedRupayMpan->getMpan() !== $rupayMpan))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_MPAN_FOR_NETWORK);
            }
        }
    }
}