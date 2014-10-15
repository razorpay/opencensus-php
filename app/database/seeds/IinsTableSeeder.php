<?php

use Models\Card;
use Constants\Table;

class IinsTableSeeder extends Seeder
{
    public function run()
    {
        // empty `iins` table
        DB::disableQueryLog();
        DB::table(Table::IIN)->delete();

        $records = self::getIinRecordsFromFile(storage_path().'/iins/iins.csv');

        $columns = array(
            Card\Detail::IIN,
            Card\Detail::CATEGORY,
            Card\Detail::BRAND,
            Card\Detail::TYPE,
            Card\Detail::COUNTRY,
            Card\Detail::ISSUER);

        $assocRecords = array();

        foreach ($records as $index => $record)
        {
            $record = array_combine($columns, $record);

            $assocRecords[] = $record;
        }

        DB::table(Table::IIN)->insert($assocRecords);
    }

    /**
     * Returns the records read from a file as an array
     *
     * @return array $records iin records
     */
    static private function getIinRecordsFromFile($path)
    {
        if (is_readable($path) === false)
        {
            throw new RuntimeException($path . ' file is either not found or not readable');
        }

        $fileHandle = fopen($path, 'r');

        $records = array();
        $iinRecord = array();

        while(($iinRecord = fgetcsv($fileHandle)) !== FALSE)
        {
            array_push($records, $iinRecord);
        }

        fclose($fileHandle);

        return $records;
    }
}