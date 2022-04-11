<?php

namespace RZP\Models\Card\TokenisedIIN;

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
        $iins = $this->repo->tokenised_iin->findbyIin($iin);

        $response =  $this->getBasicDetails($iins);

        return $response;
    }

    public function fetchbyTokenIin($tokenIin){

        $iin = $this->repo->tokenised_iin->findbyTokenIin($tokenIin);

        $response =  $this->getBasicDetails($iin);

        return $response;
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
}
