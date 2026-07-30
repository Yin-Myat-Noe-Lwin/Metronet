<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanDiscount;
use App\Models\IspPlan;
use Illuminate\Http\Request;
use App\Http\Requests\PlanDiscountRequest;
use App\Http\Requests\PlanDiscountUpdateRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PlanDiscountController extends Controller
{
    /**
     * Display a listing of plan discounts.
     */
    public function index()
    {
        try {
            $discounts = PlanDiscount::with('plan')->get();
            return response()->json($discounts);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch discounts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created plan discount.
     */
    public function store(PlanDiscountRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|exists:isp_plans,id',
                'duration_months' => [
                    'required',
                    'integer',
                    'in:1,3,6,12,24',
                    Rule::unique('plan_discounts')->where(function ($query) use ($request) {
                        return $query->where('plan_id', $request->plan_id)
                                     ->where('duration_months', $request->duration_months);
                    })
                ],
                'discount_percentage' => 'required|numeric|min:0|max:100',
                'is_active' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $discount = PlanDiscount::create([
                'plan_id' => $request->plan_id,
                'duration_months' => $request->duration_months,
                'discount_percentage' => $request->discount_percentage,
                'is_active' => $request->is_active ?? 1
            ]);

            return response()->json([
                'message' => 'Discount created successfully',
                'data' => $discount->load('plan')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create discount',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified plan discount.
     */
    public function show($id)
    {
        try {
            $discount = PlanDiscount::with('plan')->find($id);

            if (!$discount) {
                return response()->json([
                    'message' => 'Discount not found'
                ], 404);
            }

            return response()->json($discount);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch discount',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified plan discount.
     */
    public function update(PlanDiscountUpdateRequest $request, $id)
    {
        try {
            $discount = PlanDiscount::find($id);

            if (!$discount) {
                return response()->json([
                    'message' => 'Discount not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'plan_id' => 'sometimes|exists:isp_plans,id',
                'duration_months' => [
                    'sometimes',
                    'integer',
                    'in:1,3,6,12,24',
                    Rule::unique('plan_discounts')->where(function ($query) use ($request, $id) {
                        return $query->where('plan_id', $request->plan_id ?? $this->plan_id)
                                     ->where('duration_months', $request->duration_months)
                                     ->where('id', '!=', $id);
                    })
                ],
                'discount_percentage' => 'sometimes|numeric|min:0|max:100',
                'is_active' => 'sometimes|boolean'
            ]);

        if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate
            $planId = $request->plan_id ?? $discount->plan_id;
            $durationMonths = $request->duration_months ?? $discount->duration_months;

            $existing = PlanDiscount::where('plan_id', $planId)
                ->where('duration_months', $durationMonths)
                ->where('id', '!=', $id)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'A discount for this plan and duration already exists',
                    'errors' => [
                        'duration_months' => ['A discount for this plan and duration already exists']
                    ]
                ], 422);
            }

            $discount->update($request->all());

            return response()->json([
                'message' => 'Discount updated successfully',
                'data' => $discount->load('plan')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update discount',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified plan discount.
     */
    public function destroy($id)
    {
        try {
            $discount = PlanDiscount::find($id);

            if (!$discount) {
                return response()->json([
                    'message' => 'Discount not found'
                ], 404);
            }

            $discount->delete();

            return response()->json([
                'message' => 'Discount deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete discount',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get discounts for a specific plan
     */
    public function getByPlan($planId)
    {
        try {
            $discounts = PlanDiscount::where('plan_id', $planId)
                ->where('is_active', 1)
                ->orderBy('duration_months')
                ->get();

            return response()->json($discounts);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch plan discounts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
