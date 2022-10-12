<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity as E;

class CyberCrimeHelpDeskController extends Controller
{
    public function sendMailToLEAFromCyberCrimeHelpdesk()
    {
        $input    = Request::all();

        $response = $this->service(E::CYBER_CRIME_HELP_DESK)->sendMailToLEAFromCyberCrimeHelpdesk($input);

        return ApiResponse::json($response);
    }

    public function postCyberCrimeWorflowCreateAction()
    {
        $input    = Request::all();

        $response = $this->service(E::CYBER_CRIME_HELP_DESK)->postCyberCrimeWorflowCreateAction($input);

        return ApiResponse::json($response);
    }

    public function postCyberCrimeWorkflowApproval()
    {
        $input   = Request::all();

        $response = $this->service(E::CYBER_CRIME_HELP_DESK)->postCyberCrimeWorkflowApproval($input);

        return ApiResponse::json($response);
    }
}
