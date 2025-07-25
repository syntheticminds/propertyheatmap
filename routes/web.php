<?php

use Illuminate\Support\Facades\Route;

Route::view('/{any?}', 'app')->middleware('web')->where('any', '.*');
