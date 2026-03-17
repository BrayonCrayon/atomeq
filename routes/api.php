<?php

use App\Http\Controllers\ElementIndex;
use App\Http\Controllers\ElementShow;
use App\Http\Controllers\ElementStateIndex;
use App\Http\Controllers\TypeIndex;
use Illuminate\Support\Facades\Route;

Route::get('/elements', ElementIndex::class)->name('elements.index');
Route::get('/elements/{element:name}', ElementShow::class)->name('elements.show');
Route::get('/types', TypeIndex::class)->name('types.index');
Route::get('/states', ElementStateIndex::class)->name('states.index');
