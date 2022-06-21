<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Constants\Entity as E;
use RZP\Models\Base\Audit\Service as AuditingService;

class AuditingController extends Controller
{

    public function createAuditInfoPartition()
    {
        $response = (new AuditingService())->createAuditInfoPartition();

        return ApiResponse::json($response);
    }

    public function getMerchantAuditInfo($id)
    {
        $input = Request::all();

        return (new AuditingService())->getMerchantAuditInfo($id, $input);
    }

    public function getAuditInfo($entity, $id)
    {
        $input = Request::all();

        return (new AuditingService())->getAuditInfo($entity, $id, $input);
    }

    public function getAuditEntities()
    {
        $entities = [];
        foreach (\RZP\Constants\Entity::AUDITED_ENTITIES as $entity)
        {

            $entityClass = E::getEntityClass($entity);

            array_push($entities, (new $entityClass)->getTable());
        }

        return $entities;
    }
}
