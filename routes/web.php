<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\WordToPdfController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::post('/convert-word-to-pdf', [WordToPdfController::class, 'convert'])->name('word-to-pdf.convert');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
