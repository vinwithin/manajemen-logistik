<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    protected float $tersedia;

    public function __construct(float $tersedia, string $message = '')
    {
        $this->tersedia = $tersedia;
        $message = $message ?: "Stok tidak cukup. Tersedia: {$tersedia} kg";
        parent::__construct($message);
    }

    public function getTersedia(): float
    {
        return $this->tersedia;
    }
}
