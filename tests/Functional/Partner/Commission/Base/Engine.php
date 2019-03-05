<?php

namespace RZP\Tests\Functional\Partner\Commission\Base;

use RZP\Tests\Functional\Partner\Commission\Action;
use RZP\Tests\Functional\Partner\Commission\Assertions;
use RZP\Tests\Functional\Partner\Commission\Base\Assertions as BaseAssertions;

class Engine
{
    private $context;

    private $setup;

    private $action;

    private $assertions;

    private $baseAssertions;

    public function __construct($fixtures)
    {
        $this->loadContext(__DIR__ . '/../Context.php');

        $this->loadSetup(__DIR__ . '/../Setup.php', $fixtures);

        $this->loadAction(__DIR__ . '/../Action.php');

        $this->loadAssertions(__DIR__ . '/../Assertions.php');

        $this->loadBaseAssertions(__DIR__ . '/Assertions.php');
    }

    protected function loadContext($path)
    {
        $contextData = include $path;

        $this->setContext($contextData);
    }

    protected function loadSetup(string $path, $fixtures)
    {
        include_once $path;

        $this->setup = new Setup($fixtures);
    }

    protected function loadAction($path)
    {
        include_once $path;

        $this->action = new Action;
    }

    protected function loadAssertions($path)
    {
        include_once $path;

        $this->assertions = new Assertions;
    }

    protected function loadBaseAssertions($path)
    {
        include_once $path;

        $this->baseAssertions = new BaseAssertions;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    public function execute(string $contextName)
    {
        if (isset($this->context[$contextName]) === false)
        {
            throw new \Exception('The context ' . $contextName . ' is missing from the Context.php file');
        }

        $testContext = $this->context[$contextName];

        $defaultContext = $this->getDefaultContext();

        $testContext = array_merge($defaultContext, $testContext);

        $this->setupFixtures($testContext['setup'], $testContext['post_setup']);

        $exception = null;

        try
        {
            $this->runAction($testContext, $contextName);
        }
        catch (\Throwable $ex)
        {
            $exception = $this->getExceptionData($ex);
        }
        finally
        {
            $testContext['post_action']['exception'] = $exception;
        }

        $this->baseAssertions->runExceptionAssertions($testContext);

        if (method_exists($this->assertions, $contextName) === true)
        {
            $this->assertions->$contextName($testContext);
        }

    }

    protected function setupFixtures(array $setupRequests, array & $output)
    {
        foreach($setupRequests as $setupRequest => $data)
        {
            $setupFunction = studly_case($setupRequest);

            $this->setup->$setupFunction($data, $output);
        }
    }

    protected function getDefaultContext(): array
    {
        return [
            'setup'       => [],
            'post_setup'  => [],
            'post_action' => [],
        ];
    }

    protected function runAction(array & $testContext, string $contextName)
    {
        if (method_exists($this->action, $contextName) === true)
        {
            $this->action->$contextName($testContext['post_setup'], $testContext['post_action']);
        }
        else
        {
            $this->action->defaultAction($testContext['post_setup'], $testContext['post_action']);
        }
    }


    protected function getExceptionData(\Throwable $ex): array
    {
        $data = [
            'code'    => $ex->getCode(),
            'trace'   => array_map(
                function ($trace)
                {
                    if (empty($trace['file']) === false)
                    {
                        // file:line_no
                        $formattedTrace = $trace['file'] . ':' . $trace['line'];
                    }
                    else
                    {
                        // class:function()
                        $formattedTrace = $trace['class'] . ':' . $trace['function'] . '()';
                    }

                    return $formattedTrace;
                },
                $ex->getTrace()),
            'message' => $ex->getMessage(),
            'class'   => get_class($ex),
        ];

        return $data;
    }
}
