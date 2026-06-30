<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\IspPlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CpeController;
use App\Http\Controllers\ServiceAreaController;

Route::middleware(['auth:api', 'admin'])->group(function () {
  Route::post('/logout', [AuthController::class, 'logout']);

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

  Route::get('/admin/payments', [PaymentController::class, 'index']);

  Route::get('/admin/cpes', [CpeController::class, 'index']);
  Route::post('/admin/cpes', [CpeController::class, 'store']);
  Route::patch('/admin/cpes/{id}', [CpeController::class, 'update']);
  Route::delete('/admin/cpes/{id}', [CpeController::class, 'destroy']);

  Route::get('/admin/service-areas', [ServiceAreaController::class, 'index']);
  Route::post('/admin/service-areas', [ServiceAreaController::class, 'store']);
  Route::patch('/admin/service-areas/{id}', [ServiceAreaController::class, 'update']);
  Route::delete('/admin/service-areas/{id}', [ServiceAreaController::class, 'destroy']);
});
