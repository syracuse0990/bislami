<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CustomerWorkspaceResource;
use App\Support\CustomerWorkspaceData;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    public function __invoke(Request $request, CustomerWorkspaceData $workspaceData): CustomerWorkspaceResource
    {
        return CustomerWorkspaceResource::make($workspaceData->build($request->user()));
    }
}