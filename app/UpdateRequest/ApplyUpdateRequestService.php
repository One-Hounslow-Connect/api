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
     * @return bool
     */
    public function applyUpdateRequestIfAdmin(Request $request, AppliesUpdateRequests $entity, UpdateRequest $updateRequest)
    {
        if ($request->user()->isGlobalAdmin()) {
            $entity->applyUpdateRequest($updateRequest);

            return true;
        }

        return false;
    }
}
