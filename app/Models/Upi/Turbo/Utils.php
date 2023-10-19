<?php

namespace RZP\Models\Upi\Turbo;

use App;
use RZP\Trace\TraceCode;

class Utils extends \RZP\Models\Base\Core
{
    public function getFileContentsAsArray($filePath)
    {
        $baseFilePath = base_path($filePath);

        $fileContents = file_get_contents($baseFilePath);

        $contentsArray = json_decode($fileContents, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            $this->trace->error(
                TraceCode::ERROR_MAPPING_JSON_DECODE_FAILED,
                [
                    'base_file_path' => $baseFilePath,
                    'json_last_error' => json_last_error(),
                ]
            );
            return [];
        }

        return $contentsArray;
    }
}
