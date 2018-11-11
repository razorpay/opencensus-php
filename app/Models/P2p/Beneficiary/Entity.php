<?php

namespace Rzp\Models\P2p\Beneficiary;

use RZP\Models\P2p\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\Entity
{
    const ID                       = 'id';

    const ENTITY                   = 'entity';

    const BENEFICIARY_NAME         = 'beneficiary_name';

    const ADDRESS                  = 'address';

    const USERNAME                 = 'username';

    const HANDLE                   = 'handle';

    const MASKED_ACCOUNT_NUMBER    = 'masked_account_number';

    const IFSC_CODE                = 'ifsc_code';

    const BANK_NAME                = 'bank_name';

    const CREATED_AT               = 'created_at';


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
     * @return beneficiary_name
     */
    public function getBeneficiaryName()
    {
        return $this->getAttribute(self::BENEFICIARY_NAME);
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
     * @return masked_account_number
     */
    public function getMaskedAccountNumber()
    {
        return $this->getAttribute(self::MASKED_ACCOUNT_NUMBER);
    }

    /**
     * @return ifsc_code
     */
    public function getIfscCode()
    {
        return $this->getAttribute(self::IFSC_CODE);
    }

    /**
     * @return bank_name
     */
    public function getBankName()
    {
        return $this->getAttribute(self::BANK_NAME);
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
    public function setBeneficiaryName($beneficiaryName)
    {
        return $this->setAttribute(self::BENEFICIARY_NAME, $beneficiaryName);
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
    public function setMaskedAccountNumber($maskedAccountNumber)
    {
        return $this->setAttribute(self::MASKED_ACCOUNT_NUMBER, $maskedAccountNumber);
    }

    /**
     * @return $this
     */
    public function setIfscCode($ifscCode)
    {
        return $this->setAttribute(self::IFSC_CODE, $ifscCode);
    }

    /**
     * @return $this
     */
    public function setBankName($bankName)
    {
        return $this->setAttribute(self::BANK_NAME, $bankName);
    }

    /**
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setAttribute(self::CREATED_AT, $createdAt);
    }
}
