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
            Card\Detail::BANK);

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
        $records = array();
        $iin_record = array();

        if (file_exists($path) and is_readable($path))
        {
            $file_handle = fopen($path, 'r');

            while(($iin_record = fgetcsv($file_handle)) !== FALSE)
            {
                array_push($records, $iin_record);
            }

            fclose($file_handle);
        }

        return $records;
    }
}