<?php

namespace RZP\Models\FileStore;

class Utility
{
    public static function call_file_operation($method, $params)
    {
        //
        // umask can vary on system level, need to reset for doing file operations
        // and after file opertaion are done, restore umask value to default
        //
        $oldmask = umask(0);

        try
        {
            $result = call_user_func_array($method, $params);

            if ($result === false)
            {
                $params['method'] = $method;

                $this->trace->warning(
                    TraceCode::FILE_OPERATION_FAILED,
                    $params
                );
            }
        }
        catch (\Exception $e)
        {
            throw $e;
        }
        finally
        {
            umask($oldmask);
        }
    }
}
