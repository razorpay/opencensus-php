<?php

namespace RZP\Http\Controllers;

use Response;
use RZP\Trace\TraceCode;
use Illuminate\Http\Request;

class DisputesProxyController extends EdgeProxyController
{
    const CONTENT_TYPE_MULTIPART = 'multipart/form-data';

    // edge proxy controller is not working for multipart data due to php bug https://laracasts.com/discuss/channels/laravel/get-request-request-getcontent-is-empty
    // preparing payload for multipart proxy requests
    protected function getContent($contentType, Request $request)
    {

        if(str_contains($contentType, self::CONTENT_TYPE_MULTIPART) !== true)
        {
            return parent::getContent($contentType, $request);
        }

        $data = $request->all();

        $contentHeader = explode('boundary=', $contentType);

        $mime_boundary = $contentHeader[1];

        return $this->getMultipartData($data, $mime_boundary);
    }

    private function getMultipartData($content, $mime_boundary): string
    {
        $eol = "\r\n";

        $data = '';

        foreach ($content as $key => $value){
            if (is_file($value) === true)
            {
                $data .= $this->addFileInData($data, $key, $value, $mime_boundary, $eol);
            }
            else
            {
               $data .= $this->addKeyValueInData($data, $key, $value, $mime_boundary, $eol);
            }
        }

        $data .= '--' . $mime_boundary . '--';

        return $data;
    }

    private function addFileInData($data, $key, $file, $mime_boundary, $eol)
    {

        $data .= "--" . $mime_boundary . $eol;

        $data .= 'Content-Disposition: form-data; name="' . $key . '"; filename="' . $file->getClientOriginalName() . '"' . $eol;

        $data .= 'Content-Transfer-Encoding: binary'.$eol.$eol;

        $data .= file_get_contents($file) . $eol;

        return $data;
    }

    private function addKeyValueInData($data, $Key, $value, $mime_boundary, $eol)
    {

        $data .= "--" . $mime_boundary . $eol;

        $data .= 'Content-Disposition: form-data; name="' . $Key . '"' . $eol . $eol;

        $data .= $value . $eol;

        return $data;
    }
}
