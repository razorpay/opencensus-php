<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Error\ErrorCode;
use RZP\Models\P2p\BankAccount;
use RZP\Exception\LogicException;
use RZP\Exception\RuntimeException;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;

/**
 * * @property Core $core
 */
class Core extends Base\Core
{
    private $pspxMandate;

    public function __construct()
    {
        parent::__construct();

        $this->pspxMandate = $this->app['pspx_mandate'];
    }

    /**
     * @param $mandateInput
     * @param $upiInput
     *
     * @return Entity
     * @throws RuntimeException
     */
    public function create($mandateInput, $upiInput): Entity
    {
        $this->build($mandateInput);

        (new UpiMandate\Core)->build($upiInput);

        $pspxInput = [
            Entity::MANDATE => $mandateInput,
            Entity::UPI     => $upiInput,
        ];

        $mandateArray = $this->pspxMandate->create($this->context(), $pspxInput);

        $mandate = new Entity($mandateArray);

        $this->fillProperties($mandate, $mandateArray);

        return $mandate;
    }

    /**
     * @param Entity $mandate
     * @param array  $input
     * This is the method to update mandate entity
     *
     * @return Base\Entity
     * @throws RuntimeException
     */
    public function update(Entity $mandate, array $input): Base\Entity
    {
        // update the mandate in the system
        $updatedMandateData = $this->pspxMandate->update($this->context(), $input);

        // if id is not found in the system
        if (sizeof($updatedMandateData) === 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID, null,
                $input);
        }

        // construct the mandate object and return it
        $updatedMandate = null;

        try
        {
            $updatedMandate = new Entity($updatedMandateData);

            $this->fillProperties($updatedMandate, $updatedMandateData);
        }
        catch (\Exception $e)
        {
            throw new RuntimeException('Unable to update mandate ' . $updatedMandateData);
        }

        return $updatedMandate;
    }

    /**
     * @param string $id
     * @param false  $withTrashed
     * This is the method to fetch mandates from the system
     *
     * @return Entity
     * @throws RuntimeException
     */
    public function fetch(string $id, $withTrashed = false): Base\Entity
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        // construct a payload id to fetch mandate for
        $content = [Entity::ID => $id];

        // fetch mandate by id
        $mandateData = $this->pspxMandate->fetch($this->context(), $content);

        if (sizeof($mandateData) === 0)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID, null, $id);
        }

        // type cast to entity object
        try
        {
            $mandate = new Entity($mandateData);

            $this->fillProperties($mandate, $mandateData);
        }
        catch (\Exception $e)
        {
            throw new RuntimeException('Invalid mandate data ' . $mandate);
        }

        return $mandate;
    }

    /**
     * @param array $input
     * This is the method to fetch all the mandates data that are stored
     *
     * @return PublicCollection
     * @throws LogicException
     */
    public function fetchAll(array $input): PublicCollection
    {
        $mandateList = $this->pspxMandate->fetchAll($this->context(), $input);

        // initialize public collection and add mandate data
        $collection = new PublicCollection();

        $output[PublicCollection::ENTITY] = 'collection';
        $output[PublicCollection::COUNT]  = 0;
        $output[PublicCollection::ITEMS]  = [];

        // if no mandate exists in the system , then return empty collection
        if ($mandateList == null or count($mandateList) <= 0)
        {
            return $collection;
        }

        // populate mandate data into collection object
        foreach ($mandateList as $mandateData)
        {
            try
            {
                $mandate = new Entity($mandateData);

                $this->fillProperties($mandate, $mandateData);

                $collection->push($mandate);
            }
            catch (\Exception $e)
            {
                throw new RuntimeException('Invalid mandate data ' . $mandateData);
            }
        }

        $output[PublicCollection::COUNT] = $collection->count();
        $output[PublicCollection::ITEMS] = $collection->toArray();

        return $collection;
    }

    /**
     * Set the non fillable properties to mandate entity from pspx response array
     *
     * @param Entity $mandate
     * @param array $mandateArray
     */
    private function fillProperties(Entity $mandate, array $mandateArray)
    {
        $mandate->setId($mandateArray[Entity::ID]);

        $mandate->setCreatedAt($mandateArray[Entity::CREATED_AT]);

        $vpaCore = new Vpa\Core;
        $payer = ($vpaCore)->find($mandateArray[Entity::PAYER_ID], false);
        $payee = ($vpaCore)->find($mandateArray[Entity::PAYEE_ID], false);

        $baCore = new BankAccount\Core;
        $bankAccount = ($baCore)->find($mandateArray[Entity::BANK_ACCOUNT_ID], false);

        $customer = $this->context()->getDevice()->customer;

        $upi = new UpiMandate\Entity($mandateArray[Entity::UPI]);

        $upi->associateMandate($mandate);

        $mandate->setUpi($upi);
        $mandate->setPayee($payee);
        $mandate->setPayer($payer);
        $mandate->setBankAccount($bankAccount);
        $mandate->setCustomer($customer);
    }
}
