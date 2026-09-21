<?php

namespace Modules\AccessManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'granter_id' => $this->granter_id,
            'grantee_id' => $this->grantee_id,
            'group_id' => $this->group_id,
            'permission_name' => $this->permission_name,
            'action' => $this->action,
            'granted_at' => $this->granted_at?->toIso8601String(),
        ];
    }
}
