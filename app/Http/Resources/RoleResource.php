<?php

namespace Vanguard\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\QueryBuilder\AllowedInclude;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'removable' => (bool) $this->removable,
            'users_count' => is_null($this->users_count) ? null : (int) $this->users_count,
            'updated_at' => (string) $this->updated_at,
            'created_at' => (string) $this->created_at,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }

    public static function allowedIncludes(): array
    {
        return [
            'permissions',
            AllowedInclude::count('users_count'),
        ];
    }
}
