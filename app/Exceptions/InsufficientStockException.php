<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly Product $product,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct(
            "Stock insuficiente para «{$product->name}». Disponible: {$available}, solicitado: {$requested}.",
        );
    }
}
