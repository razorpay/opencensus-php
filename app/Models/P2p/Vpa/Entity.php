<?php

namespace Rzp\Models\P2p\Vpa;

use RZP\Models\P2p\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\Entity
{
    const ID               = 'id';

    const ENTITY           = 'entity';

    const ADDRESS          = 'address';

    const USERNAME         = 'username';

    const HANDLE           = 'handle';

    const BANK_ACCOUNT_ID  = 'bank_account_id';

    const CREATED_AT       = 'created_at';


    /**************** GETTER *******************/

    /**
     * @return id
     */
    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    /**
     * @return entity
     */
    public function getEntity()
    {
        return $this->getAttribute(self::ENTITY);
    }

    /**
     * @return address
     */
    public function getAddress()
    {
        return $this->getAttribute(self::ADDRESS);
    }

    /**
     * @return username
     */
    public function getUsername()
    {
        return $this->getAttribute(self::USERNAME);
    }

    /**
     * @return handle
     */
    public function getHandle()
    {
        return $this->getAttribute(self::HANDLE);
    }

    /**
     * @return bank_account_id
     */
    public function getBankAccountId()
    {
        return $this->getAttribute(self::BANK_ACCOUNT_ID);
    }

    /**
     * @return created_at
     */
    public function getCreatedAt()
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    /**************** SETTER *******************/

    /**
     * @return $this
     */
    public function setId($id)
    {
        return $this->setAttribute(self::ID, $id);
    }

    /**
     * @return $this
     */
    public function setEntity($entity)
    {
        return $this->setAttribute(self::ENTITY, $entity);
    }

    /**
     * @return $this
     */
    public function setAddress($address)
    {
        return $this->setAttribute(self::ADDRESS, $address);
    }

    /**
     * @return $this
     */
    public function setUsername($username)
    {
        return $this->setAttribute(self::USERNAME, $username);
    }

    /**
     * @return $this
     */
    public function setHandle($handle)
    {
        return $this->setAttribute(self::HANDLE, $handle);
    }

    /**
     * @return $this
     */
    public function setBankAccountId($bankAccountId)
    {
        return $this->setAttribute(self::BANK_ACCOUNT_ID, $bankAccountId);
    }

    /**
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setAttribute(self::CREATED_AT, $createdAt);
    }
}
