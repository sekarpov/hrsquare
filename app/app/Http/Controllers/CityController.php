<?php

namespace App\Http\Controllers;

use App\Http\Requests\CityRequest;
use App\Models\City;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CityController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => City::orderBy('name')->get()]);
    }

    public function store(CityRequest $request): JsonResponse
    {
        try {
            $city = City::create($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => ['Такой город уже есть в справочнике.']]);
        }

        return response()->json(['data' => $city], 201);
    }
}
