<?php

namespace RZP\Http\Controllers;

use App;
use ApiResponse;

use RZP\Services\Templating;

class TemplatingServiceController extends Controller
{
    public function createNamespace()
    {
        $data = $this
            ->templatingService()
            ->createNamespace($this->input);

        return ApiResponse::json($data);
    }

    public function listNamespace()
    {
        $data = $this
            ->templatingService()
            ->listNamespace($this->input);

        return ApiResponse::json($data);
    }

    public function getTemplateConfig($id)
    {
        $data = $this
            ->templatingService()
            ->getTemplateConfig($id);

        return ApiResponse::json($data);
    }

    public function updateTemplateConfig($id)
    {
        $data = $this
            ->templatingService()
            ->updateTemplateConfig($id, $this->input);

        return ApiResponse::json($data);
    }

    public function createTemplateConfig()
    {
        $data = $this
            ->templatingService()
            ->createTemplateConfig($this->input);

        return ApiResponse::json($data);
    }

    public function listTemplateConfig()
    {
        $data = $this
            ->templatingService()
            ->listTemplateConfig($this->input);

        return ApiResponse::json($data);
    }

    public function viewTemplateConfig($id)
    {
        $data = $this
            ->templatingService()
            ->viewTemplateConfig($id);

        return ApiResponse::json($data);
    }

    public function testPreProcessor()
    {
        $data = $this
            ->templatingService()
            ->testPreProcessor($this->input);

        return ApiResponse::json($data);
    }

    public function renderTemplate()
    {
        $data = $this
            ->templatingService()
            ->renderTemplate($this->input);

        return ApiResponse::json($data);
    }

    public function deleteTemplateConfig($id)
    {
        $data = $this
            ->templatingService()
            ->deleteTemplateConfig($id);

        return ApiResponse::json($data);
    }

    public function assignRole()
    {
        $data = $this
            ->templatingService()
            ->assignRole($this->input);

        return ApiResponse::json($data);
    }

    public function revokeRole()
    {
        $data = $this
            ->templatingService()
            ->revokeRole($this->input);

        return ApiResponse::json($data);
    }

    protected function templatingService()
    {
        $app = App::getFacadeRoot();

        return new Templating($app);
    }
}
