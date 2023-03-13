<?php

namespace RZP\Models\ClarificationDetail;

use RZP\Models\Base;
use RZP\Models\Merchant\Detail\NeedsClarificationMetaData;
use RZP\Models\Merchant\Detail\NeedsClarificationReasonsList;

class Entity extends Base\PublicEntity
{
    const ID            = 'id';
    const MERCHANT_ID   = 'merchant_id';
    const STATUS        = 'status';
    const COMMENT_DATA  = 'comment_data';
    const MESSAGE_FROM  = 'message_from';
    const GROUP_NAME    = 'group_name';
    const FIELD_DETAILS = 'field_details';
    const METADATA      = 'metadata';
    const AUDIT_ID      = 'audit_id';
    const CREATED_AT    = 'created_at';
    const UPDATED_AT    = 'updated_at';

    protected $entity     = 'clarification_detail';

    protected $primaryKey = self::ID;

    protected $fillable   = [
        self::ID,
        self::MERCHANT_ID,
        self::STATUS,
        self::COMMENT_DATA,
        self::MESSAGE_FROM,
        self::GROUP_NAME,
        self::FIELD_DETAILS,
        self::METADATA,
        self::AUDIT_ID,
    ];

    protected $public     = [
        self::MERCHANT_ID,
        self::STATUS,
        self::COMMENT_DATA,
        self::MESSAGE_FROM,
        self::FIELD_DETAILS,
        self::CREATED_AT,
        self::METADATA,
    ];

    protected $casts      = [
        self::FIELD_DETAILS => 'array',
        self::COMMENT_DATA  => 'array',
        self::METADATA      => 'array',
    ];

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getFieldDetails()
    {
        return $this->getAttribute(self::FIELD_DETAILS);
    }

    public function getGroupName()
    {
        return $this->getAttribute(self::GROUP_NAME);
    }

    public function getAdminEmail()
    {
        return $this->getAttribute(self::METADATA)[Constants::ADMIN_EMAIL] ?? null;
    }

    public function getNcCount()
    {
        return $this->getAttribute(self::METADATA)[Constants::NC_COUNT] ?? null;
    }

    //all fields which are in nc for the group
    public function getFields()
    {
        return array_keys($this->getAttribute(self::FIELD_DETAILS));
    }

    //populate all fields with null value since its a comment submission
    public function generateDefaultFieldDetails()
    {
        $newFieldDetails = [];

        foreach ($this->getFieldDetails() as $fieldName => $fieldValue)
        {
            $newFieldDetails = array_merge($newFieldDetails, [$fieldName => null]);
        }

        return $newFieldDetails;
    }

    // if all fields have null value i.e. it is a comment and field details submission
    public function getFieldDetailsIfApplicable()
    {
        $commentData = $this->toArrayPublic();

        $fieldDetailsNull = true;

        foreach ($commentData[Entity::FIELD_DETAILS] as $field => $value)
        {
            $fieldDetailsNull = ($fieldDetailsNull and empty($value));
        }

        if ($fieldDetailsNull)
        {
            $commentData[Entity::FIELD_DETAILS] = null;
        }

        return $commentData[Entity::FIELD_DETAILS] ?? [];
    }

    public function isMessageFromMerchant()
    {
        $messageFrom = $this->getAttribute(self::MESSAGE_FROM);

        if (empty($messageFrom) === false and $messageFrom === 'merchant')
        {
            return true;
        }

        return false;
    }

    public function getCommentData()
    {
        $commentData = $this->toArrayPublic();

        $fieldDetailsNull = true;

        foreach ($commentData[Entity::FIELD_DETAILS] as $field => $value)
        {
            $fieldDetailsNull = ($fieldDetailsNull and empty($value));
        }

        if ($fieldDetailsNull)
        {
            $commentData[Entity::FIELD_DETAILS] = null;
        }

        if (isset($commentData[Entity::COMMENT_DATA]) === true and $commentData[Entity::COMMENT_DATA][Constants::TYPE] === "predefined")
        {
            $commentData[Entity::COMMENT_DATA][Constants::TEXT] = NeedsClarificationReasonsList::REASON_DETAILS[$commentData[Entity::COMMENT_DATA][Constants::TEXT]][NeedsClarificationMetaData::DESCRIPTION];
        }

        if (isset($commentData[Entity::MESSAGE_FROM]) and $commentData[Entity::MESSAGE_FROM] === 'admin')
        {
            $commentData[Constants::ADMIN_EMAIL] = $this->getAdminEmail();
        }

        $commentData[Constants::NC_COUNT] = $this->getNcCount();

        unset($commentData[Entity::METADATA]);

        return $commentData;
    }

    public function getAdminComment()
    {
        $commentData = $this->toArrayPublic();

        $adminComment = null;

        if (isset($commentData[Entity::COMMENT_DATA]) === true and $commentData[Entity::COMMENT_DATA][Constants::TYPE] === "predefined")
        {
            $commentData[Entity::COMMENT_DATA][Constants::TEXT] = NeedsClarificationReasonsList::REASON_DETAILS[$commentData[Entity::COMMENT_DATA][Constants::TEXT]][NeedsClarificationMetaData::DESCRIPTION];
        }

        if (isset($commentData[Entity::MESSAGE_FROM]) and $commentData[Entity::MESSAGE_FROM] === 'admin')
        {
            $adminComment = $commentData[Entity::COMMENT_DATA][Constants::TEXT];
        }

        return $adminComment;
    }
}
