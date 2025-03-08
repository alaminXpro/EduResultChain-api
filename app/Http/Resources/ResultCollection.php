<?php

namespace Vanguard\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ResultCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ResultResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Return the collection directly without nesting it in another 'data' key
        return $this->collection->toArray();
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        // Only add pagination data if the resource is a paginator
        if (method_exists($this->resource, 'total')) {
            return [
                'pagination' => [
                    'total' => $this->resource->total(),
                    'count' => $this->resource->count(),
                    'per_page' => $this->resource->perPage(),
                    'current_page' => $this->resource->currentPage(),
                    'total_pages' => $this->resource->lastPage(),
                    'links' => [
                        'first' => $this->resource->url(1),
                        'last' => $this->resource->url($this->resource->lastPage()),
                        'prev' => $this->resource->previousPageUrl(),
                        'next' => $this->resource->nextPageUrl(),
                    ]
                ]
            ];
        }
        
        return [];
    }
} 