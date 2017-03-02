<?php

namespace RZP\Models\FileStore;

use App;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Utility
{
    /**
     * Call Given File Operation and tarce if it fails/errors out
     *
     * @param string $method File Operation
     * @param array  $params Array of Params to be passed to File Operation fn call
     *
     * @return void
     */
    public static function callFileOperation(string $method, array $params)
    {
        $result = true;

        $exception = null;

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
        }
        catch (\Exception $exception)
        {
            $result = false;

            throw $e;
        }
        finally
        {
            if ($result === false)
            {
                $params['method'] = $method;

                $trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::FILE_OPERATION_FAILED,
                    $params
                );
            }

            umask($oldMask);
        }
    }
}
