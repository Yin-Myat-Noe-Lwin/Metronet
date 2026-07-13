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

use Illuminate\Support\Facades\Auth;
use Junges\Kafka\Facades\Kafka;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Http\Requests\PayInvoiceRequest;

use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{

    public function getPaymentMethods(): JsonResponse
    {
        try {
            $methods = PaymentMethod::where('is_active', 1)->get();

            $methods->transform(function ($method) {
                // Ensure proper encoding for emojis
                if ($method->icon_type == 1) {
                    $encoded = mb_convert_encoding($method->icon, 'UTF-8', 'auto');

                    if (strpos($encoded, '&#') !== false) {
                        $encoded = html_entity_decode($encoded, ENT_QUOTES, 'UTF-8');
                    }

                    $method->icon = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $encoded);

                } elseif ($method->icon_type == 2) {
                    $method->icon = $method->icon;
                } elseif ($method->icon_type == 3) {
                    $method->icon = asset('storage/' . $method->icon);
                }

                if ($method->description) {
                    $method->description = mb_convert_encoding($method->description, 'UTF-8', 'auto');
                }

                if ($method->name) {
                    $method->name = mb_convert_encoding($method->name, 'UTF-8', 'auto');
                }

                return $method;
            });

            return response()->json([
                'data' => $methods
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch payment methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function pay(Request $request, $invoiceId): JsonResponse
    {
        $customer = Auth::user();

        $invoice = Invoice::where('id', $invoiceId)
            ->whereHas('subscription', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->first();

        if (!$invoice || $invoice->status != 0) {
            return response()->json(['message' => 'Invalid invoice'], 400);
        }

        $paymentMethod = $request->input('payment_method');

        // Get payment details (default to empty array if null)
        $paymentDetails = $request->input('payment_details', []);

        DB::beginTransaction();

        try {
            $payment = Payment::create([
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->amount,
                'method' => $paymentMethod,
                'payment_details' => $paymentDetails, // Now always an array
                'status' => 1,
                'transaction_ref' => 'TXN-' . time() . '-' . uniqid(),
                'paid_at' => now(),
            ]);

            $invoice->update(['status' => 1]);

            DB::commit();

            // Kafka publish
            try {
                Kafka::publish()
                    ->onTopic('payment.success')
                    ->withBodyKey('payment_id', $payment->id)
                    ->withBodyKey('invoice_id', $invoice->id)
                    ->withBodyKey('customer_id', $customer->id)
                    ->send();
            } catch (\Exception $e) {
                \Log::error('Kafka error: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Payment successful',
                'payment' => $payment
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
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
