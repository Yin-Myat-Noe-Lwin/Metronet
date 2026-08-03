<?php

  use App\Http\Controllers\Api\AuthController;
  use App\Http\Controllers\Api\CustomerAddressController;
  use App\Http\Controllers\Api\IspPlanController;
  use App\Http\Controllers\Api\SubscriptionController;
  use App\Http\Controllers\Api\InvoiceController;
  use App\Http\Controllers\Api\CustomerController;
  use App\Http\Controllers\Api\PaymentController;
  use App\Http\Controllers\Api\NotificationController;
  use App\Http\Controllers\Api\ServiceAreaController;

  Route::get('/plans', [IspPlanController::class, 'index']);

  Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
  Route::post('/reset-password', [AuthController::class, 'resetPassword']);

  Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // customer able to view all the added addresses
    Route::get('/customer-addresses', [CustomerAddressController::class, 'viewAddresses']);
    Route::post('/address', [CustomerAddressController::class, 'store']);
    Route::patch('/address/{id}', [CustomerAddressController::class, 'update']);
    Route::delete('/address/{id}', [CustomerAddressController::class, 'destroy']);

    Route::get('/subscriptions', [SubscriptionController::class, 'viewSubscriptions']);
    Route::post('/subscribe/{plan}', [SubscriptionController::class, 'store']);
    Route::delete('/subscriptions/{id}', [SubscriptionController::class, 'destroy']);

    Route::patch('/profile', [CustomerController::class, 'updateProfile']);

    // get data for dropdown
    Route::get('/service-areas', [ServiceAreaController::class, 'viewAreas']);
    // filter city based on selected region
    Route::get('/cities', [ServiceAreaController::class, 'getCitiesByRegion']);
    // filter township based on selected city
    Route::get('/townships', [ServiceAreaController::class, 'getTownshipsByCity']);
    // get filtered areas
    Route::get('/filtered', [ServiceAreaController::class, 'getFilteredAreas']);

    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);

    Route::post('/pay/{invoice}', [PaymentController::class, 'pay']);
    Route::get('/payment-methods', [PaymentController::class, 'getPaymentMethods']);
    Route::get('/payments', [PaymentController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
  });
