<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['message' => 'Eatery API is running!'];
});

Route::get('/test', function () {
    return ['message' => 'Test route working!'];
});
