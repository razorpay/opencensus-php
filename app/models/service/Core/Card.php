<?php

namespace Models\Service\Core;

use Models\Manager;
use Models\DAL;

class Card
{
    protected $manager = null;

    protected $card = null;

    public function create($input)
    {
        $this->validateAndFillNetworkDetails($input);

        $data = $this->manager->getData();

        $card = new DAL\Card($data);

        $this->card = $card;

        $this->checkNetwork($card, $input['number']);

        return $card;
    }

    public function getCard()
    {
        return $this->card;
    }

    public function createAndReturnWithSensitiveData($input)
    {
        $card = $this->create($input);

        Manager\Card::modifyNumber($input);

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

    public function checkNetwork($card, $number)
    {
        $network = $card->getNetwork();

        if ($network === null)
        {
            $iin = substr($number, 0, 6);

            DAL\UnrecognizedCard::create(['iin' => $iin]);
        }
    }
}