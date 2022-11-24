<?php

namespace RZP\Models\Admin\AdminLead;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Admin\Base;
use RZP\Models\Admin\Admin;

class Entity extends Base\Entity
{
    use SoftDeletes;

    const ADMIN_ID          = 'admin_id';
    const ORG_ID            = 'org_id';
    const MERCHANT_ID       = 'merchant_id';
    const TOKEN             = 'token';
    const EMAIL             = 'email';
    const FORM_DATA         = 'form_data';
    const DELETED_AT        = 'deleted_at';
    const SIGNED_UP_AT      = 'signed_up_at';

    // Used by PUT requests to set signed_up_at
    const SIGNED_UP         = 'signed_up';

    protected $entity = 'admin_lead';

    protected static $sign = 'adl';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::SIGNED_UP_AT,
        self::TOKEN,
        self::EMAIL,
        self::FORM_DATA,
    ];

    protected $public = [
        self::ID,
        self::ADMIN_ID,
        self::SIGNED_UP_AT,
        self::ORG_ID,
        self::EMAIL,
        self::FORM_DATA,
        self::CREATED_AT,
    ];

    protected $visible = [
        self::ID,
        self::ADMIN_ID,
        self::ORG_ID,
        self::TOKEN,
        self::EMAIL,
        self::FORM_DATA,
        self::CREATED_AT,
        self::SIGNED_UP_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ORG_ID,
        self::ADMIN_ID,
    ];

    protected $casts = [
        self::SIGNED_UP_AT => 'int',
    ];

    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    public function org()
    {
        return $this->belongsTo('RZP\Models\Admin\Org\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function setPublicAdminIdAttribute(array &$attributes)
    {
        $adminId = $this->getAdminId();

        if ($adminId !== null)
        {
            $attributes[self::ADMIN_ID] = Admin\Entity::getSignedId($adminId);
        }
    }

    public function setFormDataAttribute(array $formData)
    {
        $formData = json_encode($formData);

        $this->attributes[self::FORM_DATA] = $formData;
    }

    public function getFormDataAttribute(string $formData)
    {
        return json_decode($formData, true);
    }

    public function setFormData(array $formData)
    {
        $this->setAttribute(self::FORM_DATA, $formData);
    }

    public function setSignedUpAt(int $time)
    {
        $this->setAttribute(self::SIGNED_UP_AT, $time);
    }

    public function getFormData() : array
    {
        return $this->getAttribute(self::FORM_DATA);
    }

    public function getOrgId() : string
    {
        return $this->getAttribute(self::ORG_ID);
    }

    public function getToken() : string
    {
        return $this->getAttribute(self::TOKEN);
    }

    public function getAdminId() : string
    {
        return $this->getAttribute(self::ADMIN_ID);
    }

    public function getEmail() : string
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getInputFields() : array
    {
        // Fields in the adminLead table are stored as json in FORM_DATA
        $validator = $this->getValidator();

        $rulesVar = $validator->getRulesForOperation('send_invitation');

        return array_keys($rulesVar);
    }
}
