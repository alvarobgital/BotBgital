<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\CoverageArea;
use Illuminate\Http\Request;

class CoverageController extends Controller
{
    public function index(Request $request)
    {
        $query = CoverageArea::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                $q->where('neighborhood', 'like', "%{$search}%")
                    ->orWhere('zip_code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('active') && $request->active == 1) {
            $query->where('is_active', true);
        }

        $limit = $request->input('limit', 20);
        return response()->json($query->orderBy('zip_code')->paginate($limit));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        // Detect delimiter (guess)
        $firstLine = file_get_contents($path, false, null, 0, 500);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, 1000, $delimiter);

        if (!$header) {
            return response()->json(['message' => 'Archivo CSV vacío o inválido.'], 422);
        }

        // Map columns with aliases (including common postal DB headers)
        $cleanHeader = array_map('trim', array_map('strtolower', $header));

        $cityAliases = ['ciudad', 'municipio', 'poblacion', 'city', 'población', 'd_mnpio', 'municipio_nombre'];
        $neighAliases = ['colonia', 'asentamiento', 'neighborhood', 'fraccionamiento', 'd_asenta', 'colonia_nombre'];
        $zipAliases = ['codigo_postal', 'cp', 'c.p.', 'zip_code', 'zip', 'código postal', 'código_postal', 'd_codigo', 'cp_codigo'];
        $activeAliases = ['activo', 'status', 'estado', 'is_active', 'active'];

        $map = [
            'city' => $this->findAlias($cleanHeader, $cityAliases),
            'neighborhood' => $this->findAlias($cleanHeader, $neighAliases),
            'zip_code' => $this->findAlias($cleanHeader, $zipAliases),
            'active' => $this->findAlias($cleanHeader, $activeAliases),
        ];

        // Fallback or Guess by specific Toluca format if headers failed
        // Based on user screenshot: 0:ZIP, 1:Neighborhood, 2:Type, 3:City
        if ($map['zip_code'] === false)
            $map['zip_code'] = 0;
        if ($map['neighborhood'] === false)
            $map['neighborhood'] = 1;
        if ($map['city'] === false)
            $map['city'] = 3;
        if ($map['active'] === false)
            $map['active'] = 4;

        $count = 0;
        $errors = 0;

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            if (count($data) < 2)
                continue;

            $zipCode = trim($data[$map['zip_code']] ?? '');
            $neighborhood = trim($data[$map['neighborhood']] ?? '');
            $city = trim($data[$map['city']] ?? 'México');
            $activeStr = trim($data[$map['active']] ?? 'si');

            // Heuristic filter: If ZIP is at index 0 but it's not numeric, but index 2 is numeric, swap
            if (!is_numeric($zipCode) && isset($data[2]) && is_numeric(trim($data[2]))) {
                $zipCode = trim($data[2]);
            }

            // Clean data
            $zipCode = preg_replace('/[^0-9]/', '', $zipCode);
            // Normalize to Title Case to avoid duplicates like "CENTRO" vs "Centro"
            $neighborhood = mb_convert_case(trim($neighborhood), MB_CASE_TITLE, "UTF-8");
            $city = mb_convert_case(trim($city), MB_CASE_TITLE, "UTF-8");

            // Final Validation: Skip if headers or empty
            if (!$neighborhood || !$zipCode || is_numeric($neighborhood) || strlen($zipCode) < 4) {
                $errors++;
                continue;
            }

            $isActive = (strtolower($activeStr) === 'no' || $activeStr === '0' || $activeStr === 'false') ? false : true;

            // Use string conversion to ensure key matching in updateOrCreate
            \App\Models\CoverageArea::updateOrCreate(
            ['zip_code' => (string)$zipCode, 'neighborhood' => (string)$neighborhood],
            ['city' => (string)$city, 'is_active' => (bool)$isActive]
            );
            $count++;
        }
        fclose($handle);

        return response()->json([
            'message' => "Importación completada: {$count} registros procesados." . ($errors > 0 ? " ({$errors} filas ignoradas)." : "")
        ]);
    }

    public function clearAll()
    {
        \App\Models\CoverageArea::query()->delete();
        return response()->json(['message' => 'Todas las zonas de cobertura han sido eliminadas.']);
    }

    private function findAlias($header, $aliases)
    {
        foreach ($aliases as $alias) {
            $index = array_search($alias, $header);
            if ($index !== false)
                return $index;
        }
        return false;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'city' => 'required|string',
            'neighborhood' => 'required|string',
            'zip_code' => 'required|string',
            'streets' => 'nullable|string',
        ]);

        $area = CoverageArea::create($validated);
        return response()->json($area, 201);
    }

    public function update(Request $request, CoverageArea $coverage)
    {
        $validated = $request->validate([
            'city' => 'sometimes|string',
            'neighborhood' => 'sometimes|string',
            'zip_code' => 'sometimes|string',
            'streets' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $coverage->update($validated);
        return response()->json($coverage);
    }

    public function destroy(CoverageArea $coverage)
    {
        $coverage->delete();
        return response()->json(['status' => 'deleted']);
    }
}
