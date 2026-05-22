<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property string|null $value
 */
class ApplicationSetting extends Model
{
    public const COMPLEMENT_LINK_VALIDITY_DAYS = 'complement_link_validity_days';

    public const DEFAULT_COMPLEMENT_LINK_VALIDITY_DAYS = 3;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function complementLinkValidityDays(): int
    {
        $value = self::query()
            ->where('key', self::COMPLEMENT_LINK_VALIDITY_DAYS)
            ->value('value');

        return (int) ($value ?: self::DEFAULT_COMPLEMENT_LINK_VALIDITY_DAYS);
    }

    public static function setComplementLinkValidityDays(int $days): void
    {
        self::query()->updateOrCreate(
            ['key' => self::COMPLEMENT_LINK_VALIDITY_DAYS],
            ['value' => (string) $days]
        );
    }
}
