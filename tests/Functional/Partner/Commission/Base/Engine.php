<?php

namespace RZP\Tests\Functional\Partner\Commission\Base;

class Engine
{
    private $context;

    private $setup;

    public function __construct($fixtures)
    {
        $this->loadContextData = __DIR__ . '/../Context.php';
        $this->loadContextData = __DIR__ . '/../Context.php';

        $this->loadContext();

        require __DIR__ . '/Setup.php';
        $this->setup = new Setup($fixtures);
    }

    protected function loadContext()
    {
        $contextData = require($this->loadContextData);

        $this->setContext($contextData);
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

        $output = $this->runAction($testContext['action'], $testContext['post_setup']);

        $testContext['post_action'] = $output;

        $this->executeRulesPostAction($testContext);
    }

    public function setupFixtures(array $setupRequests, array & $output)
    {
        foreach($setupRequests as $setupRequest => $data)
        {

            $setupFunction = studly_case($setupRequest);

            $this->setup->$setupFunction($data, $output);
        }
    }

    public function executeRulesPostAction(array $data)
    {

    }

    public function runAction($actionData, $postSetupData)
    {
        return [];
    }

    public function getDefaultContext(): array
    {
        return [
            'setup'       => [],
            'post_setup'  => [],
            'action'      => function () {},
            'post_action' => [],
        ];
    }
}
