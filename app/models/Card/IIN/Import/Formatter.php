<?php

namespace Models\Card\IIN\Import;

use Models\Card;
use EE\Exception;

/**
 * This class takes the column names, rows and the input and tries to
 * format the data as best as possible.
 *
 * Formatted data: Each input row is an associative array with keys
 * from Card\Detail.
 *
 * <h3>The configuration that is used to parse the rows.</h3>
 *
 * The formatting is done based on the value of
 * `$this->mapping` array. The columns(from `$this->columns`)
 * that are not present are set to null.
 *
 * The required key in each mapping is `level`.
 *
 * Each mapping has 3 levels.
 *
 * Level 0: Constant value for all rows.
 *          This value of key `value` is used.
 *          The required key is `value`.
 *
 * Level 1: The data in the row under `columnName` is used directly.
 *          The required key is `columnName`.
 *
 * Level 2: The data in row under `columnName` is to be mapped again.
 *          This second level of mapping is done using `map`.
 *          The required keys are `columnName` and `map`.
 */
class Formatter
{
    private $mapping = [
        Card\Detail::IIN => [
            'level' => 1,
            'columnName' => 'BIN',
        ],
        Card\Detail::CATEGORY => [
            'level' => 1,
            'columnName' => 'CARD_BRAND'
        ],
        Card\Detail::NETWORK => [
            'level' => 1,
            'columnName' => 'NETWORK',
        ],
        Card\Detail::TYPE => [
            'level' => 2,
            'columnName' => 'TYPE',
            'map' => [
                'FC' => 'credit',
                'DC' => 'credit',
                'FD' => 'debit',
                'DD' => 'debit'
            ],
        ],
        Card\Detail::COUNTRY => [
            'level' => 2,
            'columnName' => 'TYPE',
            'map' => [
                'DC' => 'IN',
                'DD' => 'IN',
                'FD' => NULL,
                'FC' => NULL
            ],
        ],
    ];

    private $columns = array(
        Card\Detail::IIN,
        Card\Detail::CATEGORY,
        Card\Detail::NETWORK,
        Card\Detail::TYPE,
        Card\Detail::COUNTRY,
        Card\Detail::ISSUER);

    // Mapping of string to be searched to
    // the original name that should be in database
    private $networkType = array( 'VISA' => 'Visa', 'MASTER' => 'MasterCard');

    /**
     * This function is called by the importer class with the reqired
     * parameters.
     *
     * This returns the structured data. Each row is converted
     * into associative array with keys from Card\Detail.
     *
     * @param array $input      the post data received
     * @param array $columns    the title of each column
     * @param array $data       the rows
     *
     * @return array The structured data.
     */
    public function formatData($input, $columns, $data)
    {
        $this->setNetworkType($input, $columns); // Should be removed after
                            // shifting completly to mapping from `$input`

        $this->setMapping($input);
        $structured = $this->structureData($columns, $data);

        return $structured;

    }

    public function setMapping($input)
    {
        if(isset($input['mapping']))
        {
            $this->mapping = $input['mapping'];
        }
        else
        {
            // throw new new Exception\BadRequestException('Input mapping not set');

            // using the default mapping, this requires the network mapping to
            // set manually. Remove that when we need that anymore.
        }
    }

    /**
     * Determines the network type from the user input network, or from the file
     * name.
     *
     * It sets the $network variable.
     *
     * @param array $input the post data
     */
    protected function setNetworkType($input, $columns)
    {
        if (array_search($this->mapping[Card\Detail::NETWORK]['columnName'], $columns))
        {
            return;
        }
        if (isset($input['network']) and ($input['network'] !== ""))
        {
            $this->mapping[Card\Detail::NETWORK]['level'] = 0;
            $this->mapping[Card\Detail::NETWORK]['value'] = $input['network'];
            return;
        }

        if (isset($input['file']))
        {
            $filename = $input['file']->getClientOriginalName();
            foreach ($this->networkType as $networkType => $origName)
            {
                if (stripos($filename, $networkType) !== false)
                {
                    $this->mapping[Card\Detail::NETWORK]['level'] = 0;
                    $this->mapping[Card\Detail::NETWORK]['value'] = $origName;
                    return;
                }
            }
        }

        throw new Exception\BadRequestException("Failed to determine the network type.");
    }

    /**
     * Returns the mapping for the given column.
     * It also remaps the columnName from input file to column index.
     *
     * @param string $column  The column for which we need the mapping
     * @param array $fileColumns The column names form the file.
     *
     * @return array          The map as specified in the class documentation.
     */
    private function getMapping($column, $fileColumns)
    {
        if (isset($this->mapping[$column]))
        {
            $mapping = $this->mapping[$column];
            if (($mapping['level'] > 0) and !(isset($mapping['column'])))
            {
                $mapping['column'] = array_search(
                    $mapping['columnName'],
                    $fileColumns);
            }

            return $mapping;
        }
        return ['level' => 0, 'value' => null];
    }

    /**
     * This does the main work. It takes the column names and the rows and
     * tries to structure the data.
     *
     * @param array $columns     the column names.
     * @param array $data        the rows.
     *
     * @return array            the rows after structuring.
     */
    protected function structureData($columns, $rows)
    {
        $map = array();

        foreach ($this->columns as $column)
        {
            $map[$column] = $this->getMapping($column, $columns);
        }

        $data = array_map(function ($row) use ($map)
        {
            $record = array();

            foreach ($this->columns as $column)
            {
                switch ($map[$column]['level'])
                {
                    case 0:
                        $value = $map[$column]['value'];
                        break;
                    case 1:
                        $col = $map[$column]['column'];
                        $value = $row[$col];
                        break;
                    case 2:
                        $col = $map[$column]['column'];
                        $intermediate = $row[$col];
                        $value = $map[$column]['map'][$intermediate];
                        break;
                    default:
                        throw new Exception\ServerErrorException(
                            "Failed to determine the network type.");
                }

                $record[$column] = $value;
            }
            return $record;
        }, $rows);

        return $data;

    }
}
