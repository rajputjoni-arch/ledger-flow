<?php

namespace App\Model;

final class Account
{
    public function __construct(
        private string $id,
        private string $owner,
        private float $balance,
        private string $currency = 'USD'
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function withdraw(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        if ($amount > $this->balance) {
            throw new \RuntimeException('Insufficient funds for transfer.');
        }

        $this->balance -= $amount;
    }

    public function deposit(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        $this->balance += $amount;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner' => $this->owner,
            'balance' => $this->balance,
            'currency' => $this->currency,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['owner'],
            (float) $data['balance'],
            $data['currency'] ?? 'USD'
        );
    }
}
