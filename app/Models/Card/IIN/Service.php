<?php

namespace RZP\Models\Card\IIN;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Feature\Constants as Feature;

class Service extends Base\Service
{
    public function addIin($input)
    {
        $iin = (new Entity)->build($input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
    }

    public function editIin($id, $input)
    {
        $iin = $this->repo->iin->findOrFail($id);

        $this->formatEditInput($iin, $input);

        $iin->edit($input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
    }

    public function editIinBulk($input)
    {
        $this->trace->info(TraceCode::IIN_UPDATE_BULK, [
            'input' => $input,
        ]);

        (new Validator())->validateInput('edit_bulk', $input);

        $returnData = [];

        $editPayload = $input['payload'];

        $iins = $input['iins'];

        foreach ($iins as $iin)
        {
            try
            {
                $iinEditResponse = $this->editIin($iin, $editPayload);

                $returnData[$iin] = $iinEditResponse;
            }
            catch (\Exception $e)
            {
                $returnData[$iin] = $e->getMessage();

                $this->trace->error(
                    TraceCode::IIN_UPDATE_FAILED,
                    [
                        'iin'    => $iin,
                        'error'  => $e->getMessage(),
                    ]
                );
            }
        }

        return $returnData;
    }

    public function disableIinFlow($id, $flow)
    {
        $iin = $this->repo->iin->findOrFail($id);

        $iin->disableFlow($flow);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
    }

    public function enableIinFlow($id, $flow)
    {
        $iin = $this->repo->iin->findOrFail($id);

        $iin->enableFlow($flow);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
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
        (new Validator)->validateInput('bin_issuer_validation', $input);

        $cardNumber = $input[Entity::NUMBER];

        $response = ['result' => false];

        $iinNumber = intval(substr($cardNumber, 0, 6));

        $iin = $this->repo->iin->find($iinNumber);

        if (empty($iin) === false)
        {
            $response['result'] = true;

            $response['issuer'] = ($iin->getIssuer() === 'HDFC') ? 'HDFC' : 'Others';

            $response['type'] = $iin->getType();
        }
        else
        {
            // This log helps us track any bin validations
            // which we are unable to serve because of our iin database errors.
            $this->trace->info(
                TraceCode::BIN_ISSUER_VALIDATION_FAILED,
                [
                    'iin'    => $iinNumber
                ]);
        }

        return $response;
    }

    public function getIinsList(array $input) : array
    {
        (new Validator)->validateInput('bin_list_validation', $input);

        $iins = $this->repo->iin->findOtpEnabledIins();

        $response['count'] = count($iins);

        $response['iins'] = $iins;

        return $response;
    }

    public function addOrUpdate($id, $input) : array
    {
        $iin = $this->repo->iin->find($id);

        if ($iin === null)
        {
            $input['iin'] = $id;

            return $this->addIin($input);
        }
        else
        {
            return $this->editIin($id, $input);
        }
    }

    public function processRecord(string $type, array $input)
    {
        // hardcoding for now
        $this->processor = new Batch\NpciRupay;

        $this->processor->preprocess($input);

        return $this->processor->process();
    }

    protected function formatEditInput(Entity $iin, array & $input)
    {
        if (isset($input[Entity::FLOWS]) === false)
        {
            return;
        }

        $existingFlows = [];

        foreach (Flow::getEnabledFlows($iin->getFlows()) as $flow)
        {
            $existingFlows[$flow] = '1';
        }

        $mergedValues = array_merge($existingFlows, $input[Entity::FLOWS]);

        $input[Entity::FLOWS] = $mergedValues;
    }
}
