<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class Download extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!Storage::exists('areas.csv')) {
            $this->info('Downloading areas');

            Http::sink(Storage::path('areas.csv'))
                ->get('https://www.doogal.co.uk/UKPostcodesSummaryCSV/');
        }

        if (!Storage::exists('districts.csv')) {
            $this->info('Downloading districts');

            Http::sink(Storage::path('districts.csv'))
                ->get('https://www.doogal.co.uk/PostcodeDistrictsCSV/');
        }

        if (!Storage::exists('sectors.csv')) {
            $this->info('Downloading sectors');

            Http::sink(Storage::path('sectors.csv'))
                ->get('https://www.doogal.co.uk/PostcodeSectorsCSV/');
        }

        if (!Storage::exists('postcodes.csv')) {
            $this->info('Downloading postcodes');

            Http::sink(Storage::path('postcodes.zip'))
                ->get('https://www.doogal.co.uk/files/postcodes.zip');

            $path = Storage::path('postcodes.zip');

            $this->info('Extracting postcodes');

            $zip = new ZipArchive();
            $zip->open($path);

            $filename = $originalFileName = $zip->getNameIndex(0);

            $zip->extractTo(Storage::path('/'));
            $zip->close();

            Storage::delete('postcodes.zip');
        }

        if (Storage::exists('sales.csv')) {
            $our_last_modified = Storage::lastModified('sales.csv');

            $their_last_modified = Http::head('http://prod2.publicdata.landregistry.gov.uk.s3-website-eu-west-1.amazonaws.com/pp-complete.csv')->header('Last-Modified');

            if (Carbon::parse($our_last_modified) < Carbon::parse($their_last_modified)) {
                Storage::delete('sales.csv');
            }
        }

        if (!Storage::exists('sales.csv')) {
            $this->info('Downloading sales');

            Http::timeout(60 * 60)->sink(Storage::path('sales.csv'))
                ->get('http://prod2.publicdata.landregistry.gov.uk.s3-website-eu-west-1.amazonaws.com/pp-complete.csv');
        }
    }
}
