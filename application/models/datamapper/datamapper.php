<?php

namespace DataMapper;

abstract class DataMapper
{

    private static $attr_insert = array();

    private static $attr_required = array();

    private static $attr_update = array();

    abstract private static $attr_db;

    public static function get_attr_db()
    {
        return self::$attr_db;
    }

    public static function array_prefix_table_name()
    {
        return Utility::array_prefix(static::$table.'.');
    }

    

    public static function array_attr_alias($input, $alias_prefix)
    {
        $array_prefix_table_name = self::array_prefix_table_name();

        $array_prefix_alias = Utility::array_prefix($input, $prefix);

        $array_alias = Utility::array_join_conjunction($array_prefix_table_name, $array_prefix_alias, ' AS ');

        return $array_alias;
    }

    public static function table_cols_aliasing_default()
    {
        return self::array_attr_alias(self::$attr_db, self::table.'_');
    }

    public static function bulk_load($dataset, $keys, $class_do, $prefix)
    {
        $class_do = 'Class'.$class_do;

        if ($prefix !== null)
        {
            $result = Utility::array_column_prefix($dataset, $prefix);

            $do_arr = array();

            foreach ($result as $row)
            {
                $record = array_combine(self::$keys, $row);
                
                $do = new $class_do();

                $do->set($record);

                array_push($do_arr, $do);
            }

        }
        else
        {
            $do_arr = array();

            foreach ($dataset as $row)
            {
                $do = new $class_do();

                $do->set($row);

                array_push($do_arr, $do);
            }
        }
    }
}