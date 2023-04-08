<?php

namespace RZP\Models\Settings;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use Razorpay\Spine\DataTypes\Dictionary;

class Service extends Base\Service
{
    public function get(string $module, string $key, Merchant\Entity $merchant = null): array
    {
        $merchant = $merchant ?? $this->merchant;

        $settings = Accessor::for($merchant, $module)
                            ->get($key);
        
        return ['settings' => $settings];
    }

    public function getAll(string $module): array
    {
        $settings = Accessor::for($this->merchant, $module)
                            ->all();

        return ['settings' => $settings];
    }

    public function upsert(string $module, array $input, Merchant\Entity $merchant = null)
    {
        $merchant = $merchant ?? $this->merchant;

        Accessor::for($merchant, $module)
                ->upsert($input)
                ->save();
    }

    public function delete(string $module, string $key)
    {
        Accessor::for($this->merchant, $module)
                ->delete($key)
                ->save();
    }

    /**
     * Return pre-defined settings for a module
     * To be used for clients for a settings CRUD UI
     *
     * @param string $module
     *
     * @return array
     */
    public function getDefined(string $module): array
    {
        $settings = Keys::getWithDescriptions($module);

        return ['settings' => $settings];
    }

    public function getForMerchant(string $module, string $key, Merchant\Entity $merchant = null)
    {
        $merchant = $merchant ?? $this->merchant;

        $setting = Accessor::for($merchant, $module)->get($key);

        if ($setting instanceof Dictionary)
        {
            $setting = null;
        }

        return $setting;
    }

    public function getSettingsIfKeyPresent(string $module, string $key, $value, $offset, $limit)
    {
        // so get all the settings for this module, if this key is present in it
        return $this->repo->settings->getSettingsIfKeyPresent($module, $key, $value, $offset, $limit);
    }

    public function getSettings(string $entityId, $module, $key)
    {
        return $this->repo->settings->getSettings($entityId, $module, $key);
    }

}
