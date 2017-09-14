<?php

namespace RZP\Models\Settings;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function get(string $module, string $key): array
    {
        $settings = Accessor::for($this->merchant, $module)
                            ->get($key);

        return ['settings' => $settings];
    }

    public function getAll(string $module): array
    {
        $settings = Accessor::for($this->merchant, $module)
                            ->all();

        return ['settings' => $settings];
    }

    public function upsert(string $module, array $input)
    {
        Accessor::for($this->merchant, $module)
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
}
