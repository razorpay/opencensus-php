<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Base\Traits\NotesTrait;

use Illuminate\Database\Eloquent\SoftDeletes;
use MVanDuijker\TransactionalModelEvents as TransactionalModelEvents;

/**
 * Class Entity
 *
 * @property Merchant\Entity $merchant
 * @property Detail\Entity $merchantDetail
 *
 * @package RZP\Models\Merchant\Stakeholder
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use SoftDeletes;
    use TransactionalModelEvents\TransactionalAwareEvents;

    protected $entity = 'stakeholder';

    const ID_LENGTH = 14;

    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const EMAIL                     = 'email';
    const NAME                      = 'name'; // this will store the name ass per the promoter pan
    const PHONE_PRIMARY             = 'phone_primary';
    const PHONE_SECONDARY           = 'phone_secondary';
    const DIRECTOR                  = 'director';
    const EXECUTIVE                 = 'executive';
    const PERCENTAGE_OWNERSHIP      = 'percentage_ownership';
    const NOTES                     = 'notes';
    const POI_IDENTIFICATION_NUMBER = 'poi_identification_number';
    const POI_STATUS                = 'poi_status';
    const PAN_DOC_STATUS            = 'pan_doc_status';
    const POA_STATUS                = 'poa_status';
    const AADHAAR_ESIGN_STATUS      = 'aadhaar_esign_status';
    const AADHAAR_VERIFICATION_WITH_PAN_STATUS   = 'aadhaar_verification_with_pan_status';
    const AADHAAR_PIN               = 'aadhaar_pin';
    const AADHAAR_LINKED            = 'aadhaar_linked';
    const BVS_PROBE_ID              = 'bvs_probe_id';
    const AUDIT_ID                  = 'audit_id';
    const CREATED_AT                = 'created_at';
    const UPDATED_AT                = 'updated_at';
    const VERIFICATION_METADATA     = 'verification_metadata';

    protected $generateIdOnCreate = true;

    protected static $sign = 'sth';

    protected $fillable = [
        self::MERCHANT_ID,
        self::EMAIL,
        self::NAME,
        self::PHONE_PRIMARY,
        self::PHONE_SECONDARY,
        self::DIRECTOR,
        self::EXECUTIVE,
        self::PERCENTAGE_OWNERSHIP,
        self::NOTES,
        self::POI_IDENTIFICATION_NUMBER,
        // added here because these are copied from merchant details during create
        // will be removed once dual write is removed
        self::POI_STATUS,
        self::PAN_DOC_STATUS,
        self::POA_STATUS,
        self::AADHAAR_ESIGN_STATUS,
        self::AADHAAR_VERIFICATION_WITH_PAN_STATUS,
        self::AADHAAR_PIN,
        self::AADHAAR_LINKED,
        self::BVS_PROBE_ID,
        self::AUDIT_ID,
        self::VERIFICATION_METADATA
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::EMAIL,
        self::NAME,
        self::PHONE_PRIMARY,
        self::PHONE_SECONDARY,
        self::DIRECTOR,
        self::EXECUTIVE,
        self::PERCENTAGE_OWNERSHIP,
        self::NOTES,
        self::POI_IDENTIFICATION_NUMBER,
        self::POI_STATUS,
        self::PAN_DOC_STATUS,
        self::POA_STATUS,
        self::AADHAAR_ESIGN_STATUS,
        self::AADHAAR_VERIFICATION_WITH_PAN_STATUS,
        self::AADHAAR_PIN,
        self::AADHAAR_LINKED,
        self::BVS_PROBE_ID,
        self::VERIFICATION_METADATA
    ];

    protected $casts = [
        self::DIRECTOR             => 'bool',
        self::EXECUTIVE            => 'bool',
        self::PERCENTAGE_OWNERSHIP => 'float',
        self::VERIFICATION_METADATA => 'array',
    ];

    protected $defaults           = [
        self::VERIFICATION_METADATA   => []
    ];

    public function getPercentageOwnershipAttribute($value)
    {
        return $value ? round($value / 100, 2) : null;
    }

    public function toArrayWithRawValuesForAccountService() : array {
        $array = $this->toArray();
        if(array_key_exists(self::PERCENTAGE_OWNERSHIP, $array)) {
            $array[self::PERCENTAGE_OWNERSHIP] = $this->getAttributes()[self::PERCENTAGE_OWNERSHIP];
        }
        return $array;
    }

    public function merchantDetail()
    {
        return $this->belongsTo('RZP\Models\Merchant\Detail\Entity', self::MERCHANT_ID, self::MERCHANT_ID);
    }

    public function setPoiStatus(string $status = null)
    {
        $this->setAttribute(self::POI_STATUS, $status);
    }

    public function getPoiStatus()
    {
        return $this->getAttribute(self::POI_STATUS);
    }

    public function setPanDocStatus(string $status = null)
    {
        $this->setAttribute(self::PAN_DOC_STATUS, $status);
    }

    public function getPanDocStatus()
    {
        return $this->getAttribute(self::PAN_DOC_STATUS);
    }

    public function setPoaStatus(string $status = null)
    {
        $this->setAttribute(self::POA_STATUS, $status);
    }

    public function getPoaStatus()
    {
        return $this->getAttribute(self::POA_STATUS);
    }

    public function getPoiIdentificationNumber()
    {
        return $this->getAttribute(self::POI_IDENTIFICATION_NUMBER);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getAadhaarLinked()
    {
        return $this->getAttribute(self::AADHAAR_LINKED);
    }

    public function getAadhaarEsignStatus()
    {
        return $this->getAttribute(self::AADHAAR_ESIGN_STATUS);
    }

    public function getAadhaarVerificationWithPanStatus()
    {
        return $this->getAttribute(self::AADHAAR_VERIFICATION_WITH_PAN_STATUS);
    }

    public function getBvsProbeId()
    {
        return $this->getAttribute(self::BVS_PROBE_ID);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getPhonePrimary()
    {
        return $this->getAttribute(self::PHONE_PRIMARY);
    }

    public function getPhoneSecondary()
    {
        return $this->getAttribute(self::PHONE_SECONDARY);
    }

    public function getDirector()
    {
        return $this->getAttribute(self::DIRECTOR);
    }

    public function getExecutive()
    {
        return $this->getAttribute(self::EXECUTIVE);
    }

    public function getPercentageOwnership()
    {
        return $this->getAttribute(self::PERCENTAGE_OWNERSHIP);
    }

    public function getVerificationMetadata()
    {
        return $this->getAttribute(self::VERIFICATION_METADATA);
    }

    public function setVerificationMetadata($verificationMetadata)
    {
        $this->setAttribute(self::VERIFICATION_METADATA, $verificationMetadata);
    }

    public function getValueFromVerificationMetaData($key)
    {
        $verificationMetaData   = $this->getAttribute(self::VERIFICATION_METADATA);

        $value = null;

        if (empty($verificationMetaData) === false and array_key_exists($key, $verificationMetaData))
        {
            $value = $verificationMetaData[$key];
        }

        return $value;
    }
}
