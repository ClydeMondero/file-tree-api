<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TreeController;


Route::get("/tree", [TreeController::class, 'getFileTree']);
