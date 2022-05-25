<?php


namespace RZP\Models\CreditTransfer\Helper;

use RZP\Models\CreditTransfer\Entity;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Base\Entity as BaseEntity;

abstract class Base extends BaseCore
{
    /**
     * @var BaseEntity
     */
    protected $source;

    /**
     * @var Entity
     */
    protected $creditTransfer;

    public function __construct(BaseEntity $source)
    {
        parent::__construct();

        $this->setSource($source);
    }

    public function setSource(BaseEntity $source)
    {
        $this->source = $source;
    }

    abstract public function getCreditAccountTypeFromSourceEntity();

    abstract public function getDestinationVirtualAccountFromSourceEntity();

    abstract public function getCreditTransferInputFromSourceEntity();
}
