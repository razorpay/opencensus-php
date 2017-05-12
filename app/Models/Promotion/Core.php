<?php

namespace RZP\Models\Promotion;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $promotion = (new Entity)->build($input);

        if ($promotion->getIterations() > 1)
        {
            $schedule = $this->createSchedule();
        }

        $this->repo->saveOrFail($promotion, $input);

        return $promotion;
    }

    public function update(Entity $promotion, array $input)
    {
        $promotion->edit($input);

        $this->repo->saveOrFail($promotion);

        return $promotion;
    }

    protected function createSchedule()
    {

    }
}
