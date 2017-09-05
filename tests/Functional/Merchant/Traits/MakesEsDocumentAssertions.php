<?php

namespace RZP\Tests\Functional\Merchant\Traits;

/**
 * TODO:
 * - Move this to some other name space where it can be used
 *   by other tests as well.
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
            'index' => "testing_{$entity}_{$mode}",
            'type'  => "testing_{$entity}_{$mode}",
            'id'    => $id,
        ];

        $actual = $this->es->get($params)['_source'];

        $this->assertArraySelectiveEquals($expected, $actual);

        return $actual;
    }
}
