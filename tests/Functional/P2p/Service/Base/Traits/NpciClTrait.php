<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

trait NpciClTrait
{
    /**
     * @var case assertion message
     */

    protected $npciClAssertionMessage;
    /**
     * @param array $input
     * @param string $action
     * @param callable|null $closure
     *  Param 1 is list of params
     *  Param 2 is complete content
     */
    protected function handleNpciClRequest(
        array $input,
        string $action,
        string $callback = null,
        array $vector = [],
        callable $closure = null)
    {
        $expected = [
            'version'   => 'v1',
            'type'      => 'sdk',
            'request' => [
                'sdk'       => 'npci',
                'content'   => [
                    'vector' => $vector,
                ],
                'action'    => $action,
            ]
        ];

        if (is_null($callback) === false)
        {
            $expected['callback'] = $callback;
        }

        $this->assertArraySubset($expected, $input, true, $this->npciClAssertionMessage);

        if (is_callable($closure) === true)
        {
            $closure($input['request']);
        }
    }
}
