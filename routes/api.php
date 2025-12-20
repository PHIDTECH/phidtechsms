<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactImportController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Contact API routes
    Route::prefix('contacts')->name('api.contacts.')->group(function () {
        // Contact CRUD - View permissions
        Route::middleware(['contact.permission:view-contacts'])->group(function () {
            Route::get('/', [ContactController::class, 'index'])->name('index');
            Route::get('/{contact}', [ContactController::class, 'show'])->whereNumber('contact')->name('show');
        });
        
        // Contact CRUD - Manage permissions
        Route::middleware(['contact.permission:manage-contacts'])->group(function () {
            Route::post('/', [ContactController::class, 'store'])->name('store');
            Route::put('/{contact}', [ContactController::class, 'update'])->name('update');
            Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
        });
        
        // Contact Export - Export permissions
        Route::middleware(['contact.permission:export-contacts'])->group(function () {
            Route::get('/export/csv', [ContactController::class, 'export'])->name('export');
        });
        
        // Contact Groups - View permissions
        Route::middleware(['contact.permission:view-contacts'])->prefix('groups')->name('groups.')->group(function () {
            Route::get('/', [ContactController::class, 'getGroups'])->name('index');
        });
        
        // Contact Groups - Group management permissions
        Route::middleware(['contact.permission:manage-contact-groups'])->prefix('groups')->name('groups.')->group(function () {
            Route::post('/', [ContactController::class, 'storeGroup'])->name('store');
            Route::put('/{group}', [ContactController::class, 'updateGroup'])->name('update');
            Route::delete('/{group}', [ContactController::class, 'destroyGroup'])->name('destroy');
        });
        
        // Contact Import - Available to all authenticated users
        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/', [ContactImportController::class, 'index'])->name('index');
            Route::post('/upload', [ContactImportController::class, 'uploadFile'])->name('upload');
            Route::post('/mapping', [ContactImportController::class, 'processMapping'])->name('mapping');
            Route::get('/groups', [ContactImportController::class, 'getContactGroups'])->name('groups');
            Route::post('/process', [ContactImportController::class, 'processImport'])->name('process');
            Route::get('/{import}/status', [ContactImportController::class, 'getImportStatus'])->name('status');
            Route::get('/{import}/errors', [ContactImportController::class, 'downloadErrorReport'])->name('errors');
        });
        
        // Contact Group Creation in Import - Group management permissions
        Route::middleware(['contact.permission:manage-contact-groups'])->prefix('import')->name('import.')->group(function () {
            Route::post('/groups', [ContactImportController::class, 'createContactGroup'])->name('groups.create');
        });
    });
    
    // Legacy addressbook routes (if needed for backward compatibility)
    Route::get('/addressbooks', [\App\Http\Controllers\Contacts\AddressbookController::class, 'index']);
    Route::post('/addressbooks', [\App\Http\Controllers\Contacts\AddressbookController::class, 'store']);
    Route::put('/addressbooks/{id}', [\App\Http\Controllers\Contacts\AddressbookController::class, 'update']);
    Route::delete('/addressbooks/{id}', [\App\Http\Controllers\Contacts\AddressbookController::class, 'destroy']);
});
