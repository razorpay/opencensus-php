<?php

namespace Models\Card\IIN\Import;

use Excel;

class IinGenerator
{
    public function _construct()
    {
        $this->mode = \App::getFacadeRoot()['rzp.mode'];

        if ($this->mode !== 'test')
        {
            throw new Exception\LogicException('Only test mode allowed');
        }
    }


    public function generate($input)
    {
        $filePath = $this->createExcelFile($input);

        return $filePath;
    }

    public function createExcelFile($input)
    {
        $data = $input['data'];
        $file = Excel::create('IINTest', function($excel) use($data) {

            $excel->sheet('Sheet1', function($sheet) use($data) {
                $sheet->fromArray($data);
            });
        })->store('xls', false, true);

        return $file['full'];
    }
}
