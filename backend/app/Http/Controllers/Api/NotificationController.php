<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

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

            $notifications = Notification::where('customer_id', $customer->id)->get();

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
        try{
            $customer = Auth::user();

            $notification = $customer->notifications()
                                    ->where('id', $id)
                                    ->first();

            if (!$notification) {
                return response()->json([
                    'message' => 'Notification not found'
                ]);
            }

            if ($notification->status == 1) {
                return response()->json([
                    'message' => 'Notification is already marked as read.'
                ]);
            }

            // update notification status after read by customer
            $notification->update([
                'status' => 1
            ]);

            return response()->json([
                'message' => 'Notification marked as read'
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
