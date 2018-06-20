<?php

namespace RZP\Models\Card\IIN;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;

class Service extends Base\Service
{
    public function fetchIin($iinId)
    {
        $iin = $this->repo->iin->findOrFail($iinId);

        return $iin->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $iins = $this->repo->iin->fetch($input);

        return $iins->toArrayPublic();
    }

    public function fetchPaymentFlows(array $input)
    {
        (new Validator)->validateInput('fetch_payment_flows', $input);

        $iinEntity = $this->repo->iin->find($input['iin']);

        $data = [];

        if (empty($iinEntity) === true)
        {
            return $data;
        }

        $merchant = $this->merchant;

        if ($merchant->isFeatureEnabled(Feature\Constants::ATM_PIN_AUTH) === true)
        {
            $data[Constants::PIN] = $iinEntity->isDebitPin();
        }

        if ($merchant->isFeatureEnabled(Feature\Constants::OTPELF) === true)
        {
            $data[Constants::OTP] = (($iinEntity->isHeadLessOtp()) or
                                     ($iinEntity->isOtp()));
        }

        return $data;
    }

    public function addIin($input)
    {
        $iin = (new Entity)->build($input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayPublic();
    }

    public function editIin($id, $input)
    {
        $iin = $this->repo->iin->findOrFail($id);

        $iin->edit($input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayPublic();
    }

    public function addIinRange($input)
    {
        $result = (new Import\RangeImporter)->import($input);

        return $result;
    }

    public function importIin($input)
    {
        $result = (new Import\XLSImporter)->import($input);

        return $result;
    }

    public function importCsvIin($job, $input)
    {
        $result = (new Import\XLSImporter)->importWithoutNetwork($input);

        return $result;
    }

    public function generateIinFile($input)
    {
        $result = (new Import\IinGenerator)->generate($input);

        return $result;
    }
}
