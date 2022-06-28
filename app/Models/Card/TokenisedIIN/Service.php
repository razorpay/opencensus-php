<?php

namespace RZP\Models\Card\TokenisedIIN;

use RZP\Trace\TraceCode;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function createIin($input)
    {
        $tokenisedIin = (new Entity)->build($input);

        $this->repo->saveOrFail($tokenisedIin);

        return $tokenisedIin->toArrayAdmin();
    }

    public function updateIin($iin, $input)
    {
        $iin = $this->repo->tokenised_iin->findByIin($iin);

        $iin->edit($input);

        $this->repo->saveOrFail($iin);

        return $iin->toArrayAdmin();
    }

    public function addOrUpdate($id, $input) : array
    {
        $iin = $this->repo->tokenised_iin->find($id);

        if ($iin === null)
        {
            $input['iin'] = $id;

            return $this->createIin($input);
        }
        else
        {
            return $this->updateIin($id, $input);
        }
    }

    public function fetchIin($iin)
    {
        $iin = $this->repo->tokenised_iin->findbyIin($iin);

        if(isset($iin)){

            $response =  $this->getBasicDetails($iin);

            return $response;

        }

        return null;
    }

    public function fetchbyTokenIin($tokenIin){

        $iin = $this->repo->tokenised_iin->findbyTokenIin($tokenIin);

        if(isset($iin)){

            $response =  $this->getBasicDetails($iin);

            return $response;

        }

        return null;
    }

    protected function getBasicDetails(Entity $iins)
    {
        $response = [
            Entity::IIN             => $iins->getIin(),
            ENTITY::HIGH_RANGE      => $iins->getHighRange(),
            ENTITY::LOW_RANGE       => $iins->getLowRange(),
        ];

        return $response;
    }

    public function addIinBulk($input): array
    {

        $returnData = [];

        foreach ($input['iins'] as $iin) {

            try
            {

                $iincreateResponse = $this->createIin($iin);

                $returnData[$iin['iin']] = $iincreateResponse;

            }
            catch (\Exception $e)
            {
                $returnData[$iin['iin']] = $e->getMessage();

                $this->trace->error(
                    TraceCode::TOKENISED_IIN_BULK_ADDITION_FAILED,
                    [
                        'iin' => $iin,
                        'error' => $e->getMessage(),
                    ]
                );
            }

        }

        return $returnData;
    }


    public function deleteIin($id)
    {
        $iin = $this->repo->tokenised_iin->findById($id);

        $this->trace->info(TraceCode::TOKEN_IIN_DELETE_BULK,
            [
                'iin'    => $iin
            ]
        );

        if(!isset($iin)){
            return null;
        }

        return $this->repo->deleteOrFail($iin);
    }

    public function deleteBulk($input)
    {

        $failedIds = [];
        $deletedIds = [];
        $failedCount = 0;

        for($val = 0; $val < 10 ;$val++){

            $id = $input + $val;
            try
            {

                $this->deleteIin($id);
                $deletedIds[] = ($id);
                $this->trace->info(TraceCode::TOKEN_IIN_DELETE_BULK,
                [
                    'id'    => ($id)
                ]
                );

            }
            catch (\Exception $e)
            {
                $this->trace->error($e,
                    TraceCode::TOKEN_IIN_DELETE_BULK_FAILED,
                    [
                        'id'    => ($id),
                        'error'  => $e->getMessage(),
                    ]
                );

                $failedCount++;

                $failedIds[] = ($id);
            }

        }

        $response = [
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
            'deletedIds' => $deletedIds,
        ];

        return $response;

    }

}
