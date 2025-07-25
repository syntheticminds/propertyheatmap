<?php

namespace App\Models;

use App\Enums\Resolution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader as CsvReader;
use Location\Bounds;

abstract class Location extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'type' => Resolution::class,
        ];
    }

    /* Scopes */

    public function scopeWithin($query, Bounds $bounds)
    {
        return $query
            ->whereBetween('latitude', [$bounds->getSouth(), $bounds->getNorth()])
            ->whereBetween('longitude', [$bounds->getWest(), $bounds->getEast()]);
    }

    /* Helpers */

    public static function import(string $file, callable $transformer)
    {
        $path = Storage::path($file);

        $reader = CsvReader::createFromPath($path)->setHeaderOffset(0);

        foreach ($reader->getRecords() as $record) {
            $data = $transformer($record);

            if ($data) {
                static::create($data);
            }
        }
    }
}
