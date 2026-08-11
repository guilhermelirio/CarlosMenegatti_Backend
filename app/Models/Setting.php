<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasUlids;

    protected $fillable = ['key', 'value'];

    public const DEFAULT_MONTHLY_FEE_CENTS = 'default_monthly_fee_cents';

    public const DEFAULT_DAILY_FEE_CENTS = 'default_daily_fee_cents';

    public const MONTHLY_FEE_DUE_DAY = 'monthly_fee_due_day';

    public const PIX_KEY = 'pix_key';

    public const PIX_KEY_TYPE = 'pix_key_type';

    public const PIX_RECEIVER_NAME = 'pix_receiver_name';

    public const PIX_CITY = 'pix_city';

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever(
            "setting:{$key}",
            fn () => static::query()->where('key', $key)->value('value') ?? $default,
        );
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $value = static::get($key);

        return $value === null ? $default : (int) $value;
    }

    public static function set(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => Cache::forget("setting:{$setting->key}"));
        static::deleted(fn (Setting $setting) => Cache::forget("setting:{$setting->key}"));
    }
}
