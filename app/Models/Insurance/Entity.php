<?php

namespace RZP\Models\Insurance;

use RZP\Models\Base\PublicEntity;
use RZP\Constants\Entity as ConstantsEntity;

class Entity extends PublicEntity
{
    /**
     *  properties of this entity
     */
    const STATUS = 'status';
    const CLAIM_STATUS = 'claim_status';
    const CLAIM_HISTORY = 'claim_history';
    const INSURANCE_PROVIDER = 'insurance_provider';
    const INSURED_ENTITY_ID = 'insured_entity_id';
    const INSURED_ENTITY_TYPE = 'insured_entity_type';

    protected $entity = ConstantsEntity::INSURANCE;
}
