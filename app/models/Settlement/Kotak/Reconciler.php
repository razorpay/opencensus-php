<?php

namespace Models\Settlement\Kotak;

class Reconciler
{
    public function __construct()
    {
        ;
    }

    public function process($input)
    {
        $file = $input['file'];

        $rows = fread($file);

        $headings = Settlement::$headings;
        $headings[] = 'Symbol';

        foreach ($rows as $row)
        {
            $data = explode('~', $row);

            $data = array_combine($headings, $data);
        }


    }
}