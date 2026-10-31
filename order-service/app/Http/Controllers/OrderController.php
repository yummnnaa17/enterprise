<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderConflictException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\ServiceUnavailableException;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {
    }

    // POST /api/orders
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data request tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $order = $this->orderService->checkout(
                (int) $request->user_id
            );

            return response()->json([
                'message' => 'Pesanan berhasil dibuat.',
                'data' => $order,
            ], 201);

        } catch (EmptyCartException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);

        } catch (
            InsufficientStockException |
            ProductNotFoundException |
            OrderConflictException $e
        ) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);

        } catch (ServiceUnavailableException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 503);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Terjadi kesalahan pada Order Service.',
            ], 500);
        }
    }

    // GET /api/orders/{id}
    public function show(int $id)
    {
        try {
            $order = Order::with('items')->findOrFail($id);

            return response()->json([
                'message' => 'Detail pesanan berhasil diambil.',
                'data' => $order,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Terjadi kesalahan pada Order Service.',
            ], 500);
        }
    }

    // GET /api/orders/user/{userId}
    public function userOrders(Request $request, int $userId)
    {
        $validator = Validator::make($request->query(), [
            'status' => 'nullable|in:PENDING,PAID',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Filter status tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = Order::with('items')
                ->where('user_id', $userId);

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            return response()->json([
                'message' => 'Daftar pesanan berhasil diambil.',
                'data' => $query->orderByDesc('created_at')->get(),
            ]);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Terjadi kesalahan pada Order Service.',
            ], 500);
        }
    }

    // PATCH /api/orders/{id}/pay
    public function pay(int $id)
    {
        try {
            $order = $this->orderService->pay($id);

            return response()->json([
                'message' => 'Pesanan berhasil dibayar.',
                'data' => $order,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);

        } catch (OrderConflictException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Terjadi kesalahan pada Order Service.',
            ], 500);
        }
    }
}