<?php


namespace RZP\Models\Mpan;

use App;
use RZP\Http\Route;
use RZP\Models\Base;
use RZP\Constants;
use RZP\Models\Merchant;
use RZP\Models\Base\UniqueIdEntity;

class Entity extends Base\PublicEntity
{
    const MPAN          = 'mpan';
    const NETWORK       = 'network';
    const ASSIGNED      = 'assigned';
    const MERCHANT_ID   = 'merchant_id';

    const ID_LENGTH = 16;

    protected $public = [
        self::MPAN,
        self::NETWORK,
        self::ASSIGNED,
        self::MERCHANT_ID,
    ];

    protected $fillable = [
        self::MPAN,
        self::NETWORK,
        self::ASSIGNED,
    ];

    protected $visible = [
        self::MPAN,
        self::NETWORK,
    ];

    protected $publicSetters = [
        self::MPAN,
        self::NETWORK,
        self::ASSIGNED,
        self::MERCHANT_ID,
    ];

    protected static $sign = self::MPAN;

    protected $primaryKey = self::MPAN;

    protected $entity = Constants\Entity::MPAN;

    protected $defaults = [
        self::ASSIGNED          => false,
        self::MERCHANT_ID       => null,
    ];

    protected $casts = [
        self::MPAN              => 'string',
        self::ASSIGNED          => 'bool',
        self::NETWORK           => 'string',
        self::MERCHANT_ID       => 'string',
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // Since, mpan column is also primary_key of the table, we don't want it to be verified as RZP unique id
    public static function verifyUniqueId($id, $throw = true)
    {
        return false;
    }

    public function getMpan()
    {
        return $this->getAttribute(self::MPAN);
    }

    public function getAssigned()
    {
        return $this->getAttribute(self::ASSIGNED);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    public function isAssigned()
    {
        return $this->getAssigned() === true;
    }

    public function getMaskedMpan(string $mpan = "")
    {
        // adding below condition so that this function can be called as a method on mpan object as well
        if ($mpan === "")
        {
            $mpan = $this->getMpan();
        }

        // if mpan is not 16 digit, it means its invalid and we can return as is
        if ( (empty($mpan) === true)
            or (strlen($mpan) !== 16) )
        {
            return $mpan;
        }

        $maskedMpan =  substr($mpan,0, 6) . str_repeat("*", 6) . substr($mpan, -4);

        return $maskedMpan;
    }

    public function setPublicMpanAttribute(array &$array)
    {
        $app = App::getFacadeRoot();

        $routeName = $app['api.route']->getCurrentRouteName();

        if (in_array($routeName, Route::$detokenizeMpansRoutes, true) === false)
        {
            return;
        }

        $variant = app('razorx')->getTreatment(UniqueIdEntity::generateUniqueId(), Merchant\RazorxTreatment::DETOKENIZE_MPANS,
            $this->mode ?? "live");

        // If experiment enabled then don't de-tokenize
        if (strtolower($variant) === 'on')
        {
            return;
        }

        $cardVaultApp = $app['mpan.cardVault'];

        // if mpan length is not 16, it means it is tokenized and needs to be detokenized
        if (empty($array[self::MPAN] === false) and (strlen($array[self::MPAN]) !== 16))
        {
            $array[self::MPAN] = $cardVaultApp->detokenize($array[self::MPAN]);
        }

    }

    public function setPublicNetworkAttribute(array &$array)
    {

    }

    public function setPublicAssignedAttribute(array &$array)
    {

    }

    public function setPublicMerchantIdAttribute(array &$array)
    {

    }

    /**
     * This is being overridden because, we store mpans in db in tokenized form
     * and merchants(e.g. some aggregators) who are able to access mpans should be able to see mpans in original form
     * @return array
     */
    public function toArrayPublic()
    {
        return  parent::toArrayPublic();
    }

}
