<?php

Route::middleware('auth:api')->group(function () {

  Route::post('/logout', [AuthController::class, 'logout']);

  Route::post('/address', [CustomerAddressController::class, 'store']);
  Route::patch('/address/{id}', [CustomerAddressController::class, 'update']);
  Route::delete('/address/{id}', [CustomerAddressController::class, 'destroy']);

  Route::get('/plans', [IspPlanController::class, 'index']);

  Route::post('/subscribe/{plan}', [SubscriptionController::class, 'store']);
  Route::post('/service-status', [SubscriptionController::class, 'status']);

  Route::get('/profile', [CustomerController::class, 'profile']);
  Route::patch('/profile', [CustomerController::class, 'updateProfile']);

  Route::get('/invoices', [InvoiceController::class, 'index']);
  Route::get('/invoices/{id}', [InvoiceController::class, 'show']);

  Route::post('/pay/{invoice}', [PaymentController::class, 'pay']);
  Route::get('/payments', [PaymentController::class, 'index']);

  Route::get('/notifications', [NotificationController::class, 'index']);
  Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});
