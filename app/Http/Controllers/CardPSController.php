<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Constants\Entity;

class CardPSController extends Controller
{
    public function FetchEntity($entity, $id)
    {
        $repoName = Entity::getEntityRepository($entity);

        $repo = new $repoName;

        $data = $repo->findOrFailPublic($id);

        return ApiResponse::json($data->toArrayAdmin());
    }

    public function BackfillRouteProxy($entity, $column)
    {
        $path = '/v1/entities/backfill/' . $entity . '/' . $column;

        if (Request::has('limit') === true)
        {
            $path = $path . '?limit=' . Request::Query('limit');
        }

        $response = $this->app['card.payments']->sendRequest('GET', $path);

        return ApiResponse::json($response);
    }
}
