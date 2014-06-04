<?php

namespace Models\Service\Core;

use Models\Manager;
use Models\DAL;

class Card
{
    protected $manager = null;

    public function create($input)
    {
        $this->validateAndFillNetworkDetails($input);

        $data = $this->manager->getData();

        $card = DAL\Card::createOrFail($data);

        return $card;
    }

    public function createAndReturnWithSensitiveData($input)
    {
        $card = $this->create($input);

        return array_merge(
            $card->toArray(),
            ['number' => $input['number'],
             'cvv' => $input['cvv']]);
    }

    public function validateAndFillNetworkDetails($input)
    {
        $this->manager = Manager\Card::createValidate($input);

        $details = DAL\CardDetail::retrieveDetails($this->manager->getField('number'));

        $this->manager->fillNetworkDetails($details);
    }
}