<?php

namespace Vanguard\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'registration_number' => $this->registration_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'permanent_address' => $this->permanent_address,
            'image' => $this->image,
            'phone_verified' => $this->verified ?? false,
            'phone_verified_at' => $this->verified_at ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 