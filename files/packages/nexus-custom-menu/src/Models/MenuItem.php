<?php

namespace NexusPlugin\CustomMenu\Models;

use App\Models\NexusModel;
use App\Models\User;
use App\Http\Middleware\Locale;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class MenuItem extends NexusModel
{
    public const TARGET_SELF = '_self';
    public const TARGET_BLANK = '_blank';

    protected $table = 'plugin_custom_menu_items';

    public $timestamps = true;

    protected $fillable = [
        'parent_id',
        'text',
        'url',
        'target',
        'style',
        'sort',
        'min_class',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderByDesc('sort')->orderBy('id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withDefault();
    }

    protected function minClassText(): Attribute
    {
        return Attribute::make(
            get: fn () => User::getClassText($this->min_class)
        );
    }

    protected function text(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->normalizeText($value),
            set: fn ($value) => is_array($value)
                ? json_encode($value, JSON_UNESCAPED_UNICODE)
                : (string) $value,
        );
    }

    protected function displayText(): Attribute
    {
        return Attribute::make(
            get: function () {
                $text = $this->text;

                if (!is_array($text)) {
                    return (string) $text;
                }

                $lang = \function_exists('get_langfolder_cookie') ? \get_langfolder_cookie() : array_key_first(Locale::$languageMaps);

                return Arr::get($text, $lang)
                    ?: Arr::first($text, fn ($value) => $value !== null && $value !== '')
                    ?: '';
            }
        );
    }

    public static function targetOptions(): array
    {
        return [
            self::TARGET_SELF => '_self',
            self::TARGET_BLANK => '_blank',
        ];
    }

    public static function parentOptions(?int $excludeId = null): array
    {
        return self::query()
            ->when($excludeId, fn ($query) => $query->where('id', '<>', $excludeId))
            ->orderByDesc('sort')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (self $item) => [$item->id => $item->display_text])
            ->prepend('--', 0)
            ->toArray();
    }

    private function normalizeText($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = is_string($value) ? json_decode($value, true) : null;

        if (is_array($decoded)) {
            return $decoded;
        }

        $result = [];

        foreach (array_keys(Locale::$languageMaps) as $lang) {
            $result[$lang] = (string) $value;
        }

        return $result;
    }
}
