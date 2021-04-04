<?php

namespace RZP\Tests\Unit\Services\Harvester;

use RZP\Tests\TestCase;

class HarvesterClientTest extends TestCase
{
    protected $algo;

    protected $classname;

    protected function setUp(): void
    {
        parent::setUp();

        $this->algo = 'sha1';

        $this->classname = '\RZP\Services\Harvester\HarvesterClient';
    }

    /**
     * Method to successfully test signature generation
     *
     * @dataProvider addDataForSignatureCheck
     */
    public function testGenerateSignatureSuccess(array $config, string $message)
    {
        $value = $this->getGenerateSignatureMockValue($config, [$message, $config['secret']]);

        $expectedSignature = $this->getSignature($message, $config['secret']);

        $this->assertEquals($expectedSignature, $value);
    }

    /**
     * Method to failure test signature generation for incorrect key
     *
     * @dataProvider addDataForSignatureCheck
     */
    public function testGenerateSignatureFailureIncorrectKey(array $config, string $message)
    {
        $value = $this->getGenerateSignatureMockValue($config, [$message, $config['secret']]);

        $expectedSignature = $this->getSignature($message. '_incorrect', $config['secret']);

        $this->assertNotEquals($expectedSignature, $value);
    }

    /**
     * Method to failure test signature generation for incorrect secret
     *
     * @dataProvider addDataForSignatureCheck
     */
    public function testGenerateSignatureFailureIncorrectSecret(array $config, string $message)
    {
        $value = $this->getGenerateSignatureMockValue($config, [$message, $config['secret']]);

        $expectedSignature = $this->getSignature($message, $config['secret'] . '_incorrect');

        $this->assertNotEquals($expectedSignature, $value);
    }

    protected function getSignature($message, $key)
    {
        return hash_hmac($this->algo, $message, $key);
    }

    /**
     * Method to check sensisitive info is removed before raising an event
     *
     * @dataProvider addDataForSensitiveDataRemoval
     */
    public function testRemoveSensitiveInformation(array $sensitiveArgs, array $inSensitiveArgs)
    {
        $functionName = 'removeSensitiveInformation';

        $args = array_merge($sensitiveArgs, $inSensitiveArgs);

        $mock = $this->getClassMock();

        $reflectionClass = new \ReflectionClass($this->classname);

        $method = $this->setMethodAccessible($reflectionClass, $functionName);

        $method->invokeArgs($mock, [&$args]);

        $this->assertEquals($inSensitiveArgs, $args);
    }

    /**
     * Method to create a mock signature generator
     *
     * @param array $config
     * @return mixed
     */
    protected function getGenerateSignatureMockValue(array $config, array $args)
    {
        $functionName = 'generateSignature';

        $constructorProperty = 'config';

        $mock = $this->getClassMock();

        $reflectionClass = new \ReflectionClass($this->classname);

        $reflectionProperty = $reflectionClass->getProperty($constructorProperty);

        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue($mock, $config);

        $method = $this->setMethodAccessible($reflectionClass, $functionName);

        return $method->invokeArgs($mock, $args);
    }

    /**
     * Method to create mock of a given class
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getClassMock()
    {
        $mock = $this->getMockBuilder($this->classname)
              ->disableOriginalConstructor()
              ->setMethods(null)
              ->getMock();

        return $mock;
    }

    /**
     * Method to set a method accessible for mocking through reflection
     *
     * @param \ReflectionClass $reflectionClass
     * @param string $method
     * @return ReflectionMethod
     */
    protected function setMethodAccessible(\ReflectionClass $reflectionClass, string $method)
    {
        $method = $reflectionClass->getMethod($method);

        $method->setAccessible(true);

        return $method;
    }

    /**
     * Test data for generate signature function
     *
     * @return array $testData
     */
    public function addDataForSignatureCheck()
    {
        $config = [
            'key'       => 'hmac_key',
            'secret'    => 'hmac_secret'
        ];

        $hmacMessage = 'message';

        $testData = [[$config, $hmacMessage]];

        return $testData;
    }

    /**
     * Test data for removing sensitive info function
     *
     * @return array $testData
     */
    public function addDataForSensitiveDataRemoval()
    {
        $sensitiveArgs = [
            'card.number'  => 123
        ];

        $inSensitiveArgs = [
            'test.value'   => 'test'
        ];

        $testData = [[$sensitiveArgs, $inSensitiveArgs]];

        return $testData;
    }
}
