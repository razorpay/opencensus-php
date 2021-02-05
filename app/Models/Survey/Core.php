<?php

namespace RZP\Models\Survey;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        $survey = (new Entity)->build($input);

        $this->repo->saveOrFail($survey);

        return $survey;
    }

    public function edit(Entity $survey, array $input): Entity
    {
        $survey->edit($input);

        $this->repo->saveOrFail($survey);

        return $survey;
    }
}
