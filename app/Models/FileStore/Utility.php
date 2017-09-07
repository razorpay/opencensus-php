<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Utility extends Base\Core
{
    /**
     * Call Given File Operation and tarce if it fails/errors out
     *
     * @param string $method File Operation
     * @param array  $params Array of Params to be passed to File Operation fn call
     *
     * @throws \Exception
     */
    public function callFileOperation(string $method, array $params)
    {
        $result = true;

        $exception = null;

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

            throw $exception;
        }
        finally
        {
            if ($result === false)
            {
                $params['method'] = $method;

                $this->trace->traceException(
                    $exception,
                    Trace::ERROR,
                    TraceCode::FILE_OPERATION_FAILED,
                    $params
                );
            }

            umask($oldMask);
        }
    }
}
