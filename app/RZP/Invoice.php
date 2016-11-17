<?php

namespace App\RZP;

use Auth;

class Invoice extends Entity
{
    public function create($params = null)
    {
        $params['user_id'] = Auth::user()->id;

        return parent::create($params);
    }

    public function all($options = [])
    {
        $options['user_id'] = Auth::user()->id;

        return parent::all($options);
    }

    public function fetch($id)
    {
        // User id should be sent here as well.
        // Any delivery boy can see anyone's invoice
        // if we dont' send the user_id here
        return parent::fetch($id);
    }
}
