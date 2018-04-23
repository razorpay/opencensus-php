<?php

namespace RZP\Models\Key;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;

class Service extends Base\Service
{
    public function createKey()
    {
        $merchant = $this->merchant;

        (new Validator)->checkHasKeyAccess($merchant, $this->mode);

        $keyData = (new Core)->createFirstKey($merchant, $this->mode);

        if ($this->mode === Mode::LIVE)
        {
            $action = Merchant\Action::LIVE_KEYS_CREATED;
        }
        elseif ($this->mode === Mode::TEST)
        {
            $action = Merchant\Action::TEST_KEYS_CREATED;
        }

        if ($action !== null)
        {
            $this->app['eventManager']->trackEvents($merchant, $action, $merchant->toArrayEvent());
        }

        return $keyData;
    }

    public function fetchKeys()
    {
        (new Validator)->checkHasKeyAccess($this->merchant, $this->mode);

        $merchantId = $this->merchant->getId();

        $keys = $this->repo->key->getKeysForMerchant($merchantId);

        return $keys->toArrayPublic();
    }

    public function updateKey($keyId, array $input)
    {
        (new Validator)->checkHasKeyAccess($this->merchant, $this->mode);

        $merchantId = $this->merchant->getId();

        return (new Core)->rollKey($merchantId, $keyId, $input, $this->mode);
    }
}
