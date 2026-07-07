<?php

  use App\Http\Controllers\Api\AuthController;
  use App\Http\Controllers\Api\CustomerAddressController;
  use App\Http\Controllers\Api\IspPlanController;
  use App\Http\Controllers\Api\SubscriptionController;
  use App\Http\Controllers\Api\InvoiceController;
  use App\Http\Controllers\Api\CustomerController;
  use App\Http\Controllers\Api\PaymentController;
  use App\Http\Controllers\Api\NotificationController;


  Route::get('/plans', [IspPlanController::class, 'index']);

  Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/address', [CustomerAddressController::class, 'store']);
    Route::patch('/address/{id}', [CustomerAddressController::class, 'update']);
    Route::delete('/address/{id}', [CustomerAddressController::class, 'destroy']);

    Route::post('/subscribe/{plan}', [SubscriptionController::class, 'store']);
    Route::post('/service-status', [SubscriptionController::class, 'status']);

    Route::get('/profile', [CustomerController::class, 'profile']);
    Route::patch('/profile', [CustomerController::class, 'updateProfile']);

    Route::get('/service-areas', [ServiceAreaController::class, 'viewAreas']);

    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);

    Route::post('/pay/{invoice}', [PaymentController::class, 'pay']);
    Route::get('/payments', [PaymentController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
  });
