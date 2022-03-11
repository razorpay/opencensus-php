<?php

namespace Unit\Models\PaymentLink;

use RZP\Services\Elfin;
use RZP\Models\PaymentLink\ElfinWrapper;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;

class ElfinWrapperTest extends BaseTest
{
    const GIMLI_ID_VALUE        = "J2tmF3eM1AAv3Z";
    const CUSTOM_SLUG_VALUE     = "myslug";
    const GIMLI_ALIAS_ID_VALUE  = "J2tmF3eM1AAv3Z";
    const GIMLI_HASH_KEY_VALUE  = "g56xX8VTU";
    const ENTITY_VALUE          = "PAGE";
    const ENTITY_ID_VALUE       = "RAMDOM_ID";
    const MODE_VALUE            = "live";

    const GIMLI_ID_KEY      = "id";
    const ALIASES_KEY       = "url_aliases";
    const URL_ID_KEY        = "url_id";
    const HASH_KEY          = "hash";
    const GIMLI_HASH_KEY    = "hash_key";
    const META_DATA_KEY     = "metadata";
    const ENTITY_KEY        = "entity";
    const MODE_KEY          = "mode";

    const EXPAND_RESULT = [
        self::GIMLI_ID_KEY  => self::GIMLI_ID_VALUE,
        self::ALIASES_KEY   => [
            [
                self::GIMLI_ID_KEY  => self::GIMLI_ALIAS_ID_VALUE,
                self::URL_ID_KEY    => self::GIMLI_ID_VALUE,
                self::HASH_KEY      => self::CUSTOM_SLUG_VALUE,
                self::META_DATA_KEY => [
                    self::GIMLI_ID_KEY  => self::ENTITY_ID_VALUE,
                    self::ENTITY_KEY    => self::ENTITY_VALUE,
                    self::MODE_KEY      => self::MODE_VALUE,
                ],
            ]
        ],
        self::GIMLI_HASH_KEY    => self::GIMLI_HASH_KEY_VALUE,
    ];

    const SHORTEN_URL_BASE = "https://rzp.io/l";

    /**
     * @group nocode_gimli_slug_wrapper
     * @return void
     */
    public function testExpand()
    {
        $this->mockGimli();

        $wrapper = new ElfinWrapper(ElfinService::GIMLI);

        $result = $wrapper->expand(self::CUSTOM_SLUG_VALUE);

        $this->assertExpandedResult($result, $this->getExpandResult());

        // in previous call the response should have been cached if not it's a failure
        $this->assertCachedResponse();
    }

    /**
     * @group nocode_gimli_slug_wrapper
     * @return void
     */
    public function testExpandAndGetMetadataWithValue()
    {
        $this->mockGimli();

        $wrapper = new ElfinWrapper(ElfinService::GIMLI);

        $result = $wrapper->expandAndGetMetadata(self::CUSTOM_SLUG_VALUE);

        $expected = $this->getExpandResult()[self::ALIASES_KEY][0][self::META_DATA_KEY];

        $this->assertExpandedResult($result, $expected);
    }

    /**
     * @group nocode_gimli_slug_wrapper
     * @return void
     */
    public function testExpandAndGetMetadataWithNull()
    {
        $this->mockGimli(['expand'], ['expand_result' => null]);

        $wrapper = new ElfinWrapper(ElfinService::GIMLI);

        $result = $wrapper->expandAndGetMetadata(self::CUSTOM_SLUG_VALUE);

        $this->assertNull($result);
    }

    /**
     * @group nocode_gimli_slug_wrapper
     * @return void
     */
    public function testShorten()
    {
        $this->mockGimli(['shorten', 'expand']);

        $wrapper = new ElfinWrapper(ElfinService::GIMLI);

        $result = $wrapper->shorten($this->getShortenResult());

        $this->assertEquals($this->getShortenResult(), $result);

        // in previous call the response should have been cached if not it's a failure
        $this->assertCachedResponse();
    }

    /**
     * @group nocode_gimli_slug_wrapper
     * @return void
     */
    public function testUpdate()
    {
        $this->mockGimli(['update', 'expand']);

        $wrapper = new ElfinWrapper(ElfinService::GIMLI);

        $resultHash = $wrapper->update(self::CUSTOM_SLUG_VALUE, "");

        $this->assertEquals(self::GIMLI_HASH_KEY_VALUE, $resultHash);

        // in previous call the response should have been cached if not it's a failure
        $this->assertCachedResponse();
    }

    // =============== Private Methods ================== //
    /**
     * @param array $mockMethods
     * @param array $inputs
     *
     * @return void
     */
    private function mockGimli(array $mockMethods = ['expand'], array $inputs = [])
    {
        $gimli = $this->getMockBuilder(Elfin\Impl\Gimli::class)
            ->setConstructorArgs([$this->app['config']->get('applications.elfin.gimli')])
            ->setMethods($mockMethods)
            ->getMock();

        $hash = array_get($inputs, 'hash', self::CUSTOM_SLUG_VALUE);

        foreach ($mockMethods as $mockMethod)
        {
            $method = strtolower($mockMethod);
            $resultFunc = 'get'. ucfirst($method) . 'Result';
            $result = array_get($inputs, $method . '_result', $this->$resultFunc($hash));

            $gimli->expects($this->any())
                ->method($method)
                ->willReturn($result);
        }

        $elfin = $this->getMockBuilder(Elfin\Mock\Service::class)
            ->setConstructorArgs([$this->app['config'], $this->app['trace']])
            ->setMethods(['driver'])
            ->getMock();

        $elfin->expects($this->any())
            ->method('driver')
            ->with('gimli')
            ->willReturn($gimli);

        $this->app->instance('elfin', $elfin);
    }

    /**
     * @param string $hash
     *
     * @return array
     */
    private function getExpandResult(string $hash = self::CUSTOM_SLUG_VALUE): array
    {
        $values = self::EXPAND_RESULT;

        $values[self::ALIASES_KEY][0][self::HASH_KEY] = $hash;

        return $values;
    }

    /**
     * @param array|null $actualResult
     * @param array|null $expectedResult
     *
     * @return void
     */
    private function assertExpandedResult(?array $actualResult, ?array $expectedResult)
    {
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @param string $hash
     *
     * @return string
     */
    private function getShortenResult(string $hash = self::CUSTOM_SLUG_VALUE): string
    {
        return self::SHORTEN_URL_BASE . "/$hash";
    }

    /**
     * @param string $hash
     *
     * @return string
     */
    private function getUpdateResult(string $hash = self::CUSTOM_SLUG_VALUE): string
    {
        return self::GIMLI_HASH_KEY_VALUE;
    }

    /**
     * @return void
     */
    private function assertCachedResponse()
    {
        $this->mockGimli(['expand'], ['expand_result' => []]);

        $wrapper = new ElfinWrapper(ElfinService::GIMLI);

        $result = $wrapper->expand(self::CUSTOM_SLUG_VALUE);

        $this->assertExpandedResult($result, $this->getExpandResult());
    }
}
