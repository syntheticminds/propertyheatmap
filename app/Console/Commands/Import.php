<?php

namespace App\Console\Commands;

use App\Enums\Resolution;
use App\Models\Area;
use App\Models\District;
use App\Models\Location;
use App\Models\Postcode;
use App\Models\Sector;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader as CsvReader;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Resetting database');

        $this->callSilent('migrate:fresh', ['--force']);

        $this->info('Importing areas');

        Area::import('areas.csv', function ($record) {
            if ($record['Population'] < 1 || in_array($record['Postcode area'], ['AB', 'BT', 'DD', 'DG', 'EH', 'FK', 'G', 'HS', 'IV', 'KA', 'KY', 'KW', 'ML', 'PA', 'PH', 'TD', 'ZE'])) {
                return;
            }

            return [
                'id' => $record['Postcode area'],
                'description' => $record['Area covered'],
                'latitude' => (float) $record['Latitude'],
                'longitude' => (float) $record['Longitude'],
            ];
        });

        $this->info('Importing districts');

        District::import('districts.csv', function ($record) {
            if ($record['Active postcodes'] < 1 || $record['Population'] < 1) {
                return;
            }

            return [
                'id' => $record['Postcode'],
                'area_id' => static::parsePostcode($record['Postcode'])['area'],
                'description' => $record['Town/Area'],
                'latitude' => (float) $record['Latitude'],
                'longitude' => (float) $record['Longitude'],
            ];
        });

        $this->info('Importing sectors');

        Sector::import('sectors.csv', function ($record) {
            if ($record['Active postcodes'] < 1 || $record['Population'] < 1) {
                return;
            }

            return [
                'id' => $record['Postcode'],
                'district_id' => static::parsePostcode($record['Postcode'])['district'],
                'description' => $record['Built up area'],
                'latitude' => (float) $record['Latitude'],
                'longitude' => (float) $record['Longitude'],
            ];
        });

        $this->info('Importing postcodes');

        Postcode::withoutIndexes(function (Builder $builder) {
            $path = Storage::path('postcodes.csv');

            $row_count = Process::run('wc -l < ' . $path)->throw()->output() - 1;

            $chunks = CsvReader::createFromPath($path)
                ->setHeaderOffset(0)
                ->chunkBy(1000);

            $progress_bar = $this->output->createProgressBar($row_count);

            foreach ($chunks as $rows) {
                $records = [];

                foreach ($rows as $row) {
                    if ($row['In Use?'] !== 'Yes' || !in_array($row['Country'], ['England', 'Wales']) || $row['Quality'] > 6 || $row['Latitude'] === '' || $row['Longitude'] === '' || $row['Latitude'] === 0) {
                        continue;
                    }

                    $records[] = [
                        'id' => $row['Postcode'],
                        'sector_id' => static::parsePostcode($row['Postcode'])['sector'],
                        'latitude' => (float) $row['Latitude'],
                        'longitude' => (float) $row['Longitude'],
                        // 'altitude' => $row['Altitude'] !== '' ? (int) $row['Altitude'] : null,
                        // 'train_station_km' => (int) $row['Distance to station'],
                        // 'sea_km' => (int) $row['Distance to sea'],
                        // 'is_national_park' => !empty($row['National Park']),
                        // 'is_rural' => str_contains(strtolower($row['Rural/urban']), 'rural'),
                        // 'population' => $row['Population'] !== '' ? (int) $row['Population'] : null,
                        // 'households' => $row['Households'] !== '' ? (int) $row['Households'] : null,
                        // 'deprevation_index' => (int) $row['Index of Multiple Deprivation'],
                        // 'average_income' => (int) $row['Average Income'],
                    ];
                }

                $builder->insert($records);

                $progress_bar->advance(count($rows));
            }

            $progress_bar->finish();
            $this->newLine();
        });

        $this->info('Importing sales');

        Sale::withoutIndexes(function (Builder $builder) {
            $path = Storage::path('sales.csv');

            $row_count = Process::run('wc -l < ' . $path)->throw()->output();

            $chunks = CsvReader::createFromPath($path)
                ->mapHeader(['identifier', 'price', 'completed_at', 'postcode', 'type', 'is_new_build', 'tenure', 'paon', 'saon', 'street', 'locality', 'town', 'district', 'county', 'sale_type', 'record_status'])
                ->chunkBy(1000);

            $progress_bar = $this->output->createProgressBar($row_count);

            $address_formatter = function ($record) {
                $address = ucwords(strtolower(value(function () use ($record) {
                    if (!$record['saon'] && !$record['street']) {
                        return $record['paon'];
                    }

                    if ($record['saon']) {
                        if (is_numeric($record['saon'])) {
                            return implode(', ', array_filter([
                                $record['saon'] . ' ' . $record['paon'],
                                $record['street'],
                            ]));
                        }

                        $parts_of_paon = explode(',', $record['paon']) ?? [$record['paon']];
                        $last_part_of_paon = trim(end($parts_of_paon));

                        if (preg_match('/\d/', $last_part_of_paon)) {
                            return $record['saon'] . ', ' . $record['paon'] . ' ' . $record['street'];
                        }

                        return implode(', ', array_filter([
                            $record['saon'],
                            $record['paon'],
                            $record['street'],
                        ]));
                    }

                    if (!preg_match('/\d/', $record['paon'])) {
                        return $record['paon'] . ', ' . $record['street'];
                    }

                    return $record['paon'] . ' ' . $record['street'];
                })), ' -');

                // TODO: Maybe merge the ucwords(strtolower()) with the exploded string stuff below?

                $words = explode(' ', $address);

                foreach ($words as &$word) {
                    if (str_starts_with($word, 'Mc') && strlen($word) > 2) {
                        // TODO: Repair addresses where the Mc is separate from the given name. For instance: Mc Kenzie House, 19 Barleycroft Road [Clue: switch to a for loop so you can skip stuff]

                        $word[2] = strtoupper($word[2]);
                    }
                }

                return implode(' ', $words);
            };

            foreach ($chunks as $records) {
                $rows = [];

                foreach ($records as $record) {
                    if (!$record['postcode'] || $record['sale_type'] !== 'A' || $record['record_status'] !== 'A') {
                        continue;
                    }

                    $rows[] = [
                        'postcode' => $record['postcode'],
                        'address' => $address_formatter($record),
                        'price' => $record['price'],
                        'completed_at' => strtok($record['completed_at'], ' '),
                        'type' => match ($record['type']) {
                            'D' => 'detached',
                            'S' => 'semi_detached',
                            'T' => 'terraced',
                            'F' => 'flat',
                            default => 'other',
                        },
                        'is_new_build' => $record['is_new_build'] === 'Y',
                        'is_freehold' => $record['tenure'] === 'F',
                    ];
                }

                $builder->insert($rows);

                $progress_bar->advance(count($rows));
            }

            $progress_bar->finish();
            $this->newLine();
        });

        /* Pruning */

        $this->info('Pruning districts');

        District::query()
            ->leftJoin('areas', 'areas.id', 'districts.area_id')
            ->whereNull('areas.id')
            ->delete();

        $this->info('Pruning sectors');

        Sector::query()
            ->leftJoin('districts', 'districts.id', 'sectors.district_id')
            ->whereNull('districts.id')
            ->delete();

        $this->info('Pruning postcodes');

        Postcode::query()
            ->leftJoin('sectors', 'sectors.id', 'postcodes.sector_id')
            ->whereNull('sectors.id')
            ->delete();

        $this->info('Pruning sales');

        Sale::query()
            ->leftJoin('postcodes', 'postcodes.id', 'sales.postcode')
            ->whereNull('postcodes.id')
            ->delete();
    }

    private static function parsePostcode(string $input): array
    {
        $district = $input;
        $sector = null;

        if (str_contains($input, ' ')) {
            [$outward, $inward] = explode(' ', $input);

            $district = $outward;
            $sector = $outward . ' ' . substr($inward, 0, 1);
        }

        $area = substr($district, 0, 2);

        if (!ctype_alpha($area)) {
            $area = substr($district, 0, 1);
        }

        return [
            'area'     => $area,
            'district' => $district,
            'sector'   => $sector,
        ];
    }
}
