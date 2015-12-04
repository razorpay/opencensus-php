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
 * This maps the data in the Excel/CSV file to database columns.
 *
 * The map has to be submitted with the request as an array or as a JSON. If the map is not submitted, the
 * default mapping (which is only able to parse the bin files that was used for
 * testing and will definitely break for other file) will be used.
 *
 * The map is an array having the column names (from database) as keys. Each
 * column specifies a `level`.
 *
 * <h5> There are 3 _levels_ for mapping</h5>
 * - *level 0*: Constant value for all rows. This supplied `value` is used.
 *              The required key is `value`.
 *
 * - *level 1*: The data in the row under `columnName` is used directly.
 *              The `columnName` is taken from the Excel file.
 *              The required key is `columnName`.
 *
 * - *level 2*: The data in row under `columnName` is to be mapped again.
 *              This _second level_ of mapping is done using `map`.
 *              The required keys are `columnName` and `map`.
 *
 * <h3>A sample mapping</h3>
 *
 * <code>
 * 'mapping' => [
 *     'iin' => [
 *         'level' => 1,
 *         'columnName' => 'BIN',          // Direct value from the column will be used
 *     ],
 *     'category' => [
 *         'level' => 1,
 *         'columnName' => 'CARD_BRAND'
 *     ],
 *     'network' => [
 *         'level' => 0,
 *         'value' => 'MasterCard',        // network will be MasterCard used for all rows
 *     ],
 *     'type' => [
 *         'level' => 2,
 *         'columnName' => 'TYPE',   // The value in the field TYPE will be looked up in the supplied map
 *         'map' => [
 *             'FC' => 'credit',
 *             'DC' => 'credit',
 *             'FD' => 'debit',
 *             'DD' => 'debit'
 *         ],
 *     ],
 *     'country' => [
 *         'level' => 2,
 *         'columnName' => 'TYPE',
 *         'map' => [
 *             'DC' => 'IN',
 *             'DD' => 'IN',
 *             'FD' => NULL,
 *             'FC' => NULL
 *         ],
 *     ],
 * ];
 * </code>
 */
class Formatter
{
    private $mapping = null;

    private $columns = array(
        Card\Detail::IIN,
        Card\Detail::CATEGORY,
        Card\Detail::NETWORK,
        Card\Detail::TYPE,
        Card\Detail::COUNTRY,
        Card\Detail::ISSUER,
        Card\Detail::TRIVIA,
    );

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
        $this->setMapping($input);
        $structured = $this->structureData($columns, $data);

        return $structured;

    }

    public function setMapping($input)
    {
        if(isset($input['mapping']))
        {
            if(gettype($input['mapping']) === 'array')
            {
                $this->mapping = $input['mapping'];
            }
            else
            {
                $this->mapping = json_decode($input['mapping'], true);
            }

        }
        else
        {
            throw new Exception\BadRequestException('Input mapping not set');
        }
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
