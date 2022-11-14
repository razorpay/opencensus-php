<?php


namespace RZP\Models\Merchant\Consent\Details;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;

/**
 * Class Entity
 *
 * @package RZP\Models\Merchant\Consent\Details
 */
class Entity extends Base\PublicEntity
{
    const ID         = 'id';
    const URL        = 'url';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $entity             = 'merchant_consent_details';

    protected $generateIdOnCreate = true;

    protected $fillable           = [
        self::ID,
        self::URL
    ];

    protected $public             = [
        self::ID,
        self::URL
    ];

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getURL()
    {
        return $this->getAttribute(self::URL);
    }

    public function setId($id)
    {
        return $this->setAttribute(self::ID, $id);
    }

    public function setURL($url)
    {
        return $this->setAttribute(self::URL, $url);
    }
}
