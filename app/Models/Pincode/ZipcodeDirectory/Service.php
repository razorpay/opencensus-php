<?php

namespace RZP\Models\Pincode\ZipcodeDirectory;

use Request;
use RZP\Models\Base;
use RZP\Models\ZipcodeDirectory;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    /**
     * Add/Update zipCode into zipcode directory
     * @param array $input
     */
    public function add(array $input)
    {
        Validator::validateZipcodeDirectory($input);

        foreach ($input['zipcodes'] as $zipcode)
        {
            $zipcodeData = $this->repo->zipcode_directory->findByZipcodeAndCountry(
                $zipcode['zipcode'],
                $zipcode['country']);

            if(empty($zipcodeData) === false)
            {
                $this->core->updateZipcodeData($zipcodeData, $zipcode);
            }
            else
            {
                $this->core->createAndSaveZipcodeData($zipcode);
            }
        }
        return [];
    }

    /**
     * Soft Delete zipCode from zipcode directory
     * @param array $input
     */
    public function remove(array $input)
    {
        Validator::validateZipcodeDirectory($input);
        foreach ($input['zipcodes'] as $zipcode)
        {
            $zipcodeData = $this->repo->zipcode_directory->findByZipcodeAndCountry(
                $zipcode['zipcode'],
                $zipcode['country']);

            if(empty($zipcodeData) === false){
                $this->core->delete($zipcodeData);
            }
        }
        return [];
    }

}
