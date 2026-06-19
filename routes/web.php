<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProfileController;
use App\Mail\TestEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/ops/test-email', function (Request $request) {
    $expectedToken = config('services.test_email.token');

    abort_unless($expectedToken, 404);
    abort_unless(hash_equals($expectedToken, (string) $request->query('token')), 403);

    $to = $request->query('to', config('services.test_email.to'));

    abort_unless(filter_var($to, FILTER_VALIDATE_EMAIL), 422, 'Debes indicar un email valido en el parametro to.');

    try {
        Mail::to($to)->send(new TestEmail);
    } catch (Throwable $exception) {
        report($exception);

        return response()->json([
            'ok' => false,
            'message' => 'No se pudo enviar el correo. Revisa los logs de Render para ver el detalle.',
            'error' => $exception->getMessage(),
        ], 500);
    }

    return response()->json([
        'ok' => true,
        'message' => 'Correo de prueba enviado.',
        'to' => $to,
    ]);
})->name('ops.test-email');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
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
