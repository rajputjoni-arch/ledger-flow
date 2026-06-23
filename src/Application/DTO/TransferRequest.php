<?php

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class TransferRequest
{
    #[Assert\NotBlank]
    public string $fromAccountId;

    #[Assert\NotBlank]
    public string $toAccountId;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public float|int $amount;

    #[Assert\NotBlank]
    public string $currency;

    public function __construct(array $payload)
    {
        $this->fromAccountId = (string) ($payload['fromAccountId'] ?? '');
        $this->toAccountId = (string) ($payload['toAccountId'] ?? '');
        $this->amount = isset($payload['amount']) && is_numeric($payload['amount'])
            ? (float) $payload['amount']
            : 0;
        $this->currency = (string) ($payload['currency'] ?? '');
    }
}
