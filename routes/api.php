<?php

use App\Http\Controllers\ElementIndex;
use Illuminate\Support\Facades\Route;

Route::get('/elements', ElementIndex::class)->name('elements.index');
