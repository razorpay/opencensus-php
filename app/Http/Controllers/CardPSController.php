<?php

namespace RZP\Http\Controllers;

use App;
use ApiResponse;
use Request;

use RZP\Models\Card;
use RZP\Constants\Entity;
use RZP\Models\Customer\Token;

class CardPSController extends Controller
{
    public function FetchEntity($entity, $id)
    {
        $repoName = Entity::getEntityRepository($entity);

        $repo = new $repoName;

        $data[$entity] = $repo->findOrFailPublic($id)->toArrayAdmin();

        return ApiResponse::json($data);
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

    public function CreateCardEntity()
    {
        $input = Request::all();

        $repo = App::getFacadeRoot()['repo'];

        $merchant = $repo->merchant->findorFail($input['card']['merchant_id']);

        unset($input['card']['merchant_id']);

        $card = (new Card\Core)->createViaCps($input['card'], $merchant, $input['save_token']);

        $response['card'] = $card->toArray();

        if ((isset($input['save_token']) === true) and
            ($input['save_token']))
        {
            try
            {
                $response['token'] = (new Token\Core)->createViaCps($input,$merchant, $card)->toArrayAdmin();
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);
            }
        }

        return ApiResponse::json($response);
    }
}
