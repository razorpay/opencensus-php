<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Tests\Functional\TestCase;
use RZP\Models\PaymentLink\Entity;
use RZP\Models\PaymentLink\EsRepository;

class EsRespositoryTest extends TestCase
{
    /**
     * @group nocode_pp_es_repository
     */
    public function testBuildQueryForUserId()
    {
        $esRepo = \Mockery::mock(EsRepository::class)->makePartial();
        $query  = [];
        $userId = "someUserId";
        $esRepo->buildQueryForUserId($query, $userId);
        $this->assertTrue(array_get($query, "bool.filter.bool.must.0.term.".Entity::USER_ID.".value") === $userId);
    }

    /**
     * @group nocode_pp_es_repository
     */
    public function testBuildQueryForStatus()
    {
        $esRepo = \Mockery::mock(EsRepository::class)->makePartial();
        $query  = [];
        $status = "someStatus";
        $esRepo->buildQueryForStatus($query, $status);
        $this->assertTrue(array_get($query, "bool.filter.bool.must.0.term.".Entity::STATUS.".value") === $status);
    }

    /**
     * @group nocode_pp_es_repository
     */
    public function testBuildQueryForStatusReason()
    {
        $esRepo = \Mockery::mock(EsRepository::class)->makePartial();
        $query  = [];
        $statusReason = "someStatusReason";
        $esRepo->buildQueryForStatusReason($query, $statusReason);
        $this->assertTrue(array_get($query, "bool.filter.bool.must.0.term.".Entity::STATUS_REASON.".value") === $statusReason);
    }

    /**
     * @group nocode_pp_es_repository
     */
    public function testBuildQueryForReceipt()
    {
        $esRepo = \Mockery::mock(EsRepository::class)->makePartial();
        $query  = [];
        $recipt = "SomeRecipt";
        $esRepo->buildQueryForReceipt($query, $recipt);
        $this->assertTrue(array_get($query, "bool.filter.bool.must.0.term.".Entity::RECEIPT.".value") === $recipt);
    }

    /**
     * @group nocode_pp_es_repository
     */
    public function testBuildQueryForViewType()
    {
        $esRepo = \Mockery::mock(EsRepository::class)->makePartial();
        $query  = [];
        $type = "type";
        $esRepo->buildQueryForViewType($query, $type);
        $this->assertTrue(array_get($query, "bool.filter.bool.must.0.term.".Entity::VIEW_TYPE.".value") === $type);
    }
}
