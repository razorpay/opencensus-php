<?php

use Models\DAL\CardDetail;

class IinsTableSeeder extends Seeder
{
    public function run()
    {
        // empty `iins` table
        DB::disableQueryLog();
        DB::table('iins')->delete();

        $records = self::getIinRecordsFromFile(storage_path().'/iins/iins.csv');
        $columns = array('iin', 'card_category', 'brand', 'card_type', 'country_code', 'bank');

        foreach ($records as $index => $record) {
            $record = array_combine($columns, $record);

            $cardDetail = CardDetail::create($record);
        }
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

        if(file_exists($path) and is_readable($path))
        {
            $file_handle = fopen($path, 'r');

            while(($iin_record = fgetcsv($file_handle)) !== FALSE) {
                array_push($records, $iin_record);
            }

            fclose($file_handle);
        }

        return $records;
    }
}