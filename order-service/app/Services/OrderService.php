<?php

namespace App\Services;

use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderConflictException;
use App\Services\Clients\CartServiceClient;
use App\Services\Clients\ProductServiceClient;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderService
{
    public function __construct(
        private CartServiceClient $cartClient,
        private ProductServiceClient $productClient
    ) {
    }

    public function checkout(int $userId): Order
    {
        // 1. Mengambil keranjang pengguna
        $cart = $this->cartClient->getCart($userId);

        $items = $cart['items'] ?? [];

        // 2. Memastikan keranjang tidak kosong
        if (empty($items)) {
            throw new EmptyCartException(
                'Keranjang masih kosong.'
            );
        }

        $orderItems = [];
        $totalAmount = 0;

        // 3. Memeriksa setiap produk dan stok
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                throw new OrderConflictException(
                    'Data produk atau jumlah barang tidak valid.'
                );
            }

            $product = $this->productClient->getProduct($productId);

            $stock = (int) ($product['stock'] ?? 0);

            if ($stock < $quantity) {
                throw new InsufficientStockException(
                    "Stok produk {$productId} tidak mencukupi."
                );
            }

            $unitPrice = (float) ($product['price'] ?? 0);

            if ($unitPrice < 0 || !is_numeric($product['price'] ?? null)) {
                throw new OrderConflictException(
                    'Harga produk tidak valid.'
                );
            }

            $subtotal = round($unitPrice * $quantity, 2);

            $orderItems[] = [
                'product_id' => $productId,
                'product_name' => $product['name'] ?? 'Produk',
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];

            $totalAmount += $subtotal;
        }

        // 4. Menyimpan pesanan dan detailnya dalam transaksi database
        $order = DB::transaction(function () use (
            $userId,
            $orderItems,
            $totalAmount
        ) {
            $order = Order::create([
                'user_id' => $userId,
                'status' => 'PENDING',
                'total_amount' => $totalAmount,
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            return $order->load('items');
        });

        // 5. Mengosongkan keranjang setelah pesanan berhasil disimpan
        // Kegagalan mengosongkan keranjang tidak membatalkan pesanan.
        try {
            $this->cartClient->clearCart($userId);
        } catch (Throwable $e) {
            Log::error('Pesanan berhasil dibuat, tetapi keranjang gagal dikosongkan.', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return $order;
    }

    public function pay(int $orderId): Order
{
    return DB::transaction(function () use ($orderId) {
        $order = Order::where('id', $orderId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($order->status !== 'PENDING') {
            throw new OrderConflictException(
                'Pesanan sudah dibayar atau tidak dapat dibayar.'
            );
        }

        $order->update([
            'status' => 'PAID',
        ]);

        return $order->load('items');
    });
}
}