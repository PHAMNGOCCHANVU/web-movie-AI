<?php

use Illuminate\Support\Facades\Route;

// Route cho admin: Bất kỳ thứ gì bắt đầu bằng /admin sẽ load view 'admin'
Route::get('/admin/{any}', function () {
    return view('admin');
})->where('any', '.*');

// Route cho người dùng: Mọi request còn lại (không phải admin) load 'welcome'
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');