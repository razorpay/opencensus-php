<?php

namespace RZP\Models\Card\IIN\Import;

use Excel;
use RZP\Exception;
use RZP\Excel\Export;

/**
 * This class generates the testing xml file from the input array.
 * This is used for testing purpose.
 */
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


    /**
     * The function that is called by the Service class.
     *
     * The input array should contain data key with a 2D array value.
     *
     * @param array $input the user post data.
     *
     * @return string the path to generated file
     */
    public function generate($input)
    {
        $filePath = $this->createExcelFile($input);

        return $filePath;
    }

    /**
     * This function generates the xls file.
     *
     * The input array should contain data key with a 2D array value.
     * @param array $input the user post data.
     *
     * @return string the path to generated file
     */
    protected function createExcelFile($input)
    {
        $data = $input['data'];
        $filePath =   storage_path('exports') . '/IINTest.xls';

        (new Export($data, [], array('Sheet1')))->generateAutoHeading(true)->store($filePath, 'local_storage');

        return $filePath;
    }
}
