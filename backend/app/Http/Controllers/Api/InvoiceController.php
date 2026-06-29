<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

use PDOException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $customer = Auth::user();

            $invoices = Invoice::whereHas('subscription', function ($q) {
                                    $q->where('customer_id', $customer->id);
                                })->get();

            return response()->json([
                'data' => $invoices
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }

    public function show($id): JsonResponse
    {
        try{
            $customer = Auth::user();

            $invoice = Invoice::where('id', $id)
                                ->whereHas('subscription', function ($q) use ($customer) {
                                    $q->where('customer_id', $customer->id);
                                })
                                ->first();

            if (!$invoice) {
                return response()->json([
                    'message' => 'Invoice not found'
                ]);
            }

            return response()->json([
                'data' => $invoice
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }

    public function viewInvoices(): JsonResponse
    {
        try{
            $invoices = Invoice::with([
                            'subscription.customer',
                            'subscription.plan',
                            'payments'
                        ])
                        ->orderBy('created_at', 'desc')
                        ->get();

            return response()->json([
                'data' => $invoices
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }
}
