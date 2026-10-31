<?php

namespace App\Services\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\ServiceUnavailableException;

class ProductServiceClient
{
    public function getProduct(int $productId): array
    {
        try {
            $response = Http::timeout(5)->get(
                rtrim(config('services.product.url'), '/') . "/products/{$productId}"
            );

            if ($response->status() === 404) {
                throw new ProductNotFoundException("Produk dengan ID {$productId} tidak ditemukan.");
            }

            if (!$response->successful()) {
                throw new ServiceUnavailableException("Product Service tidak dapat memproses permintaan.");
            }

            return $response->json('data') ?? [];

        } catch (ConnectionException $e) {
            throw new ServiceUnavailableException("Product Service tidak dapat dihubungi.");
        }
    }
}