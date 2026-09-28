<?php
// #archivo: /backend/app/Services/Auth/GoogleAuthException.php

namespace App\Services\Auth;

/** CU02 E5 — motivo: not_configured | unavailable | invalid_token */
class GoogleAuthException extends \RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
