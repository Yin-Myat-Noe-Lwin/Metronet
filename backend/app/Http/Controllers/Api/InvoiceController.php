<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;

use Barryvdh\DomPDF\Facade\Pdf;
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

            // Check if customer exists
            if (!$customer) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $invoices = Invoice::whereHas('subscription', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
                    ->orderBy('created_at', 'desc')
                    ->get();

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);

        } catch (PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Query error: ' . $e->getMessage()
            ], 500);
        } catch (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage()
            ], 401);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization failed: ' . $e->getMessage()
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $customer = Auth::user();

            // Check if customer exists
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $invoice = Invoice::where('id', $id)
                ->whereHas('subscription', function ($q) use ($customer) {
                    $q->where('customer_id', $customer->id);
                })
                ->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);

        } catch (PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Query error: ' . $e->getMessage()
            ], 500);
        } catch (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage()
            ], 401);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization failed: ' . $e->getMessage()
            ], 403);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function download($id)
    {
        try {
            $customer = Auth::user();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $invoice = Invoice::where('id', $id)
                ->whereHas('subscription', function ($q) use ($customer) {
                    $q->where('customer_id', $customer->id);
                })
                ->with(['subscription.plan', 'subscription.customer'])
                ->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            // Get customer data
            $customer = $invoice->subscription->customer;
            $plan = $invoice->subscription->plan;

            // Prepare data for PDF
            $data = [
                'invoice' => $invoice,
                'customer' => $customer,
                'plan' => $plan,
                'invoice_number' => $invoice->invoice_number ?? 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT),
                'amount' => number_format($invoice->amount, 2),
                'status' => $this->getStatusText($invoice->status),
                'due_date' => $invoice->due_date ? date('F d, Y', strtotime($invoice->due_date)) : 'N/A',
                'created_date' => $invoice->created_at ? date('F d, Y', strtotime($invoice->created_at)) : 'N/A',
                'paid_date' => $invoice->paid_at ? date('F d, Y', strtotime($invoice->paid_at)) : 'N/A',
                'company_name' => config('app.name', 'MetroNet'),
                'company_address' => 'Yangon, Myanmar',
                'company_phone' => '+95 9 123 456 789',
                'company_email' => 'info@metronet.com',
            ];

            // Generate PDF
            $pdf = Pdf::loadView('pdf.invoice', $data);

            // Set paper size
            $pdf->setPaper('A4', 'portrait');

            // Return PDF download
            return $pdf->download($data['invoice_number'] . '.pdf');

        } catch (PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getStatusText($status)
    {
        $map = [
            0 => 'Pending',
            1 => 'Paid',
            2 => 'Overdue',
            3 => 'Cancelled'
        ];
        return $map[$status] ?? 'Unknown';
    }

    public function viewInvoices(): JsonResponse
    {
        try {
            // ✅ Load all invoices with relationships
            $invoices = Invoice::with([
                'subscription.customer',
                'subscription.plan',
                'payment'
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice) {
                $invoice->customer = $invoice->subscription?->customer;
                $invoice->plan = $invoice->subscription?->plan;
                return $invoice;
            });

            return response()->json([
                'success' => true,
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
