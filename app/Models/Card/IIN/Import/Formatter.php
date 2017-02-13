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

    public static $networkMapping = array(
        'JCB'                       => Network::JCB,
        'MASTERCARD'                => Network::MC,
        'MasterCard'                => Network::MC,
        'RuPay'                     => Network::RUPAY,
        'RUPAY'                     => Network::RUPAY,
        'CHINA UNION PAY'           => Network::UNP,
        'Maestro'                   => Network::MAES,
        'MAESTRO'                   => Network::MAES,
        'Visa'                      => Network::VISA,
        'VISA'                      => Network::VISA,
        'DISCOVER'                  => Network::DISC,
        'AMERICAN EXPRESS'          => Network::AMEX,
        'DINERS CLUB INTERNATIONAL' => Network::DICL,
        'unknown'                   => Network::UNKNOWN,
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

    public function formatIinDataRange($input, $range)
    {
        $data = [];

        unset($input['range']);

        foreach ($input as $key => $value)
        {
            $value = trim($value);

            switch (strtolower($key))
            {
                case 'type':
                    $data[IIN::COUNTRY] = self::$countryMap[$value];
                    $data[IIN::TYPE] = self::$cardTypeMap[$value];
                    break;

                case 'issuer':
                    if (IFSC::exists($value) === true)
                    {
                        $data[IIN::ISSUER] = $value;
                        $data[IIN::ISSUER_NAME] = Name::getName($value);
                    }
                    break;

                case 'network':
                    $data[IIN::NETWORK] = $this->formatNetwork($value, self::$networkMapping);
                    break;
            }
        }

        $iins = new BaseModel\PublicCollection;

        for ($i = $range['min']; $i <= $range['max']; $i++)
        {
            $data[IIN::IIN] = $i;

            $iins[$i] = $data;
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
