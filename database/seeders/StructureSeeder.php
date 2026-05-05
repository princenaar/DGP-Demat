<?php

namespace Database\Seeders;

use App\Models\Structure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StructureSeeder extends Seeder
{
    public function run(): void
    {
        $usedCodes = [];

        foreach ($this->structures() as $nom) {
            Structure::updateOrCreate(
                ['nom' => $nom],
                ['code' => $this->uniqueCodeFor($nom, $usedCodes)]
            );
        }
    }

    /**
     * @return list<string>
     */
    private function structures(): array
    {
        return $this->readCsvColumn(__DIR__.'/data/structures.csv', 'Structure');
    }

    /**
     * @param  array<string, true>  $usedCodes
     */
    private function uniqueCodeFor(string $nom, array &$usedCodes): string
    {
        $baseCode = $this->baseCodeFor($nom);
        $code = $baseCode;
        $suffix = 2;

        while (isset($usedCodes[$code])) {
            $code = Str::limit($baseCode, 95, '').'_'.$suffix;
            $suffix++;
        }

        $usedCodes[$code] = true;

        return $code;
    }

    private function baseCodeFor(string $nom): string
    {
        return Str::of($nom)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->limit(100, '')
            ->value();
    }

    /**
     * @return list<string>
     */
    private function readCsvColumn(string $path, string $column): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        $header = array_map(
            fn (string $value): string => preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value,
            fgetcsv($handle) ?: []
        );
        $columnIndex = array_search($column, $header ?: [], true);

        if ($columnIndex === false) {
            fclose($handle);

            return [];
        }

        $values = [];

        while (($row = fgetcsv($handle)) !== false) {
            $value = trim((string) ($row[$columnIndex] ?? ''));

            if ($value !== '') {
                $values[] = $value;
            }
        }

        fclose($handle);

        return array_values(array_unique($values));
    }
}
