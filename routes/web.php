<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FillTextController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\TestFillTextController;
use App\Http\Controllers\MappingController;
use App\Http\Controllers\ResetCenterController;
use App\Http\Controllers\RunController;
use App\Http\Controllers\TextReferenceController;
use App\Http\Controllers\ValidatorController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Run extraction
Route::prefix('run')->name('run.')->group(function () {
    Route::get('/', [RunController::class, 'showForm'])->name('form');
    Route::post('/execute', [RunController::class, 'execute'])->name('execute');
    Route::get('/profiles/{entity}', [RunController::class, 'getProfiles'])->name('profiles');
    Route::get('/show/{history}', [RunController::class, 'show'])->name('show');
    Route::get('/download/{history}', [RunController::class, 'download'])->name('download');
    Route::get('/history', [RunController::class, 'history'])->name('history');
    Route::get('/health', [RunController::class, 'checkHealth'])->name('health');
});

// Mapping editor
Route::prefix('mapping')->name('mapping.')->group(function () {
    Route::get('/', [MappingController::class, 'index'])->name('index');
    Route::get('/profile/{profile}', [MappingController::class, 'showProfile'])->name('profile');
    Route::get('/profile/{profile}/duplicate', [MappingController::class, 'duplicateForm'])->name('duplicate.form');
    Route::post('/profile/{profile}/duplicate', [MappingController::class, 'duplicate'])->name('duplicate');
    Route::delete('/profile/{profile}', [MappingController::class, 'destroyProfile'])->name('profile.destroy');
    Route::get('/profile/{profile}/add', [MappingController::class, 'createMapping'])->name('row.create');
    Route::post('/profile/{profile}/add', [MappingController::class, 'storeMapping'])->name('row.store');
    Route::get('/{mapping}/edit', [MappingController::class, 'editMapping'])->name('row.edit');
    Route::put('/{mapping}', [MappingController::class, 'updateMapping'])->name('row.update');
    Route::delete('/{mapping}', [MappingController::class, 'destroyMapping'])->name('row.destroy');
});

// Validator
Route::prefix('validator')->name('validator.')->group(function () {
    Route::get('/', [ValidatorController::class, 'showForm'])->name('form');
    Route::post('/run', [ValidatorController::class, 'runValidation'])->name('run');
});

// Fill Text
Route::prefix('fill-text')->name('fill_text.')->group(function () {
    Route::get('/', [FillTextController::class, 'showForm'])->name('form');
    Route::post('/run', [FillTextController::class, 'runFill'])->name('run');
    Route::get('/result-view/{history}', [FillTextController::class, 'resultView'])->name('result_view');
    Route::get('/show/{history}', [FillTextController::class, 'show'])->name('show');
    Route::post('/{history}/save-manual', [FillTextController::class, 'saveManual'])->name('save_manual');
    Route::get('/download/{history}', [FillTextController::class, 'download'])->name('download');
});

// Text References
Route::prefix('text-references')->name('text_references.')->group(function () {
    Route::get('/', [TextReferenceController::class, 'index'])->name('index');
    Route::get('/create', [TextReferenceController::class, 'create'])->name('create');
    Route::post('/', [TextReferenceController::class, 'store'])->name('store');
    Route::get('/{reference}/edit', [TextReferenceController::class, 'edit'])->name('edit');
    Route::put('/{reference}', [TextReferenceController::class, 'update'])->name('update');
    Route::delete('/{reference}', [TextReferenceController::class, 'destroy'])->name('destroy');
});

// Reset Center (dengan PIN gate)
Route::prefix('reset-center')->name('reset_center.')->group(function () {
    Route::get('/', [ResetCenterController::class, 'index'])->name('index');
    Route::post('/verify-pin', [ResetCenterController::class, 'verifyPin'])->name('verify_pin');
    Route::post('/logout', [ResetCenterController::class, 'logout'])->name('logout');
    Route::post('/reset/{section}', [ResetCenterController::class, 'resetSection'])->name('reset_section');
    Route::post('/reset-all', [ResetCenterController::class, 'resetAll'])->name('reset_all');
});

// Test Fill Text (experimental page)
Route::prefix('test-fill-text')->name('test_fill_text.')->group(function () {
    Route::get('/', [TestFillTextController::class, 'showForm'])->name('form');
    Route::get('/result', [TestFillTextController::class, 'showResult'])->name('result');
    Route::post('/process', [TestFillTextController::class, 'process'])->name('process');
    Route::post('/apply', [TestFillTextController::class, 'apply'])->name('apply');
});

// Help & Documentation
Route::get('/help', [HelpController::class, 'index'])->name('help.index');