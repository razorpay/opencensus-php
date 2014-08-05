<?php
use Zizaco\TestCases\IntegrationTestCase;

class ModifiedZizacoIntegrationTestCase extends IntegrationTestCase
{   
   protected static function launchServer()
    {
        if(IntegrationTestCase::$serverLaunched)
            return;

        $command = "php artisan serve --env=testing --port 4443";
        static::execAsyncAndWaitFor($command, 'development server started');

        IntegrationTestCase::$serverLaunched = true;
    }

    protected static function execAsync($command, $output_path = '/dev/null')
    {
        $force_async = " > $output_path 2>&1 &";
        exec($command.$force_async);
    }

    protected static function execAsyncAndWaitFor($command, $content, $timeout = 30)
    {
        $output_path = "/tmp/zizaco-".str_shuffle(MD5(microtime()));
        self::execAsync($command, $output_path);
        self::waitForOutput($output_path, $content, $timeout);
    }
    
    protected static function waitForOutput($file, $output) {
        $found = FALSE;
        $max_tries = 30;
        $num_tries = 0;
        while ( !$found ) {
            $contents = file_get_contents($file);
            // var_dump($contents);
            if ( strstr($contents, $output) ) {
                $found = TRUE;
            } else {
                if ( ++$num_tries > $max_tries ) {
                    throw new \Exception("Failed to find $output in $file");
                }
                sleep(1);
            }
        }
    }

    protected static function killProcessByPort($port)
    {
        $processInfo = exec("lsof -i :$port");
        preg_match('/^\S+\s*(\d+)/', $processInfo, $matches);

        if(isset($matches[1]))
        {
            $pid = $matches[1];
            exec("kill $pid");
        }
    }
}