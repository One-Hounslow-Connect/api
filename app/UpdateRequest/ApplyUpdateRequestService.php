<?php

namespace App\UpdateRequest;

use App\Models\UpdateRequest;
use Illuminate\Http\Request;

class ApplyUpdateRequestService
{
    /**
     * @param Request $request
     * @param AppliesUpdateRequests $entity
     * @param UpdateRequest $updateRequest
     * @return UpdateRequest
     */
    public function applyUpdateRequestIfAdmin(Request $request, AppliesUpdateRequests $entity, UpdateRequest $updateRequest): UpdateRequest
    {
        if ($request->user()->isGlobalAdmin()) {
            return $entity->applyUpdateRequest($updateRequest);
        }

        return $updateRequest;
    }
}
