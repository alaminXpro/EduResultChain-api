<?php

namespace Vanguard\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->resource->present()->avatar,
            'address' => $this->address,
            'country_id' => $this->country_id ? (int) $this->country_id : null,
            'role_id' => (int) $this->role_id,
            'status' => $this->status->value,
            'birthday' => $this->birthday ? $this->birthday->format('Y-m-d') : null,
            'last_login' => (string) $this->last_login,
            'two_factor_country_code' => (int) $this->two_factor_country_code,
            'two_factor_phone' => (string) $this->two_factor_phone,
            'two_factor_options' => json_decode($this->two_factor_options, true),
            'email_verified_at' => $this->email_verified_at ? (string) $this->email_verified_at : null,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            'country' => new CountryResource($this->whenLoaded('country')),
            'role' => $this->when($this->canViewRole($request), function () {
                return new RoleResource($this->resource->role);
            }),
        ];
    }

    private function canViewRole(Request $request): bool
    {
        return $this->resource->relationLoaded('role')
            && $request->user()->hasPermission('roles.manage');
    }

    public static function allowedIncludes(): array
    {
        return ['role', 'country'];
    }
}
