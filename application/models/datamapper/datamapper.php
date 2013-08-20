<?php

namespace DataMapper;

use \Utility;
use DomainObject\Card as CardDO;
use DomainObject\CardToken as CardTokenDO;
use DomainObject\Transaction as TransactionDO;

abstract class DataMapper
{

    private static $attr_insert = array();

    private static $attr_required = array();

    private static $attr_update = array();

//    private static $attr_db = array();

    public static function get_attr_db()
    {
        return static::$attr_db;
    }

    public static function array_prefix_table_name()
    {
        return Utility::array_prefix(static::$attr_db, static::table.'.');
    }

    

    public static function array_attr_alias($input, $alias_prefix)
    {
        $array_prefix_table_name = self::array_prefix_table_name();

        $array_prefix_alias = Utility::array_prefix($input, $alias_prefix);

        $array_alias = Utility::array_join_conjunction($array_prefix_table_name, $array_prefix_alias, ' AS ');

        return $array_alias;
    }

    public static function table_cols_aliasing_default()
    {
        return self::array_attr_alias(static::$attr_db, static::table.'_');
    }

    public static function bulk_load(array $dataset, $do, array $keys = null)
    {
        $count = array();

        $do_obj_arr = array();

        if (is_array($do))
        {
            $do_num = count($do);
            if ($do_num !== 0)
            {
                if ($keys === null) 
                {
                    throw new \InvalidArgumentException('$keys cannot be null for initializing multiple DOs');
                }
            }
            else throw new \InvalidArgumentException('$class_do cannot be empty array');

            $do_class = array_combine($do, Utility::array_prefix($do, 'DomainObject\\'));

            foreach ($do as $d)
            {
                $count[$d] = count($keys[$d]);

                $do_obj_arr[$d] = array();
            }

            foreach ($dataset as $row)
            {
                $offset = 0;
                foreach ($do as $d)
                {
                    $vals = array_slice($row, $offset, $count[$d]);

                    $do_obj = new $do_class[$d];

                    $do_obj->set(array_combine($keys[$d], $vals));

                    $offset += $count[$d];

                    array_push($do_obj_arr[$d], $do_obj);
                }
            }
        }
        else if (is_string($do))
        {
            $do_class = 'Class'.$class_do;

            foreach ($dataset as $row)
            {
                $do_obj = new $do_class();

                $do_obj->set($row);

                array_push($do_obj_arr, $do_obj);
            }
        }
        else throw new \InvalidArgumentException('$class_do is not of proper format');

        return $do_obj_arr;
/*        if ($prefix !== null)
        {
            $result = Utility::array_column_prefix($dataset, $prefix);

            $do_arr = array();

            foreach ($result as $row)
            {
                $record = array_combine($keys, $row);
                
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
*/    
    }
}
