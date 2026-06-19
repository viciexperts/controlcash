<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/expenses/export/{format}', [ExpenseController::class, 'export'])->name('expenses.export');
    Route::post('/expenses/{expense}/comments', [ExpenseController::class, 'comment'])->name('expenses.comments.store');
    Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
    Route::resource('groups', GroupController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember'])->name('groups.members.store');
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('groups.members.destroy');
    Route::post('/groups/{group}/settlements', [GroupController::class, 'settle'])->name('groups.settlements.store');
});

require __DIR__.'/auth.php';
