<?php

namespace RZP\Models\Gateway\File\Processor;

trait FileHandler
{
    protected function getInitialLine(string $glue = '|')
    {
        $data = static::HEADERS;

        $line = implode($glue, $data) . "\r\n";

        return $line;
    }

    protected function getTextData($data, $prependLine = '', string $glue = '|')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, $glue, $ignoreLastNewline);

        return $prependLine . $txt;
    }

    protected function generateText($data, $glue = '|', $ignoreLastNewline = false)
    {
        $txt = '';

        $count = count($data);

        foreach ($data as $row)
        {
            $txt .= implode($glue, array_values($row));

            $count--;

           if (($ignoreLastNewline === false) or
               (($ignoreLastNewline === true) and ($count > 0)))
           {
                $txt .= "\r\n";
           }
        }

        return $txt;
    }
}
