<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Base;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\P2p\Mandate\Patch\Action as PatchAction;

/**
 * * @property Core $core
 */
class Core extends Base\Core
{
    /**
     * This is the method to create mandate entity
     * @param array $input
     *
     * @return Entity
     */
    public function create(Properties $properties, array $input): Entity
    {
        $mandate = $this->build($input);

        $properties->attachToMandate($mandate);

        $this->repo->saveOrFail($mandate);

        return $mandate;
    }


    /**
     * This is the method to update the mandate entity
     * @param Entity $mandate
     * @param array  $input
     *
     * @return Entity
     */
    public function update(Entity $mandate, array $input): Entity
    {
        $this->repo->saveOrFail($mandate);

        return $mandate;
    }

    /**
     * @param Entity $mandate
     * @param string $action
     * @param array  $input
     * This is the method to build upi for mandates
     * @return UpiMandate\Entity
     */
    public function buildUpi(Entity $mandate, string $action, array $input): UpiMandate\Entity
    {
        $refId                = $this->context()->getRequestId();
        $networkTransactionId = $this->context()->handlePrefix() . $this->app['request']->getId();

        $default = [
            UpiMandate\Entity::NETWORK_TRANSACTION_ID   => $networkTransactionId,
            UpiMandate\Entity::REF_ID                   => $refId,
        ];

        $cleaned = $this->cleanUpiInput(array_merge($default, $input));

        $defined = [
            UpiMandate\Entity::STATUS                   => $mandate->getInternalStatus(),
            UpiMandate\Entity::ACTION                   => $action,
        ];

        $upi = (new UpiMandate\Core)->build(array_merge($cleaned, $defined));

        return $upi;
    }

    /***
     * @param array $input
     * This is the method to find all upis which are associated with upi
     * @return PublicCollection
     * @throws LogicException
     */
    public function findAllUpi(array $input): PublicCollection
    {
        if (isset($input[UpiMandate\Entity::ACTION]) === false)
        {
            throw $this->logicException('Action is required', $input);
        }

        $defined = array_only($input, UpiMandate\Entity::ACTION);

        $mandateId = $input[UpiMandate\Entity::MANDATE_ID] ?? null;
        $networkTransactionId = $input[UpiMandate\Entity::NETWORK_TRANSACTION_ID] ?? null;

        $this->trace()->info(TraceCode::P2P_CALLBACK_TRACE,[
            'action'                    => $defined[UpiMandate\Entity::ACTION],
            'rrn'                       => $input[UpiMandate\Entity::RRN] ?? null,
            'mandate_id'                => $mandateId,
            'network_transaction_id'    => $networkTransactionId
        ]);

        if (empty($networkTransactionId) === false)
        {
            $defined[UpiMandate\Entity::NETWORK_TRANSACTION_ID] = $networkTransactionId;
        }
        else if (empty($mandateId) === false)
        {
            $defined[UpiMandate\Entity::MANDATE_ID] = $mandateId;
        }
        else
        {
            throw $this->logicException('Invalid find parameters', $input);
        }

        $upi = (new UpiMandate\Core)->findAll($defined);

        return $upi;
    }

    /**
     * @param UpiMandate\Entity $upi
     * @param array             $input
     * This is the method to update upi data
     * @return UpiMandate\Entity
     */
    public function updateUpi(UpiMandate\Entity $upi, array $input)
    {
        $cleaned = $this->cleanUpiInput($input);

        $upi = (new UpiMandate\Core)->update($upi, $cleaned);

        return $upi;
    }

    /**
     * @param array $input
     *  This is the method to clean upi input
     * @return array
     */
    protected function cleanUpiInput(array $input): array
    {
        unset($input[UpiMandate\Entity::MANDATE_ID],
            $input[UpiMandate\Entity::ACTION],
            $input[UpiMandate\Entity::HANDLE],
            $input[UpiMandate\Entity::MANDATE]);

        return $input;
    }

    /**
     * @param string $input
     * This is the method to find mandate by umn
     * @return mixed
     */
    public function findByUMN(string $input)
    {
        return $this->repo->findByUMN($input);
    }

    /**
     * @param array $input
     *  This is the method to clean upipatch input
     * @return array
     */
    protected function cleanPatchInput(array $input): array
    {
        unset($input[UpiMandate\Entity::MANDATE_ID],
            $input[UpiMandate\Entity::ACTION],
            $input[UpiMandate\Entity::STATUS],
            $input[UpiMandate\Entity::HANDLE]);

        return $input;
    }

    /**
     * @param UpiMandate\Entity $upi
     * @param array             $input
     * This is the method to update upi data
     * @return UpiMandate\Entity
     */
    public function findPatchByMandateIdAndActive(string $id, bool $active)
    {
        $patch = (new Patch\Core)->findPatchByMandateIdAndActive($id, $active);

        return $patch;
    }
}
