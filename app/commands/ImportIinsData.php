<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Models\Card;
use Constants\Table;

class ImportIinsData extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rzp:importiins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import IINs data from a excel or csv into database';

    private $mapping = array(
        Card\Detail::IIN => array('BIN', 'IIN'),
        Card\Detail::CATEGORY => array('CARD_BRAND'),
        Card\Detail::NETWORK => array('NETWORK'),
        Card\Detail::TYPE => array('TYPE'),
        Card\Detail::COUNTRY => array('TYPE'),
        Card\Detail::ISSUER => array('TYPE'));

    private $typeMap = array(
        'FC' => 'credit',
        'DC' => 'credit',
        'FD' => 'debit',
        'DD' => 'debit');

    private $countryMap = array(
        'DC' => 'IN',
        'DD' => 'IN',
        'FD' => NULL,
        'FC' => NULL);

    private $columns = array(
        Card\Detail::IIN,
        Card\Detail::CATEGORY,
        Card\Detail::NETWORK,
        Card\Detail::TYPE,
        Card\Detail::COUNTRY,
        Card\Detail::ISSUER,
        Card\Detail::INTERNATIONAL);

    // Mapping of string to be searched to
    // the original name that should be in database
    private $networkType = array( 'VISA' => 'Visa', 'MASTER' => 'MasterCard');

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $dir = $this->argument('dir');
        $flist = scandir($dir);

        $indexed = array();
        foreach ($flist as $file)
        {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'xls')
            {
                echo "Parsing file {$file}\n";
                $parsed = $this->parse($dir . DIRECTORY_SEPARATOR . $file);

                // Takes the parsed data and modifies it to fit our structure
                $data = $this->structure($parsed, $file);

                // Indexeing the data based on IIN number
                foreach($data as $d)
                {

                    $indexed[$d[0]][] = $d;

                }

            }
        }
        $records = $this->removeDuplicate($indexed);

        $assocRecords = array();
        foreach ($records as $index => $record)
        {
            $record = array_combine($this->columns, $record);
            $assocRecords[] = $record;
        }

        $table = DB::table(Table::IIN);

        // Some old records clash
        $table->delete();
        for ($i = 0; $i < count($assocRecords); $i += 3000)
        {
            echo "Inserting entries from $i to " . ($i + 3000) . "\n";
            $table->insert(array_slice($assocRecords, $i, 3000));
        }

    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getArguments()
    {
        $array = array();

        array_push($array, ['dir', InputArgument::REQUIRED, 'Directory from which files are to be imported', null]);
        return $array;

    }

    /**
     * Tries to remove duplicate entires
     * Does nothing much, just nullifies the conflicting fields.
     * If there is mismatch in credit or debit type, it prompts
     * the user.
     * The input array is an array containing the rows grouped
     * by iin number, i.e. all the entries for each IIN is under
     * same key/index
     *
     * Returns a 2-D array, each index containing a single entry
     *
     * @param array $indexed
     *
     * @return array
     */
    protected function removeDuplicate($indexed)
    {
        return array_map(function ($row)
        {
            $len = count($row);
            if ($len === 1)
                return $row[0];

            $typeSet = false;
            $categorySet = false;

            // Comparing the first row with the rest
            for ($i = 1; $i < $len; $i++)
            {
                //var_dump($row);
                if(array_diff($row[$i], $row[0]))
                {
                    $intersect = array_intersect_assoc($row[0], $row[$i]);
                    if(!$categorySet && !array_key_exists(1, $intersect))
                    {
                        $row[0][1] = NULL;
                        $categorySet = true;
                    }
                    if(!$typeSet && !array_key_exists(3, $intersect))
                    {
                        echo "Choose between debit and credit for \n";
                        echo "BIN {$row[0][0]}\n";
                        echo "NETWORK {$row[0][2]}\n";
                        echo "Nationality " . (($row[0][6] === false)?"International":"IN") . "\n";
                        echo "Enter 1 for debit\n2 for credit\n";

                        $choice = readline();
                        if ($choice === '1')
                        {
                            $row[0][3] = 'debit';
                        }
                        else
                        {
                            $row[0][3] = 'credit';
                        }
                        $typeSet = true;
                    }
                }
            }
            return $row[0];
        }, $indexed);

    }

    /**
     * Converts the parsed data into our requierd format
     * It returns an array of arrays of values at proppper index
     *
     * @param array $parsed
     * @param string $filename
     *
     * @return array
     */
    protected function structure($parsed, $filename)
    {
        $columns = $parsed['columns'];
        $map = array();

        // Mapping IIN
        $ret = $this->getFromMapping(Card\Detail::IIN, $columns);
        $map[Card\Detail::IIN] = $ret;

        // Mapping Category
        $ret = $this->getFromMapping(Card\Detail::CATEGORY, $columns);
        $map[Card\Detail::CATEGORY] = $ret;

        // Card Network
        $network = NULL;
        foreach ($this->networkType as $nt => $origName)
        {
            if (stripos($filename, $nt) !== false)
            {
                $network = $origName;
            }
        }
        if ($network == NULL)
        {
            $network = readline ("Enter the network type ");
        }
        $map[Card\Detail::NETWORK] = $network;

        // Card Type
        // A function which will parse the given type to the required type
        $typeIndex = $this->getFromMapping(Card\Detail::TYPE, $columns);
        $map[Card\Detail::TYPE] = $typeIndex;

        //  Country
        // A function which will parse the given type to the required type
        $countryIndex = $this->getFromMapping(Card\Detail::COUNTRY, $columns);
        $map[Card\Detail::COUNTRY] = $countryIndex;

        $data = array_map(function ($row) use ($map)
        {
            $iin = $row[$map[Card\Detail::IIN]];
            $category = $row[$map[Card\Detail::CATEGORY]];
            $network = $map[Card\Detail::NETWORK];
            $type = $this->typeMap[$row[$map[Card\Detail::TYPE]]];
            $country = $this->countryMap[$row[$map[Card\Detail::COUNTRY]]];

            $issuer = NULL;

            $international = !$country;

            return [$iin, $category, $network, $type, $country, $issuer, $international];

        }, $parsed['data']);

        return $data;

    }

    /**
     * checks if there is a known mapping between the key and
     * column name
     *
     * @param string $key
     * @param array $columns
     *
     * @return int
     */
    private function getFromMapping ($key, $columns)
    {
        foreach ($this->mapping[$key] as $option)
        {
            for ($in = 0; $in < count($columns); $in++)
            {
                if (strtoupper($columns[$in]) === $option)
                {
                    return $in;
                }
            }
        }

        return false;
    }

    /**
     * Parses a xls or csv file and returns the parsed data in an array.
     * The return is an associative array having, columns and data.
     * columns is an array containg the columns name parsed from the file.
     * data is a 2D array containg the rows
     *
     * @param string
     *
     * @return array
     */
    protected function parse($inputFileName)
    {
        $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);

        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $columnNames = array();

        // Skipping the the rows that contain atleast one null column
        // They are mostly page/file title
        for ($row = 1; $row <= $highestRow; $row++)
        {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];

            for($i = 0; $i < count($rowData); $i++)
            {
                if($rowData[$i] == NULL )
                {
                    continue 2;
                }
            }
            break;
        }

        $columnNames = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];

        // Skipping if the following row contains all colums null
        for ($row++; $row <= $highestRow; $row++)
        {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];

            for($i = 1; $i < count($rowData); $i++)
            {
                if($rowData[$i] != NULL )
                    break 2;
            }
        }

        $data = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $highestRow, NULL, TRUE, FALSE);

        return ['columns' => $columnNames, 'data' => $data];
    }
}
