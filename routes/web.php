<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api', function () {
    return redirect('/docs');
});

Route::get('/api/docs', function () {
    return redirect('/docs');
});


