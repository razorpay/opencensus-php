<?php

namespace RZP\Models\P2p\BlackList;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\BankAccount;
use RZP\Exception\RuntimeException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\P2p\BankAccount\Entity as BankAccountEntity;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    /**
     * This is the method to add blacklist entry
     * @param array $input
     *
     * @return array
     * @throws Exception\P2p\BadRequestException
     * @throws RuntimeException
     */
    public function add(array $input): array
    {
        $this->initialize(Action::ADD_BLACKLIST, $input, true);

        $blackList = $this->findEntityIdByType($this->input->get(Entity::TYPE), $input);

        $input = [
            Entity::TYPE => $blackList->getP2pEntityName(),
            Entity::ENTITY_ID => $blackList->getId(),
        ];

        $entity = $this->core->create($blackList, $input);

        return $entity->toArrayPublic();
    }

    /**
     * This is the method to remove the black list entry
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\P2p\BadRequestException
     */
    public function remove(array $input):array
    {
        $this->initialize(Action::REMOVE_BLACKLIST, $input, true);

        $entity = $this->findEntityIdByType($this->input->get(Entity::TYPE), $input);

        $blackList = $this->core->findByEntityData([Entity::ENTITY_ID => $entity[Entity::ID]]);

        $this->core->delete($blackList);

        return $blackList->toArrayPublic();
    }

    /**
     * This is the method to find the entity by type and input
     * @param string $type
     * @param array  $input
     *
     * @return mixed
     * @throws Exception\P2p\BadRequestException
     */
    protected function findEntityIdByType(string $type, array $input)
    {
        switch ($type)
        {
            case BankAccount\Entity::BANK_ACCOUNT:

                $input[BankAccountEntity::IFSC] = strtoupper($input[BankAccountEntity::IFSC]);

                $bankAccount =(new BankAccount\Core)->findByAccountDetails($input);

                if($bankAccount === null)
                {
                    throw $this->badRequestException(ErrorCode::BAD_REQUEST_BANK_ACCOUNT_ID_MISSING);
                }

                return $bankAccount;

            case Vpa\Entity::VPA:

                $vpa =  (new Vpa\Core)->findByUsernameHandle($input, true);

                if($vpa === null)
                {
                    throw $this->badRequestException(ErrorCode::BAD_REQUEST_VPA_DOESNT_EXIST);
                }

                return $vpa;
        }
    }
}
