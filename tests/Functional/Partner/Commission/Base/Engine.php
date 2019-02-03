<?php

namespace RZP\Tests\Functional\Partner\Commission\Base;

use RZP\Tests\Functional\TestCase;
use Functional\Partner\Commission\Action;
use Functional\Partner\Commission\Rules;

class Engine extends TestCase
{
    private $context;

    private $setup;

    private $action;

    private $rules;

    public function __construct($fixtures)
    {
        $this->loadContextData = __DIR__ . '/../Context.php';
        $this->loadContextData = __DIR__ . '/../Context.php';

        $this->loadContext();

        require __DIR__ . '/Setup.php';
        $this->setup = new Setup($fixtures);

        require __DIR__ . '/../Action.php';
        $this->action = new Action;

        require __DIR__ . '/../Rules.php';
        $this->rules = new Rules;
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

        $this->action->$contextName($testContext['post_setup'], $testContext['post_action']);

        $this->rules->$contextName($testContext);
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
