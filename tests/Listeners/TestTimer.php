<?php

namespace RZP\Tests\Listeners;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\AssertionFailedError;

class TestTimer implements TestListener
{
    public function startTest(Test $test)
    {
    }

    public function endTest(Test $test, $time)
    {
        $time = round($time, 5);

        echo "\nTook: $time seconds\n";
    }

    public function addWarning(Test $test, Warning $e, $time)
    {
    }

    public function addRiskyTest(Test $test, \Exception $e, $time)
    {
    }

    public function addError(Test $test, \Exception $e, $time)
    {
    }

    public function addFailure(Test $test, AssertionFailedError $e, $time)
    {
    }

    public function addIncompleteTest(Test $test, \Exception $e, $time)
    {
    }

    public function addSkippedTest(Test $test, \Exception $e, $time)
    {
    }

    public function startTestSuite(TestSuite $suite)
    {
    }

    public function endTestSuite(TestSuite $suite)
    {
    }
}
