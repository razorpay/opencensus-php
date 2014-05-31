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
        foreach ($records as $index => $record) {
            $iin = $record[0];
            $card_category = $record[1];
            $brand = $record[2];
            $card_type = $record[3];
            $country_code = $record[4];
            $bank = $record[5];

            $cardDetail = new CardDetail;
            $cardDetail->iin = $iin;
            $cardDetail->card_category = $card_category;
            $cardDetail->brand = $brand;
            $cardDetail->card_type = $card_type;
            $cardDetail->country_code = $country_code;
            $cardDetail->bank = $bank;

            $cardDetail->save();
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