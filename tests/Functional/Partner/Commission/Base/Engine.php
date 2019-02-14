<?php

namespace RZP\Tests\Functional\Partner\Commission\Base;

use Functional\Partner\Commission\Action;
use Functional\Partner\Commission\Assertions;

class Engine
{
    private $context;

    private $setup;

    private $action;

    private $assertions;

    public function __construct($fixtures)
    {
        $this->loadContext(__DIR__ . '/../Context.php');

        $this->loadSetup(__DIR__ . '/../Setup.php', $fixtures);

        $this->loadAction(__DIR__ . '/../Action.php');

        $this->loadAssertions(__DIR__ . '/../Assertions.php');
    }

    protected function loadContext($path)
    {
        $contextData = require $path;

        $this->setContext($contextData);
    }

    protected function loadSetup(string $path, $fixtures)
    {
        require $path;

        $this->setup = new Setup($fixtures);
    }

    protected function loadAction($path)
    {
        require $path;

        $this->action = new Action;
    }

    protected function loadAssertions($path)
    {
        require $path;

        $this->assertions = new Assertions;
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

        $this->action->$contextName($testContext['post_setup'], $testContext['post_action']);

        $this->assertions->$contextName($testContext);
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
