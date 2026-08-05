<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by model guards/observers enforcing a BR-* rule from the spec.
 * Gives every business-rule rejection a consistent, identifiable type,
 * distinct from HTTP-layer form validation (ValidationException) — this is
 * a domain-layer invariant, not a request-shape check.
 */
class BusinessRuleException extends RuntimeException
{
    public static function violated(string $rule, string $message): self
    {
        return new self("[{$rule}] {$message}");
    }
}
