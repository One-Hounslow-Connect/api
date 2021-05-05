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
        $user = $request->user('api');

        if ($user->isGlobalAdmin()) {
            return $updateRequest->apply($user);
        }

        return $updateRequest;
    }
}
