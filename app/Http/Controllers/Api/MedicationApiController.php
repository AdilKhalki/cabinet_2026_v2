<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicationApiController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = $request->query('q', '');

        $meds = Medication::active()
            ->search($q)
            ->orderBy('name')
            ->take(20)
            ->get(['id', 'name', 'generic_name', 'dosage_form', 'strength']);

        return response()->json($meds);
    }
}
