<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use RZP\Exception;

trait ExceptionTrait
{
    /**
     * Any runtime exception with the test case is thrown from here
     *
     * @param string $message
     * @param array $data
     * @throws Exception\RuntimeException
     */
    protected function throwTestingException(string $message, array $data)
    {
        $string = $message . PHP_EOL;

        foreach ($data as $key => $value)
        {
            $string .= $key . ':' . json_encode($value);
        }

        throw new Exception\RuntimeException($string);
    }

    /**
     * Any assertion exception in the test case is thrown from here
     *
     * @param string $message
     * @param array $data
     * @throws Exception\AssertionException
     */
    protected function throwAssertionException(string $message, array $data)
    {
        $string = $message . PHP_EOL;

        foreach ($data as $key => $value)
        {
            $string .= $key . ':' . json_encode($value);
        }

        throw new Exception\AssertionException($string);
    }
}
