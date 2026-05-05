<?php

namespace Database\Seeders;

use App\Models\CategorieSocioprofessionnelle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorieSocioprofessionnelleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usedCodes = [];

        foreach ($this->categories() as $index => $libelle) {
            CategorieSocioprofessionnelle::updateOrCreate(
                ['libelle' => $libelle],
                [
                    'code' => $this->uniqueCodeFor($libelle, $usedCodes),
                    'ordre' => $index + 1,
                ]
            );
        }
    }

    /**
     * @return list<string>
     */
    private function categories(): array
    {
        return $this->readCsvColumn(__DIR__.'/data/categories_socioprofessionnelles.csv', 'Catégorie professionnelle');
    }

    /**
     * @param  array<string, true>  $usedCodes
     */
    private function uniqueCodeFor(string $libelle, array &$usedCodes): string
    {
        $baseCode = $this->baseCodeFor($libelle);
        $code = $baseCode;
        $suffix = 2;

        while (isset($usedCodes[$code])) {
            $code = Str::limit($baseCode, 95, '').'_'.$suffix;
            $suffix++;
        }

        $usedCodes[$code] = true;

        return $code;
    }

    private function baseCodeFor(string $libelle): string
    {
        return Str::of($libelle)
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
