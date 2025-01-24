<?php

namespace Vanguard\Http\Controllers\Api;

use Vanguard\Http\Resources\CountryResource;
use Vanguard\Repositories\Country\CountryRepository;

class CountriesController extends ApiController
{
    public function __construct(private readonly CountryRepository $countries)
    {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return CountryResource::collection($this->countries->all());
    }
}
