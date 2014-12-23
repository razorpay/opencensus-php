<?php

namespace Models\Settlement\Mpr;

use Excel;
use EE\Exception;

class Parser
{
    protected static $headings = array();

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->mode = $app['rzp.mode'];
        $this->env = $app->environment();
    }

    public function process($input)
    {
        $this->checkInput($input);

        $mprFile = $input['attachment-1'];

        return $this->parseMprFile($mprFile);
    }

    protected function checkInput($input)
    {
        $recipient = 'hdfc_mpr_' . $this->env . '_' . $this->mode . '@mg.razorpay.com';

        if ((isset($input['recipient']) === false) or
            ($input['recipient'] !== $recipient))
        {
            $recipient = (isset($input['recipient'])) ? $input['recipient'] : 'unset';

            $msg = 'Email recipient does not match with env and mode. ' .
                   'Env: '. $this->env . ' Mode: ' . $this->mode .
                   'Recipient: ' . $recipient;

            throw new Exception\LogicException($msg);
        }

        if (isset($input['attachment-count']) === false)
        {
            throw new Exception\LogicException('attachment-count not set');
        }

        $count = $input['attachment-count'];
        if ($count !== '1')
        {
            throw new Exception\LogicException(
                'attachment-count should be exactly one. Count: ' . $count);
        }

        if (isset($input['attachment-1']) === false)
        {
            throw new Exception\LogicException('attachment-1 not provided. Fishy!');
        }
    }

    public function parseMprFile($mprFile)
    {
        $data = $this->getDataFromMprFile($mprFile);

        return $this->parseMprFileDataIntoAssocArray($data);
    }

    protected function getDataFromMprFile($mprFile)
    {
        $filePath = $mprFile->getRealPath();

        $data = Excel::load($filePath)
                      ->noHeading()
                      ->ignoreEmpty()
                      ->formatDates(false)
                      ->toArray();

        if ((count($data) === 3) and
            (count($data[1]) === 0))
        {
            //
            // For excel files, with 3 sheets, we get the
            // data for first sheet only, discarding other sheets.
            // The simple check to determine sheets is that they will 3
            // in number and data in second sheet should be empty.
            //
            $data = $data[0];
        }

        return $data;
    }

    protected function parseMprFileDataIntoAssocArray($data)
    {
        $assocArray = array();

        $headings = array_shift($data);

        // Change headings to camelcase values
        foreach($headings as &$attr)
        {
            $attr = strtolower($attr);
            $attr = str_replace(' ', '_', $attr);

            // if (in_array($this->headings, $attr) === false)
            // {
            //     throw new Exception\LogicException(
            //         'Hdfc mpr: heading mis-match. Value: ' . $attr);
            // }
        }

        $headingCount = count($headings);

        $txns = array();

        $r = range(1, $headingCount);

        foreach ($data as $row)
        {
            foreach($r as $i)
            {
                // Some keys may have corresponding blank columns
                // In such cases, excel does not provide a value for it.
                // So, we manually set those keys to 'null'
                if (isset($row[$i]) === false)
                {
                    $row = array_slice($row, 0, $i - 1, true) +
                           array($i => null) +
                           array_slice($row, $i - 1, null, true);
                }
            }


            array_push($assocArray, array_combine($headings, $row));
        }

        return $assocArray;
    }
}
