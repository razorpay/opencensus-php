<?php

namespace RZP\Tests\Functional\Merchant\Traits;

/**
 * TODO:
 * - When needed move this to some other name space where it
 *   can be used by other tests as well. It's content are nothing
 *   specific to this name space.
 */
trait MakesEsDocumentAssertions
{
    public function getAndAssertEsDocForEntityAndMode(
        string $id,
        array $expected,
        string $entity,
        string $mode): array
    {
        $params = [
            'index' => env('ES_ENTITY_TYPE_PREFIX')."{$entity}_{$mode}",
            'type'  => env('ES_ENTITY_TYPE_PREFIX')."{$entity}_{$mode}",
            'id'    => $id,
        ];

        $actual = $this->es->get($params)['_source'];

        $this->assertArraySelectiveEquals($expected, $actual);

        return $actual;
    }
}
