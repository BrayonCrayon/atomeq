<?php

use App\Http\Controllers\ElementIndex;
use App\Http\Controllers\ElementShow;
use Illuminate\Support\Facades\Route;

Route::get('/elements', ElementIndex::class)->name('elements.index');
Route::get('/elements/{element}', ElementShow::class)->name('elements.show');
