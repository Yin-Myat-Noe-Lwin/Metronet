<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;

use PDOException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

use Junges\Kafka\Facades\Kafka;

use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function pay($invoiceId): JsonResponse
    {
        // logged in customer
        $customer = Auth::user();

        $invoice = Invoice::where('id', $invoiceId)
                        ->whereHas('subscription', function ($q) use ($customer) {
                            $q->where('customer_id', $customer->id);
                        })
                        ->first();

        if (!$invoice || $invoice->status != 0) {
            return response()->json(['message' => 'Invalid invoice']);
        }

        DB::beginTransaction();

        try{
            $payment = Payment::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->amount,
                'method' => 4,
                'status' => 1,
                'transaction_ref' => 'TXN-' . time(),
                'paid_at' => now(),
            ]);

            // update invoice status to 1
            $invoice->update([
                'status' => 1
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
        }

        // publish Kafka event
        Kafka::publish()
            ->onTopic('payment.completed')
            ->withBodyKey('payment_id', $payment->id)
            ->withBodyKey('invoice_id', $invoice->id)
            ->withBodyKey('customer_id', $customer->id)
            ->send();

        return response()->json([
            'message' => 'Payment successful',
            'payment' => $payment
        ]);
    }

    public function index(): JsonResponse
    {
        try {
            $customer = Auth::user();

            $payments = Payment::where('customer_id', $customer->id)->get();

            return response()->json([
                'data' => $payments
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

    public function show($id)
    {
        try{
            $customer = Auth::user();

            $payment = $customer->payments()
                                ->where('id', $id)
                                ->first();

            if (!$payment) {
                return response()->json([
                    'message' => 'Payment not found'
                ]);
            }

            return response()->json([
                'data' => $payment
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

    public function viewPayments(): JsonResponse
    {
        try{
            $payments = Payment::with([
                            'customer',
                            'invoice.subscription.plan'
                        ])
                        ->orderBy('created_at', 'desc')
                        ->get();

            return response()->json([
            'data' => $payments
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
