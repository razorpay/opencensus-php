<?php

namespace Models\Settlement\Mpr;

use Excel;
use EE\Exception;
use Carbon\Carbon;

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
        $filePath = $mprFile->getRealPath();

        $data = Excel::load($filePath)
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

        $this->moveFile($mprFile);

        return $data;
    }

    protected function moveFile($file)
    {
        $filename = basename($file, '.txt');

        $dir = storage_path('files/settlement/reconciled');

        if (file_exists($dir) === false)
        {
            mkdir($dir, 0777);
        }

        $time = Carbon::now('Asia/Kolkata')->format('H:i:s');

        $newName = $filename . '_' . $time . '.xlsx';

        $file->move($dir, $newName);
    }
}
