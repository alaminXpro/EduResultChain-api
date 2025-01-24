<?php

namespace Vanguard\Http\Controllers\Api;

use Setting;

class SettingsController extends ApiController
{
    public function __construct()
    {
        $this->middleware('permission:settings.general');
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json(Setting::all());
    }
}
