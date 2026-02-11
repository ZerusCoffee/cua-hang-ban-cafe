<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{

    //GET ALL
    public function index()
    {
        $units = Unit::all();

        return response()->json([
            'success' => true,
            'data' => $units
        ]);
    }

    // CREATE
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:25',
            'symbol' => 'required|string|max:5|unique:units,symbol'
        ]);

        $unit = Unit::create($validated);

        return response()->json([
            'success' => true,
            'data' => $unit
        ], 201);
    }

    // GET ONE BY ID
    public function show(Unit $unit)
    {
        return response()->json([
            'success' => true,
            'data' => $unit
        ]);
    }

    // UPDATE
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:25',
            'symbol' => [
                'required',
                'string',
                'max:5',
                Rule::unique('units')->ignore($unit->id),
            ],
        ]);

        $unit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật đơn vị thành công',
            'data' => $unit
        ]);
    }

    // DELETE
    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa đơn vị thành công'
        ]);
    }
}
