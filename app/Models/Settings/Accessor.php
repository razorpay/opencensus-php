<?php

namespace RZP\Models\Settings;

use LaravelSettings;
use Razorpay\Spine\DataTypes\Dictionary;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

/**
 * Class Accessor
 *
 * Has helper methods to access Settings
 *
 * @package RZP\Models\Settings
 */
class Accessor extends Base\Core
{
    /**
     * Entity name for which the settings are saved
     *
     * @var string
     */
    protected $entity;

    /**
     * Entity ID
     *
     * @var string
     */
    protected $id;

    /**
     * Module for the settings
     * One of those defined in class Settings\Module
     * Ex: Openwallet, Onboarding
     *
     * @var string
     */
    protected $module;

    public function __construct(Base\PublicEntity $entity, string $module)
    {
        parent::__construct();

        $this->entity = $entity->getEntity();
        $this->id     = $entity->getId();
        $this->module = $module;

        $this->validateEntityAndModule();

        $this->setExtraColumns();
    }

    /**
     * Gets an instance of this class
     *
     * @param Base\PublicEntity $entity
     * @param string            $module
     *
     * @return Accessor
     */
    public static function for(Base\PublicEntity $entity, string $module): Accessor
    {
        return new static($entity, $module);
    }

    /**
     * Return a nested array of all settings defined for the module
     *
     * Example:
     * "closed" => [
     *      "max_limit"      => "2000000",
     *      "max_load_value" => "500000"
     * ]
     *
     * @return Dictionary|string
     */
    public function all()
    {
        $settings = LaravelSettings::all();

        return $this->serializeSettings($settings);
    }

    /**
     * Retrieve a single key's value, can be a parent key or nested child,
     * denoted by dot notated keys.
     *
     * Example:
     *
     * For the following saved settings -
     * "closed" => [
     *     "max_limit"      => "2000000",
     *     "max_load_value" => "500000"
     * ]
     *
     * `get('closed')` returns -
     * [
     *     "max_limit"      => "2000000",
     *     "max_load_value" => "500000"
     * ]
     *
     * `get('closed.max_limit')` returns -
     * "2000000"
     *
     * @param string $key
     *
     * @return Dictionary|string
     */
    public function get(string $key)
    {
        $settings = LaravelSettings::get($key);

        return $this->serializeSettings($settings);
    }

    /**
     * Inserts or updates settings
     *
     * Call `save()` after to persist to DB
     *
     * @param array|string $key - If string, the value will be set from
     *                          the `$value` parameter
     *                          - If array, the value parameter is disregarded, and
     *                          an associative array is saved. Example,
     *                          "closed" => ["max_limit" => "2000000"]
     *
     * @param string|null  $value
     *
     * @return Accessor
     */
    public function upsert($key, string $value = null): Accessor
    {
        $this->trace->info(TraceCode::SETTINGS_UPSERT_REQUEST, [$key, $value]);

        LaravelSettings::set($key, $value);

        return $this;
    }

    /**
     * Delete a key-value pair
     *
     * Can be either a parent or child-level key, dot
     * notated hierarchy
     *
     * Call `save()` after to persist to DB
     *
     * @param string $key
     *
     * @return Accessor
     */
    public function delete(string $key): Accessor
    {
        $this->trace->info(TraceCode::SETTINGS_DELETE_REQUEST, [$key]);

        LaravelSettings::forget($key);

        return $this;
    }

    /**
     * Save pending changes on the instance
     *
     * Call this after `upsert()` and `delete()` to persist
     * the changes
     */
    public function save()
    {
        LaravelSettings::save();
    }

    protected function validateEntityAndModule()
    {
        // TODO: Validate entity

        Module::validate($this->module);
    }

    /**
     * Set the extra columns that we filter on.
     *
     * Ref: https://github.com/anlutro/laravel-settings#example
     */
    protected function setExtraColumns()
    {
        $filterColumns = [
            'entity_type' => $this->entity,
            'entity_id'   => $this->id,
            'module'      => $this->module
        ];

        LaravelSettings::setExtraColumns($filterColumns);
    }

    /**
     * Serialize settings
     *
     * @param array|string|null $settings
     *
     * @return Dictionary|string
     */
    protected function serializeSettings($settings)
    {
        if ((is_array($settings) === true) or
            (is_null($settings) === true))
        {
            $settings = new Dictionary((array) $settings);
        }

        return $settings;
    }
}
