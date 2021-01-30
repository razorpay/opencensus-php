<?php


namespace RZP\Http\Controllers\Processors;


class PostProcessor
{
    function process(string $route, array $input, array $output)
    {
        $method = 'handle'.$route;

        if(method_exists($this, $method))
        {
            return call_user_func_array([$this, $method], [$input, $output]);
        }
        return $output;
    }

}
