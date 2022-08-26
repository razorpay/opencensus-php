<?php

namespace RZP\Models\Pincode\ZipcodeDirectory;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function createAndSaveZipcodeData($input): Entity
    {
        $zipcodeEntity = (new Entity)->build($input);

        $zipcodeEntity->generateId();

        $this->repo->zipcode_directory->saveOrFail($zipcodeEntity);

        return $zipcodeEntity;
    }

    public function updateZipcodeData($zipcodeData, $input){

        $zipcodeData->edit($input);

        return $this->repo->saveOrFail($zipcodeData);
    }

    public function delete(Entity $zipcodeEntity)
    {
        return $this->repo->zipcode_directory->deleteOrFail($zipcodeEntity);
    }
}
