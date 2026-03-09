<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $query = Plan::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return response()->json($query->orderBy('category')->orderBy('price')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'speed' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $plan = Plan::create($validated);
        return response()->json($plan);
    }

    public function show(Plan $plan)
    {
        return response()->json($plan);
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'speed' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $plan->update($validated);
        return response()->json($plan);
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return response()->json(['message' => 'Plan eliminado']);
    }

    public function toggleActive(Plan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        return response()->json($plan);
    }

    public function importPlans(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120'
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $ext = strtolower($file->getClientOriginalExtension());

        $rows = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            // For Excel, try to use a simple reader or fallback
            // Using PhpSpreadsheet if available, else CSV fallback
            if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray();
                if (count($data) < 2) {
                    return response()->json(['message' => 'Archivo vacío o sin datos.'], 422);
                }
                $header = array_map(fn($h) => strtolower(trim($h ?? '')), $data[0]);
                for ($i = 1; $i < count($data); $i++) {
                    $row = [];
                    foreach ($header as $idx => $col) {
                        $row[$col] = $data[$i][$idx] ?? '';
                    }
                    $rows[] = $row;
                }
            }
            else {
                return response()->json(['message' => 'Para importar Excel (.xlsx) instala phpoffice/phpspreadsheet. Usa CSV por ahora.'], 422);
            }
        }
        else {
            // CSV
            $firstLine = file_get_contents($path, false, null, 0, 500);
            $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
            $handle = fopen($path, 'r');
            $header = fgetcsv($handle, 2000, $delimiter);
            if (!$header) {
                return response()->json(['message' => 'Archivo CSV vacío.'], 422);
            }

            $header = array_map(function ($h) {
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h ?? '');
                return strtolower(trim($h));
            }, $header);

            while (($data = fgetcsv($handle, 2000, $delimiter)) !== false) {
                $row = [];
                foreach ($header as $idx => $col) {
                    $row[$col] = $data[$idx] ?? '';
                }
                $rows[] = $row;
            }
            fclose($handle);
        }

        // Map column aliases
        $catAliases = ['tipo', 'category', 'categoria', 'categoría', 'type', 'categoría plan', 'tipo plan', 'tipo de plan'];
        $nameAliases = ['nombre', 'name', 'plan', 'plan_name', 'nombre del plan'];
        $speedAliases = ['velocidad', 'speed', 'velocidad_mbps', 'mbps', 'velocidad megas'];
        $priceAliases = ['precio', 'price', 'costo', 'cost', 'precio de lista', 'coste'];
        $descAliases = ['descripcion', 'descripción', 'description', 'desc'];

        $findCol = function ($row, $aliases) {
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $row))
                    return $alias;
            }
            return null;
        };

        if (empty($rows)) {
            return response()->json(['message' => 'No se encontraron filas de datos.'], 422);
        }

        $sample = $rows[0];
        $catCol = $findCol($sample, $catAliases);
        $nameCol = $findCol($sample, $nameAliases);
        $speedCol = $findCol($sample, $speedAliases);
        $priceCol = $findCol($sample, $priceAliases);
        $descCol = $findCol($sample, $descAliases);

        if (!$catCol || !$nameCol) {
            return response()->json(['message' => 'El archivo debe tener al menos columnas: tipo/category y nombre/name'], 422);
        }

        $added = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $category = strtoupper(trim($row[$catCol] ?? ''));
            $name = trim($row[$nameCol] ?? '');
            $speedRaw = trim($row[$speedCol] ?? '');
            $priceRaw = trim($row[$priceCol] ?? '0');
            $description = trim($row[$descCol] ?? '');

            if (!$category || !$name) {
                $skipped++;
                continue;
            }

            // Normalize speed: extract numbers only for comparison
            $speedClean = preg_replace('/[^0-9]/', '', $speedRaw);
            $speedLabel = $speedClean ? "{$speedClean} Mbps" : $speedRaw;

            // Normalize price: extract numbers
            $price = floatval(preg_replace('/[^0-9.]/', '', $priceRaw));

            // Duplicate check: same category + name (normalized)
            $exists = Plan::where('category', $category)
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Plan::create([
                'category' => $category,
                'name' => $name,
                'speed' => $speedLabel,
                'price' => $price,
                'description' => $description ?: null,
                'is_active' => true,
            ]);
            $added++;
        }

        return response()->json([
            'message' => "Importación completada: {$added} planes agregados" . ($skipped > 0 ? ", {$skipped} omitidos (duplicados o vacíos)." : ".")
        ]);
    }
}
