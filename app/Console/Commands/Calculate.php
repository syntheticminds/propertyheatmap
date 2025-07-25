<?php

namespace App\Console\Commands;

use App\Enums\Resolution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Calculate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculates statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Calculating areas');

        DB::update("
            UPDATE areas
            SET
                (five_year_count, five_year_average, five_year_standard_deviation) = (
                    SELECT
                        COUNT(*),
                        ROUND(AVG(sales.price)),
                        ROUND(SQRT(AVG(sales.price * sales.price) - (AVG(sales.price) * AVG(sales.price))))
                    FROM districts
                        JOIN sectors ON sectors.district_id = districts.id
                        JOIN postcodes ON postcodes.sector_id = sectors.id
                        JOIN sales ON sales.postcode = postcodes.id
                    WHERE
                        districts.area_id = areas.id
                        AND sales.completed_at >= DATE('now', '-5 years')
                );
        ");

        $this->info('Calculating districts');

        DB::update("
            UPDATE districts
            SET
                (five_year_count, five_year_average, five_year_standard_deviation) = (
                    SELECT
                        COUNT(*),
                        ROUND(AVG(sales.price)),
                        ROUND(SQRT(AVG(sales.price * sales.price) - (AVG(sales.price) * AVG(sales.price))))
                    FROM sectors
                        JOIN postcodes ON postcodes.sector_id = sectors.id
                        JOIN sales ON sales.postcode = postcodes.id
                    WHERE
                        sectors.district_id = districts.id
                        AND sales.completed_at >= DATE('now', '-5 years')
                );
        ");

        $this->info('Calculating sectors');

        DB::update("
            UPDATE sectors
            SET
                (five_year_count, five_year_average, five_year_standard_deviation) = (
                    SELECT
                        COUNT(*),
                        ROUND(AVG(sales.price)),
                        ROUND(SQRT(AVG(sales.price * sales.price) - (AVG(sales.price) * AVG(sales.price))))
                    FROM postcodes
                        JOIN sales ON sales.postcode = postcodes.id
                    WHERE
                        postcodes.sector_id = sectors.id
                        AND sales.completed_at >= DATE('now', '-5 years')
                );
        ");

        $this->info('Calculating postcodes');

        DB::update("
            UPDATE postcodes
            SET
                (five_year_count, five_year_average, five_year_standard_deviation) = (
                    SELECT
                        COUNT(*),
                        ROUND(AVG(sales.price)),
                        ROUND(SQRT(AVG(sales.price * sales.price) - (AVG(sales.price) * AVG(sales.price))))
                    FROM sales
                    WHERE
                        sales.postcode = postcodes.id
                        AND sales.completed_at >= DATE('now', '-5 years')
                );
        ");
    }
}
