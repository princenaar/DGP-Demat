<?php

namespace App\Enums;

enum DemandeStatut: string
{
    case Etatique = 'étatique';
    case Contractuel = 'contractuel';
    case Externe = 'externe';

    public static function normalise(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof self) {
            return $value->value;
        }

        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return match ($value) {
            'etatique' => self::Etatique->value,
            default => $value,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $statut): string => $statut->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Etatique => 'Étatique',
            self::Contractuel => 'Contractuel',
            self::Externe => 'Externe',
        };
    }
}
