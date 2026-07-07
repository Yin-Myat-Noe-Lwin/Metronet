<?php

  use App\Http\Controllers\Api\AuthController;
  use App\Http\Controllers\Api\CustomerAddressController;
  use App\Http\Controllers\Api\IspPlanController;
  use App\Http\Controllers\Api\SubscriptionController;
  use App\Http\Controllers\Api\InvoiceController;
  use App\Http\Controllers\Api\CustomerController;
  use App\Http\Controllers\Api\PaymentController;
  use App\Http\Controllers\Api\NotificationController;
  use App\Http\Controllers\Api\CpeController;
  use App\Http\Controllers\Api\ServiceAreaController;

  Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::get('/admin/customers', [CustomerController::class, 'index']);
    Route::get('/admin/customers/{id}', [CustomerController::class, 'show']);
    Route::delete('/admin/customers/{id}', [CustomerController::class, 'destroy']);

    Route::get('/addresses', [CustomerAddressController::class, 'index']);

    Route::get('/admin/plans', [IspPlanController::class, 'index']);
    Route::post('/admin/plans', [IspPlanController::class, 'store']);
    Route::patch('/admin/plans/{id}', [IspPlanController::class, 'update']);
    Route::delete('/admin/plans/{id}', [IspPlanController::class, 'destroy']);

    Route::get('/admin/subscriptions', [SubscriptionController::class, 'index']);

    Route::get('/admin/invoices', [InvoiceController::class, 'index']);
    Route::get('/admin/invoices/{id}', [InvoiceController::class, 'show']);

    Route::get('/admin/payments', [PaymentController::class, 'viewPayments']);

    Route::get('/admin/cpes', [CpeController::class, 'index']);
    Route::post('/admin/cpes', [CpeController::class, 'store']);
    Route::patch('/admin/cpes/{id}', [CpeController::class, 'update']);
    Route::delete('/admin/cpes/{id}', [CpeController::class, 'destroy']);

    Route::get('/admin/service-areas', [ServiceAreaController::class, 'index']);
    Route::post('/admin/service-areas', [ServiceAreaController::class, 'store']);
    Route::patch('/admin/service-areas/{id}', [ServiceAreaController::class, 'update']);
    Route::delete('/admin/service-areas/{id}', [ServiceAreaController::class, 'destroy']);
  });
