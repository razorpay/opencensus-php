<?php
namespace RZP\Models\Merchant\M2MReferral\FriendBuy;

use RZP\Models\Merchant\M2MReferral\Entity as M2MEntity;
use RZP\Models\Merchant\M2MReferral\Constants as M2MConstants;
abstract class FriendBuyRequest
{
    public $path;

    public $authToken;

    public function __construct()
    {

    }

    public abstract function getPath();

    public abstract function toArray(): array;
}
