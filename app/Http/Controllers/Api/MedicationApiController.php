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
        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';

        $meds = Medication::active()
            ->when($q !== '', fn ($query) => $query->search($q))
            ->orderBy('name')
            ->take(20)
            ->get(['id', 'name', 'generic_name', 'dosage_form', 'strength']);

        return response()->json($meds);
    }
}
