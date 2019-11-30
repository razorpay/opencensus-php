<?php

namespace RZP\Models\Card\IIN\Import;

use RZP\Models\Card\IIN\Entity as IIN;
use RZP\Models\Base as BaseModel;
use RZP\Models\Card\Network;
use RZP\Models\Bank\Name;
use RZP\Models\Bank\IFSC;
use RZP\Exception;

/**
 * This class takes the column names, rows and the input and formats
 * the data.
 *
 * Formatted data: Each input row is an associative array with keys
 * from Card\IIN\Entity.
 */
class Formatter
{
    public $creditCard          = 0;
    public $debitCard           = 0;
    public $otherCardType       = 0;
    public $unknownNetworkType  = 0;

    public static $cardTypeMap = array(
        'FC'    => 'credit',
        'DC'    => 'credit',
        'FD'    => 'debit',
        'DD'    => 'debit'
    );

    public static $countryMap = array(
        'DC'    => 'IN',
        'DD'    => 'IN',
        'FD'    => null,
        'FC'    => null,
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
        $iins = new BaseModel\PublicCollection;

        foreach ($data as $row)
        {
            $input = array();
            $index = 0;

            foreach ($columns as $column)
            {
                $row[$index] = trim($row[$index]);

                switch (strtolower($column))
                {
                    case 'type':
                        $input[IIN::COUNTRY] = self::$countryMap[$row[$index]];
                        $input[IIN::TYPE] = self::$cardTypeMap[$row[$index]];
                        break;

                    case 'bin':
                        $input[IIN::IIN] = $row[$index];
                        break;

                    case 'issuer':
                        if (IFSC::exists($row[$index]) === true)
                        {
                            $input[IIN::ISSUER] = $row[$index];
                            $input[IIN::ISSUER_NAME] = Name::getName($row[$index]);
                        }
                        break;

                    case 'message_type':
                        if (\RZP\Models\Card\IIN\MessageType::isValid($row[$index]) === true)
                        {
                            $input[IIN::MESSAGE_TYPE] = $row[$index];
                        }
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

    public function formatIinDataRange($input)
    {
        $data = [];

        $iins = new BaseModel\PublicCollection;

        $min = $input['min'];
        $max = $input['max'];

        unset($input['min'], $input['max']);

        for ($i = $min; $i <= $max; $i++)
        {
            $iin = str_pad($i, 6, '0', STR_PAD_LEFT);

            $input[IIN::IIN] = $iin;

            $iins[$i] = $input;
        }

        return $iins;
    }

    /**
     * formats the data to iin entity
     *
     * @param array $columns    the title of each column
     * @param array $data       the rows
     *
     * @return collection of arrays with keys from iin entity.
     */
    public function formatDataNew($columns, $data, $networkMapping, $fieldToDel)
    {
        $iins = array();

        foreach ($data as $row)
        {
            $input = array_combine($columns, $row);

            foreach ($fieldToDel as $field)
            {
                unset($input[$field]);
            }

            $input[IIN::NETWORK] = $this->formatNetwork(
                $input[IIN::NETWORK],
                $networkMapping);

            $input[IIN::TYPE] = $this->formatType($input[IIN::TYPE]);
            $input = array_filter($input);

            $iins[$input[IIN::IIN]] = $input;
        }

        return $iins;
    }

    private function formatNetwork($value, $networkMapping)
    {
        if (array_key_exists($value, $networkMapping))
        {
            return Network::$fullName[$networkMapping[$value]];
        }

        $this->unknownNetworkType += 1;

        return Network::$fullName[$networkMapping['unknown']];
    }

    private function formatType($value)
    {
        $value = strtolower($value);

        if ($value === 'credit')
        {
            $this->creditCard += 1;
            return $value;
        }

        if ($value === 'debit')
        {
            $this->debitCard += 1;
            return $value;
        }

        $this->otherCardType += 1;

        return null;
    }

}
