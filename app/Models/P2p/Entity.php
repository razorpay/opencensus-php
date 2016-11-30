<?php

namespace RZP\Models\P2p;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Upi\Vpa;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                  = 'id';
    const SOURCE_ID           = 'source_id';
    const SOURCE_TYPE         = 'source_type';
    const SINK_ID             = 'sink_id';
    const SINK_TYPE           = 'sink_type';
    const STATUS              = 'status';
    const MERCHANT_ID         = 'merchant_id';
    const AMOUNT              = 'amount';
    const DESCRIPTION         = 'description';
    const TYPE                = 'type';
    const GATEWAY             = 'gateway';
    const NOTES               = 'notes';
    const CURRENCY            = 'currency';
    const INTERNAL_ERROR_CODE = 'internal_error_code';
    const ERROR_DESCRIPTION   = 'error_description';
    const ERROR_CODE          = 'error_code';

    protected $fillable = array(
        self::SOURCE_ID,
        self::SOURCE_TYPE,
        self::SINK_ID,
        self::SINK_TYPE,
        self::STATUS,
        self::MERCHANT_ID,
        self::AMOUNT,
        self::DESCRIPTION,
        self::TYPE,
        self::GATEWAY,
        self::NOTES,
        self::CURRENCY,
        self::INTERNAL_ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::ERROR_CODE,
    );

    protected $entity = 'p2p';

    protected static $generators = [
        self::SOURCE_TYPE,
        self::SINK_TYPE,
    ];

    protected $publicSetters = [
        self::SOURCE_ID,
        self::SINK_ID,
    ];

    protected function generateSourceType($input)
    {
        $sourceId = $this->getAttribute(self::SOURCE_ID);

        $this->setAttribute(self::SOURCE_TYPE, SourceType::VPA);
    }

    protected function generateSinkType($input)
    {
        $sinkId = $this->getAttribute(self::SINK_ID);

        if (strpos($sinkId, 'vpa_') === false)
        {
            $sinkType = SinkType::BANK_ACCOUNT;
        }
        else
        {
            $sinkType = SinkType::VPA;
        }

        $this->setAttribute(self::SOURCE_TYPE, $sinkType);
    }

    // ----------------------- Public Setters ------------------

    protected function setPublicSourceIdAttribute(array & $array)
    {
        $sourceId = $this->getAttribute(self::SOURCE_ID);

        $array[self::SOURCE_ID] = Vpa\Entity::getSignedId($sourceId);
    }

    protected function setPublicSinkIdAttribute(array & $array)
    {
        $sinkId = $this->getAttribute(self::SINK_ID);

        if ($this->getAttribute(self::SINK_TYPE) === SinkType::BANK_ACCOUNT)
        {
            $sinkId = 'ba_' . $sinkId;
        }
        else
        {
            $sinkId = Vpa\Entity::getSignedId($sinkId);
        }

        $array[self::SINK_ID] = $sinkId;
    }

    // ----------------------- Mutators ------------------

    protected function setSourceIdAttribute(array & $array)
    {
        $sourceId = $this->getAttribute(self::SOURCE_ID);

        $this->attributes[self::SOURCE_ID] = Vpa\Entity::stripSignWithoutValidation($sourceId);
    }

    protected function setSinkIdAttribute(array & $array)
    {
        $sinkId = $this->getAttribute(self::SINK_ID);

        if ($this->getAttribute(self::SINK_TYPE) === SinkType::BANK_ACCOUNT)
        {
            $ix = strpos($id, '_');

            $sinkId = substr($id, $ix + 1);
        }
        else
        {
            $sinkId = Vpa\Entity::stripSignWithoutValidation($sinkId);
        }

        $array[self::SINK_ID] = $sinkId;
    }
}
