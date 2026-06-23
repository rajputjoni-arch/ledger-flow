<?php

namespace App\Domain\Exception;

final class InsufficientFundsException extends \RuntimeException
{
    public static function forAccount(string $accountId, string $availableBalance, string $requestedAmount): self
    {
        return new self(
            sprintf(
                'Account %s has insufficient funds. Available: %s, Requested: %s',
                $accountId,
                $availableBalance,
                $requestedAmount
            )
        );
    }
}
