<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Application\Entity;
use RZP\Models\Application\Service as ApplicationService;
use RZP\Models\Application\ApplicationMerchantTags\Entity as MerchantTagEntity;
use RZP\Models\Application\ApplicationMerchantTags\Service as MerchantTagService;
use RZP\Models\Application\ApplicationTags\Service as ApplicationMappingService;
use RZP\Models\Application\ApplicationMerchantMaps\Service as ApplicationMerchantMappingService;

class ApplicationFrameworkController extends Controller
{
    protected $appService;

    protected $appMappingService;

    protected $appMerchantMappingService;

    protected $appMerchantTagService;

    public function __construct()
    {
        parent::__construct();

        $this->appService = new ApplicationService;

        $this->appMappingService = new ApplicationMappingService;

        $this->appMerchantMappingService = new ApplicationMerchantMappingService;

        $this->appMerchantTagService = new MerchantTagService;
    }

    public function createApp()
    {
        $input = Request::all();

        $response = $this->appService->create($input);

        return response()->json($response);
    }

    public function updateApp(string $id)
    {
        $input = Request::all();

        $input[Entity::ID] = $id;

        $response = $this->appService->update($input);

        return response()->json($response);
    }

    public function getApp(string $id)
    {
        $response = $this->appService->get($id);

        return response()->json($response);
    }

    public function createAppMapping()
    {
        $input = Request::all();

        $response = $this->appMappingService->create($input);

        return response()->json($response);
    }

    public function deleteAppMapping()
    {
        $input = Request::all();

        $response = $this->appMappingService->deleteAppsInTag($input);

        return response()->json($response);
    }

    public function deleteTag()
    {
        $input = Request::all();

        $response = $this->appMappingService->deleteTag($input);

        return response()->json($response);
    }

    public function createMerchantMapping()
    {
        $input = Request::all();

        $response = $this->appMerchantMappingService->create($input);

        return response()->json($response);
    }

    public function updateMerchantMapping()
    {
        $input = Request::all();

        $response = $this->appMerchantMappingService->update($input);

        return response()->json($response);
    }

    public function getAppsForMerchant(string $merchantId)
    {
        $input = Request::all();

        $response = $this->appMerchantMappingService->get($merchantId, $input);

        return response()->json($response);
    }

    public function createMerchantTag(string $merchantId)
    {
        $input = Request::all();

        $input[MerchantTagEntity::MERCHANT_ID] = $merchantId;

        $response = $this->appMerchantTagService->create($input);

        return response()->json($response);
    }

    public function updateMerchantTag(string $merchantId)
    {
        $input = Request::all();

        $input[MerchantTagEntity::MERCHANT_ID] = $merchantId;

        $response = $this->appMerchantTagService->update($input);

        return response()->json($response);
    }
}
