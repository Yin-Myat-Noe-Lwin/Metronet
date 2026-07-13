<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

use PDOException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $customer = Auth::user();

            $notifications = Notification::where('customer_id', $customer->id)
                                            ->orderBy('created_at', 'desc')
                                            ->get();

            return response()->json([
                'data' => $notifications
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

    public function markAsRead($id)
    {
        try {
            $customer = Auth::user();

            if (!$customer) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            $notification = $customer->notifications()
                ->where('id', $id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'message' => 'Notification not found'
                ], 404);
            }

            if ($notification->is_read == 1) {
                return response()->json([
                    'message' => 'Notification is already marked as read.',
                    'data' => $notification
                ], 400);
            }

            $notification->update([
                'is_read' => 1,
                'read_at' => now()
            ]);

            Log::info('Notification marked as read', [
                'notification_id' => $id,
                'customer_id' => $customer->id
            ]);

            return response()->json([
                'message' => 'Notification marked as read',
                'data' => $notification
            ]);

        }  catch (PDOException $e) {
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
