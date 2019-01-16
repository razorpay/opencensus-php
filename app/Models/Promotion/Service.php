<?php

namespace RZP\Models\Promotion;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function create(array $input): array
    {
        $this->trace->info(TraceCode::PROMOTION_CREATE_REQUEST, $input);

        $this->app = App::getFacadeRoot();

        $adminName = app('basicauth')->getAdmin()->getName();

        $input[Entity::CREATOR_NAME] = $adminName;

        $promotion = $this->core()->create($input);

        return $promotion->toArrayAdmin();
    }

    public function update(string $id, array $input): array
    {
        $this->trace->info(TraceCode::PROMOTION_UPDATE_REQUEST, $input);

        $promotion = $this->repo->promotion->findByPublicId($id);

        $promotion = $this->core()->update($promotion, $input);

        return $promotion->toArrayAdmin();
    }
}
