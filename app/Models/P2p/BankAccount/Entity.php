<?php

namespace Rzp\Models\P2p\BankAccount;

use RZP\Models\P2p\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\Entity
{
    const ID                       = 'id';

    const ENTITY                   = 'entity';

    const IFSC                     = 'ifsc';

    const BANK_NAME                = 'bank_name';

    const BENEFICIARY_NAME         = 'beneficiary_name';

    const MASKED_ACCOUNT_NUMBER    = 'masked_account_number';

    const CREDS                    = 'creds';

    const CL_REGISTRATION_FORMAT   = 'cl.registration_format';

    const REFRESHED_AT             = 'refreshed_at';

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
     * @return ifsc
     */
    public function getIfsc()
    {
        return $this->getAttribute(self::IFSC);
    }

    /**
     * @return bank_name
     */
    public function getBankName()
    {
        return $this->getAttribute(self::BANK_NAME);
    }

    /**
     * @return beneficiary_name
     */
    public function getBeneficiaryName()
    {
        return $this->getAttribute(self::BENEFICIARY_NAME);
    }

    /**
     * @return masked_account_number
     */
    public function getMaskedAccountNumber()
    {
        return $this->getAttribute(self::MASKED_ACCOUNT_NUMBER);
    }

    /**
     * @return creds
     */
    public function getCreds()
    {
        return $this->getAttribute(self::CREDS);
    }

    /**
     * @return cl.registration_format
     */
    public function getClRegistrationFormat()
    {
        return $this->getAttribute(self::CL_REGISTRATION_FORMAT);
    }

    /**
     * @return refreshed_at
     */
    public function getRefreshedAt()
    {
        return $this->getAttribute(self::REFRESHED_AT);
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
    public function setIfsc($ifsc)
    {
        return $this->setAttribute(self::IFSC, $ifsc);
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
    public function setBeneficiaryName($beneficiaryName)
    {
        return $this->setAttribute(self::BENEFICIARY_NAME, $beneficiaryName);
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
    public function setCreds($creds)
    {
        return $this->setAttribute(self::CREDS, $creds);
    }

    /**
     * @return $this
     */
    public function setClRegistrationFormat($clRegistrationFormat)
    {
        return $this->setAttribute(self::CL_REGISTRATION_FORMAT, $clRegistrationFormat);
    }

    /**
     * @return $this
     */
    public function setRefreshedAt($refreshedAt)
    {
        return $this->setAttribute(self::REFRESHED_AT, $refreshedAt);
    }

    /**
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setAttribute(self::CREATED_AT, $createdAt);
    }
}
