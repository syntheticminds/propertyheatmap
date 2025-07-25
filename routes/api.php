<?php

use App\Models\Area;
use App\Models\District;
use App\Models\Postcode;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Location\Bounds;
use Location\Coordinate;

Route::post('/query', function (Request $request) {
    $validated = $request->validate([
        'north' => ['required', 'numeric', 'between:-90,90', 'gt:south'],
        'east' => ['required', 'numeric', 'between:-180,180', 'gt:west'],
        'south' => ['required', 'numeric', 'between:-90,90', 'lt:north'],
        'west' => ['required', 'numeric', 'between:-180,180', 'lt:east'],
    ]);

    $north_west = new Coordinate($validated['north'], $validated['west']);
    $south_east = new Coordinate($validated['south'], $validated['east']);

    $bounds = new Bounds($north_west, $south_east);

    $results = Postcode::within($bounds)->whereNotNull('five_year_average')->limit(501)->get();

    if ($results->count() <= 500) {
        return $results;
    }

    $results = Sector::within($bounds)->whereNotNull('five_year_average')->limit(501)->get();

    if ($results->count() <= 500) {
        return $results;
    }

    $results = District::within($bounds)->whereNotNull('five_year_average')->limit(501)->get();

    if ($results->count() <= 500) {
        return $results;
    }
    
    return Area::within($bounds)->whereNotNull('five_year_average')->limit(501)->get();
});
