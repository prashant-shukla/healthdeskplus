<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Practice;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PracticeController extends Controller
{
    public function index(Request $request)
    {
        $practices = Practice::with('doctors')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $practices
        ]);
    }

    public function show(Request $request, $id)
    {
        $practice = Practice::with(['doctors', 'patients'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $practice
        ]);
    }

    public function update(Request $request, $id)
    {
        $practice = Practice::findOrFail($id);
        
        $practice->update($request->only([
            'name', 'description', 'type', 'license_number', 'address', 'city',
            'state', 'pincode', 'country', 'phone', 'email', 'website', 'settings'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Practice updated successfully',
            'data' => $practice
        ]);
    }
}