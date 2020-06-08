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
}
