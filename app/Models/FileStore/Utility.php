<?php

namespace RZP\Models\FileStore;

use App;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Utility
{
    public static function callFileOperation($method, $params)
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];
        //
        // umask can vary on system level, need to reset for doing file operations
        // and after file operation are done, restore umask value to default
        //
        $oldMask = umask(0);

        try
        {
            $result = call_user_func_array($method, $params);

            if ($result === false)
            {
                $params['method'] = $method;

                $trace->error(
                    TraceCode::FILE_OPERATION_FAILED,
                    $params
                );
            }
        }
        catch (\Exception $e)
        {
            $params['method'] = $method;

            $trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FILE_OPERATION_FAILED,
                $params
            );

            throw $e;
        }
        finally
        {
            umask($oldMask);
        }
    }
}
