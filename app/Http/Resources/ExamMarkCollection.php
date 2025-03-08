<?php

namespace Vanguard\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ExamMarkCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ExamMarkResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->collection->count(),
                'per_page' => request('per_page', 15),
                'current_page' => request('page', 1)
            ]
        ];
    }
} 