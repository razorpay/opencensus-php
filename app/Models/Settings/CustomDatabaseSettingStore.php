<?php

namespace RZP\Models\Settings;

use Illuminate\Support\Arr;
use Illuminate\Database\Connection;
use anlutro\LaravelSettings\ArrayUtil;
use anlutro\LaravelSettings\DatabaseSettingStore;


class CustomDatabaseSettingStore extends DatabaseSettingStore
{

    
    /**
     * The createdAt column storing timestamp.
     *
     * @var string
     */
    protected $createdAtColumn;

    /**
     * The updatedAt column storing timestamp.
     *
     * @var string
     */
    protected $updatedAtColumn;
    

    /**
     * @param \Illuminate\Database\Connection $connection
     * @param string                         $table
     */
    public function __construct(Connection $connection,
                                           $table = null,
                                           $keyColumn = null,
                                           $valueColumn = null,
                                           $createdAtColumn = null,
                                           $updatedAtColumn = null)
    {
        parent::__construct($connection, $table, $keyColumn, $valueColumn);
        $this->createdAtColumn = $createdAtColumn ?: 'created_at';
        $this->updatedAtColumn = $updatedAtColumn ?: 'updated_at';
    }
    

    /**
     * Set the value column name to query from.
     *
     * @param string $value_column
     */
    public function setCreatedAtColumn($valueColumn)
    {
        $this->createdAtColumn = $valueColumn;
    }

    /**
     * Set the value column name to query from.
     *
     * @param string $value_column
     */
    public function setUpdatedAtColumn($valueColumn)
    {
        $this->updatedAtColumn = $valueColumn;
    }

    /**
     * Returns fresh time
     *
     * @return integer
     */
    public function currentTimestamp()
    {
        return time();
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $data)
    {
        $keysQuery = $this->newQuery();

        // "lists" was removed in Laravel 5.3, at which point
        // "pluck" should provide the same functionality.
        $method = !method_exists($keysQuery, 'lists') ? 'pluck' : 'lists';
        $keys = $keysQuery->$method($this->keyColumn);

        $insertData = Arr::dot($data);
        $updatedData = Arr::dot($this->updatedData);
        $persistedData = Arr::dot($this->persistedData);
        $updateData = array();
        $deleteKeys = array();

        foreach ($keys as $key) {
            if (isset($updatedData[$key]) && isset($persistedData[$key]) && (string)$updatedData[$key] !== (string)$persistedData[$key]) {
                $updateData[$key] = $updatedData[$key];
            } elseif (!isset($insertData[$key])) {
                $deleteKeys[] = $key;
            }
            unset($insertData[$key]);
        }

        foreach ($updateData as $key => $value) {
            $updatedAtValue = $this->currentTimestamp();
            $this->newQuery()
                ->where($this->keyColumn, '=', strval($key))
                ->update(array(
                    $this->valueColumn => $value,
                    $this->updatedAtColumn => $updatedAtValue));
        }

        if ($insertData) {
            $this->newQuery(true)
                ->insert($this->prepareInsertData($insertData));
        }

        if ($deleteKeys) {
            $this->newQuery()
                ->whereIn($this->keyColumn, $deleteKeys)
                ->delete();
        }
    }

    /**
     * Transforms settings data into an array ready to be insterted into the
     * database. Call Arr::dot on a multidimensional array before passing it
     * into this method!
     *
     * @param  array $data Call Arr::dot on a multidimensional array before passing it into this method!
     *
     * @return array
     */
    protected function prepareInsertData(array $data)
    {
        $dbData = array();

        $freshTimestamp = $this->currentTimestamp();
        $timestamps = array(
            $this->createdAtColumn => $freshTimestamp,
            $this->updatedAtColumn => $freshTimestamp);

        foreach ($data as $key => $value) {
            $dbData[] = array_merge(
                $this->extraColumns,
                array(
                    $this->keyColumn => $key,
                    $this->valueColumn => $value),
                $timestamps);
        }

        return $dbData;
    }
}
