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
            if (method_exists($this->action, $contextName) === true)
            {
                $this->action->$contextName($testContext['post_setup'], $testContext['post_action']);
            }
            else
            {
                $this->action->defaultAction($testContext['post_setup'], $testContext['post_action']);
            }
        }
        catch (\Throwable $ex)
        {
            $exception = $ex;
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

    public function setupFixtures(array $setupRequests, array & $output)
    {
        foreach($setupRequests as $setupRequest => $data)
        {
            $setupFunction = studly_case($setupRequest);

            $this->setup->$setupFunction($data, $output);
        }
    }

    public function getDefaultContext(): array
    {
        return [
            'setup'       => [],
            'post_setup'  => [],
            'post_action' => [],
        ];
    }
}
