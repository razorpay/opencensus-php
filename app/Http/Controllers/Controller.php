<?php namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller extends Controller {

	use DispatchesCommands, ValidatesRequests;

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout()
    {
        if (is_null($this->layout) === false)
        {
            $this->layout = View::make($this->layout);
        }
    }

    protected function checkMode($mode)
    {
        if ($mode !== 'live' and $mode !== 'test')
        {
            throw new \Exception('Invalid Mode');
        }
    }

}
