<?php

namespace Unit\Models\Partner\Commission;

class Engine
{
    private $context;

    public function __construct()
    {
        $this->loadContextData = __DIR__ . '/Context.php';

        $this->loadContext();
    }

    protected function loadContext()
    {
        $contextData = require($this->loadContextData);

        $this->context = $contextData;
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
        // @todo isset check
        $testContext = $this->context[$contextName];

        $defaultContext = $this->getDefaultContext();

        array_merge($testContext, $defaultContext);

        $this->setupFixtures($testContext['setup']);

        $output = $this->runAction($testContext['action']);

        $testContext['output'] = $output;

        $this->executeRulesOnOutput($testContext);
    }

    public function getDefaultContext(): array
    {
        return [
            'setup'  => [],
            'action' => function () {},
            'output' => [],
            ''
        ];
    }
}
