<?php

namespace Tests;

use Artisan;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Console\Kernel;
use PHPUnit_Extensions_Selenium2TestCase;

class TestCase extends PHPUnit_Extensions_Selenium2TestCase
{
    const ENV = 'testing';
    const SERVER_LAUNCH_COMMAND = "php artisan serve -vvv --env=testing --port 4443";
    const BROWSER_URL = 'http://localhost:4443';

    static $serverLaunched = false;

    protected static function launchServer()
    {
        if (TestCase::$serverLaunched)
            return;
        static::execAsyncAndWaitFor(self::SERVER_LAUNCH_COMMAND, 'development server started');

        TestCase::$serverLaunched = true;
    }

    protected static function execAsyncAndWaitFor($command, $content, $timeout = 30)
    {
        $output_path = "/tmp/rzp-dbd-".str_shuffle(md5(microtime()));
        self::execAsync($command, $output_path);
        self::waitForOutput($output_path, $content, $timeout);
    }

    protected static function execAsync($command, $output_path = '/dev/null')
    {
        $force_async = " > $output_path 2>&1 &";
        exec($command.$force_async);
    }

    protected static function waitForOutput($file, $output)
    {
        $found = false;
        $max_tries = 30;
        $num_tries = 0;
        while ( !$found )
        {
            $contents = file_get_contents($file);
            if ( strstr($contents, $output) )
            {
                $found = true;
            }
            else
            {
                if ( ++$num_tries > $max_tries )
                {
                    throw new Exception("Failed to find $output in $file");
                }
                sleep(1);
            }
        }
    }

    protected static function killProcessByPort($port)
    {
        $processInfo = exec("lsof -i :$port");
        preg_match('/^\S+\s*(\d+)/', $processInfo, $matches);

        if (isset($matches[1]))
        {
            $pid = $matches[1];
            exec("kill $pid");
        }
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::createApplication();
        static::launchServer();
    }

    protected function setUp() {
        $this->setBrowser('firefox');
        $this->setDesiredCapabilities([
            'chromeOptions' => [
                'args' => ['no-sandbox', 'no-gpu', 'start-maximized']
            ],
            'pageLoadingStrategy' => 'eager'
        ]);
        $this->shareSession(true);
        $this->setBrowserUrl(self::BROWSER_URL);
    }

    public static function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();
    }

    public function setUpPage()
    {
         $this->currentWindow()->maximize();
    }

    protected function takeScreenShot($location)
    {
        $fp = fopen($location,'wb');
        fwrite($fp,$this->currentScreenshot());
        fclose($fp);
    }
}
