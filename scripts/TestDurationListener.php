<?php

class TestDurationListener implements \PHPUnit\Runner\AfterTestHook
{
    /**
     * @var GuzzleHttp\Client
     */
    private $client;

    const URL_ENV_KEY       = 'API_UI_TEST_SUMO_COLLECTION_URL_TEMP';
    const COMMIT_ENV_KEY    = 'GIT_COMMIT_HASH';
    const SUITE_ENV_KEY     = 'SUITE_NAME';

    public function __construct()
    {
        $this->client = new GuzzleHttp\Client([
            "verify" => false
        ]);
    }

    public function executeAfterTest(string $test, float $time): void
    {
        $name = $test;
        $split = explode(" ", $name);
        $name = $split[0];
        $testCase = str_pad($name, 50);
        $timeTaken = number_format($time, 2);

        if (empty(env(self::COMMIT_ENV_KEY)) === true
            || empty(env(self::SUITE_ENV_KEY)) === true
            || empty(env(self::URL_ENV_KEY)) === true)
        {
            return;
        }

        $message = env(self::COMMIT_ENV_KEY) . ",". env(self::SUITE_ENV_KEY) . "," . $testCase. ",". $timeTaken;

        try {
            $this->client->post(env(self::URL_ENV_KEY), [
                "body" =>  $message
            ]);
        } catch (Exception $e) {
            print($e->getMessage() . "\n");
        }

        // printf($message. "\n");
    }
}
