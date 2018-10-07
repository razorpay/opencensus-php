<?php

namespace RZP\Models\Card\IIN;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Feature\Constants as Feature;

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

    public function disableIinFlow($id, $flow)
    {
        $iin = $this->repo->iin->findOrFail($id);

        $iin->disableFlow($flow);

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

    /**
     * Validates if a given IIN is issued by the issuer.
     *
     * @param array $input
     *
     * @return array
     */
    public function validateIinIssuer(array $input): array
    {
        (new Validator)->validateInput('binIssuerValidation', $input);

        $issuer = $input[Entity::ISSUER];
        $cardNumber = $input[Entity::NUMBER];

        $enabledBinIssuerValidator = $this->merchant->isFeatureEnabled(Feature::BIN_ISSUER_VALIDATOR);

        $response = ['result' => false];

        // We will be returning false when the feature flag is not added.
        // Could have thrown error at feature middleware but that is not expected functionality by frontend.
        if ($enabledBinIssuerValidator === false)
        {
            return $response;
        }
        else
        {
            $issuer = strtoupper($issuer);

            // Just having the flexibility for frontend to send a request while typing the card number.
            if (strlen($cardNumber) > 6)
            {
                $iinNumber = intval(substr($cardNumber, 0, 6));
            }
            else
            {
                $iinNumber = intval($cardNumber);
            }

            $iin = $this->repo->iin->findByIinAndIssuer($iinNumber, $issuer);

            if (empty($iin) === false)
            {
                $response['result'] = true;
            }
            else
            {
                // This log helps us track any bin validations
                //which we are unable to serve because of our iin database errors.
                $this->trace->info(
                    TraceCode::BIN_ISSUER_VALIDATION_FAILED,
                    [
                        'issuer' => $issuer,
                        'iin'    => $iinNumber
                    ]);
            }
        }

        return $response;
    }
}
