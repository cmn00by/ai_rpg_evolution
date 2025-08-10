<?php

namespace App\Exceptions;

use Exception;

class BoutiqueException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Erreur de boutique',
                'message' => $this->getMessage(),
                'code' => $this->getCode()
            ], 400);
        }

        return redirect()->back()
            ->withErrors(['boutique' => $this->getMessage()])
            ->withInput();
    }
}