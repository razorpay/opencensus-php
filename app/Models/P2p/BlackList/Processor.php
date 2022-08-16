<?php

namespace RZP\Models\P2p\BlackList;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\BankAccount;
use RZP\Exception\RuntimeException;
use RZP\Models\Base\PublicCollection;

/**
 * @property Core $core
 * @property Validator $validator
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function add(array $input): array
    {
        $this->initialize(Action::ADD, $input, true);

        $blackList = $this->findEntityId($this->input->get(Entity::TYPE), $input);

        $entity = $this->core->create($blackList, $input);

        return $entity->toArrayPublic();
    }

    public function remove(array $input): array
    {
        throw new RuntimeException("Not implemented, processor Implementation is on the way");
    }

    protected function findEntityId(string $type, array $input)
    {
        switch ($type)
        {
            case BankAccount\Entity::BANK_ACCOUNT:

                throw new RuntimeException("Not implemented, processor Implementation is on the way");

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
