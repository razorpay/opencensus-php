<?php

namespace Rzp\Models\P2p\BankAccount\Bank;

use RZP\Models\P2p\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\Entity
{
    const IFSC             = 'ifsc';

    const NAME             = 'name';

    const UPI_IIN          = 'upi_iin';

    const UPI_FORMAT       = 'upi_format';

    const REFRESHED_AT     = 'refreshed_at';

    const SPOC             = 'spoc';


    /**************** GETTER *******************/

    /**
     * @return ifsc
     */
    public function getIfsc()
    {
        return $this->getAttribute(self::IFSC);
    }

    /**
     * @return name
     */
    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    /**
     * @return upi_iin
     */
    public function getUpiIin()
    {
        return $this->getAttribute(self::UPI_IIN);
    }

    /**
     * @return upi_format
     */
    public function getUpiFormat()
    {
        return $this->getAttribute(self::UPI_FORMAT);
    }

    /**
     * @return refreshed_at
     */
    public function getRefreshedAt()
    {
        return $this->getAttribute(self::REFRESHED_AT);
    }

    /**
     * @return spoc
     */
    public function getSpoc()
    {
        return $this->getAttribute(self::SPOC);
    }

    /**************** SETTER *******************/

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
    public function setName($name)
    {
        return $this->setAttribute(self::NAME, $name);
    }

    /**
     * @return $this
     */
    public function setUpiIin($upiIin)
    {
        return $this->setAttribute(self::UPI_IIN, $upiIin);
    }

    /**
     * @return $this
     */
    public function setUpiFormat($upiFormat)
    {
        return $this->setAttribute(self::UPI_FORMAT, $upiFormat);
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
    public function setSpoc($spoc)
    {
        return $this->setAttribute(self::SPOC, $spoc);
    }
}
