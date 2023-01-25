<?php

namespace RZP\Models\Settings;

use App;
use Razorpay\Spine\DataTypes\Dictionary;
use anlutro\LaravelSettings\SettingsManager;

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

    /**
     * @var SettingsManager
     */
    protected SettingsManager $laravelSettings;

    public function __construct(Base\PublicEntity $entity, string $module, string $connection = null)
    {
        parent::__construct();

        $this->entity = $entity->getEntity();
        $this->id     = $entity->getId();
        $this->module = $module;

        if ($connection !== null)
        {
            config(['settings.connection' => $connection]);
        }

        $this->initLaravelSettings();

        $this->validateEntityAndModule();

        $this->setExtraColumns();
    }

    protected function initLaravelSettings()
    {
        $this->laravelSettings = new CustomSettingsManager(App::getFacadeRoot());
    }

    /**
     * Gets an instance of this class
     *
     * @param Base\PublicEntity $entity
     * @param string            $module
     *
     * @param string|null       $connection
     *
     * @return Accessor
     */
    public static function for(Base\PublicEntity $entity, string $module, string $connection = null): Accessor
    {
        return new static($entity, $module, $connection);
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
        $settings = $this->laravelSettings->all();

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
        $settings = $this->laravelSettings->get($key);

        return $this->serializeSettings($settings);
    }

    /**
     * Check if a key exists. Can be a parent key or a dot-notated nested key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        $value = $this->get($key);

        if ($value instanceof Dictionary)
        {
            $value = $value->toArray();
        }

        return (empty($value) === false);
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
     * @param mixed  $value
     *
     * @return Accessor
     */
    public function upsert($key, $value = null): Accessor
    {
        $this->trace->info(TraceCode::SETTINGS_UPSERT_REQUEST, [$key, $value]);

        $this->updateExtraColumnsWithCreatedAndUpdatedAt();

        $this->laravelSettings->set($key, $value);

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

        $this->laravelSettings->forget($key);

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
        $this->laravelSettings->save();
        
        $this->setExtraColumns();
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
            'entity_type'       => $this->entity,
            'entity_id'         => $this->id,
            Entity::MODULE      => $this->module,
        ];

        $this->laravelSettings->setExtraColumns($filterColumns);
    }


    protected function updateExtraColumnsWithCreatedAndUpdatedAt()
    {
        $filterColumns = [
            'entity_type'       => $this->entity,
            'entity_id'         => $this->id,
            Entity::MODULE      => $this->module,
        ];

        $this->laravelSettings->setExtraColumns($filterColumns);
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
