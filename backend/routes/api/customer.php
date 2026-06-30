<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\IspPlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;

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

  Route::get('/invoices', [InvoiceController::class, 'index']);
  Route::get('/invoices/{id}', [InvoiceController::class, 'show']);

  Route::post('/pay/{invoice}', [PaymentController::class, 'pay']);
  Route::get('/payments', [PaymentController::class, 'index']);

  Route::get('/notifications', [NotificationController::class, 'index']);
  Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});
