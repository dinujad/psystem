<?php

Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu')->prefix('inventory-management')->group(function () {
    Route::get('dashboard', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'index']);

    // Install/update routes used by Manage Modules page
    Route::get('install', [\Modules\InventoryManagement\Http\Controllers\InstallController::class, 'index']);
    Route::post('install', [\Modules\InventoryManagement\Http\Controllers\InstallController::class, 'install']);
    Route::get('install/uninstall', [\Modules\InventoryManagement\Http\Controllers\InstallController::class, 'uninstall']);
    Route::get('install/update', [\Modules\InventoryManagement\Http\Controllers\InstallController::class, 'update']);

    // Core inventory routes
    Route::get('/', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'index']);
    Route::post('create', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'createNewInventory']);
    Route::get('list', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'showInventoryList']);
    Route::get('make/{id}', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'makeInevtory']);
    Route::post('update-status/{id}', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'updateStatus']);

    Route::get('products/{id}', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'getProducts']);
    Route::post('product-row', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'getPurchaseEntryRow']);
    Route::post('save-products', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'saveInventoryProducts']);
    Route::post('update-product-qty', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'updateProductQuantity']);
    Route::post('product-data', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'getProductData']);

    Route::get('report/{id}/{branch_id}', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'showInventoryReports']);
    Route::get('report/increase/{inventory_id}/{branch_id}', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'inventoryIncreaseReports']);
    Route::get('report/decrease/{inventory_id}/{branch_id}', [\Modules\InventoryManagement\Http\Controllers\InventoryManagementController::class, 'inventoryDisabilityReports']);
});
