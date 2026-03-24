<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Health check endpoint for Railway
Route::get('/up', fn() => response()->json(['status' => 'ok']));

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Facebook OAuth — only admin and super-admin
Route::middleware(['auth', 'verified', 'role:super-admin,admin', 'throttle:oauth'])->group(function () {
    Route::get('/facebook/connect', [\App\Http\Controllers\FacebookController::class, 'redirect'])
        ->name('facebook.connect');
    Route::get('/facebook/callback', [\App\Http\Controllers\FacebookController::class, 'callback'])
        ->name('facebook.callback');
});

// Admin routes
Route::middleware(['auth', 'verified', 'role:super-admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('pages', \App\Http\Controllers\Admin\PageController::class)
        ->only(['index', 'destroy']);
    Route::get('audit-log', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])
        ->name('audit-log.index');
});

// Publisher routes — all authenticated roles
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/publisher', [\App\Http\Controllers\PublisherController::class, 'index'])
        ->name('publisher.index');
    Route::get('/publisher/create', [\App\Http\Controllers\PublisherController::class, 'create'])
        ->name('publisher.create');
    Route::post('/publisher', [\App\Http\Controllers\PublisherController::class, 'store'])
        ->name('publisher.store');
});

require __DIR__.'/auth.php';
