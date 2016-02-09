<?php

namespace Models\Card\IIN\Import;

use Models\Card\IIN\Entity as IIN;
use Models\Base;
use EE\Exception;

/**
 * This class takes the column names, rows and the input and formats
 * the data.
 *
 * Formatted data: Each input row is an associative array with keys
 * from Card\IIN\Entity.
 */
class Formatter
{
    public static $cardTypeMap = array(
        'FC'    =>  'credit',
        'DC'    =>  'credit',
        'FD'    =>  'debit',
        'DD'    =>  'debit'
    );

    public static $countryMap = array(
        'DC'    =>  'IN',
        'DD'    =>  'IN',
        'FD'    =>  NULL,
        'FC'    =>  NULL
    );

    /**
     * formats the data to iin entity
     *
     * @param array $columns    the title of each column
     * @param array $data       the rows
     *
     * @return collection of arrays with keys from iin entity.
     */
    public function formatData($columns, $data)
    {
        $iins = new Base\PublicCollection;

        foreach ($data as $row)
        {
            $input = array();
            $index = 0;

            foreach($columns as $column)
            {
                switch (strtolower($column))
                {
                    case 'type':
                        $input[IIN::COUNTRY] = self::$countryMap[$row[$index]];
                        $input[IIN::TYPE] = self::$cardTypeMap[$row[$index]];
                        break;

                    case 'bin':
                        $input[IIN::IIN] = $row[$index];
                        break;

                    case 'card_brand':
                        // $input[IIN::CATEGORY] = $row[$index];
                        break;

                    default:
                        //ignore extra columns
                        break;
                }

                $index++;
            }
            $iins[] = $input;
        }

        return $iins;
    }
}
