<?php

namespace App\Services\Clients;

use App\Exceptions\ServiceUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class CartServiceClient
{
    private function baseUrl(): string
    {
        return rtrim(config('services.cart.url'), '/');
    }

    public function getCart(int $userId): array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get($this->baseUrl() . "/carts/{$userId}");
        } catch (ConnectionException $e) {
            throw new ServiceUnavailableException(
                'Cart Service tidak dapat dihubungi.',
                0,
                $e
            );
        }

        if (!$response->successful()) {
            throw new ServiceUnavailableException(
                'Gagal mengambil data dari Cart Service.'
            );
        }

        return $response->json('data') ?? [];
    }

    public function clearCart(int $userId): array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->delete($this->baseUrl() . "/carts/{$userId}");
        } catch (ConnectionException $e) {
            throw new ServiceUnavailableException(
                'Gagal mengosongkan keranjang.',
                0,
                $e
            );
        }

        if (!$response->successful()) {
            throw new ServiceUnavailableException(
                'Cart Service gagal mengosongkan keranjang.'
            );
        }

        return $response->json('data') ?? [];
    }
}