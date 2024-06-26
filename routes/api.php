<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailChimpController;


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/getRecords', [MailChimpController::class, 'getRecords']);
    Route::post('/getOpenDetails', [MailChimpController::class, 'getOpenDetails']);
});
