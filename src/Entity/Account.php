<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
final class Account
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 128)]
    private string $owner;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'bigint')]
    private int $balanceCents;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $id, string $owner, int $balanceCents, string $currency = 'USD')
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->currency = strtoupper($currency);
        $this->balanceCents = $balanceCents;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBalanceCents(): int
    {
        return $this->balanceCents;
    }

    public function getBalance(): string
    {
        return number_format($this->balanceCents / 100, 2, '.', '');
    }

    public function withdrawCents(int $amountCents): void
    {
        if ($amountCents <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be greater than zero.');
        }

        if ($amountCents > $this->balanceCents) {
            throw new \RuntimeException('Insufficient funds for transfer.');
        }

        $this->balanceCents -= $amountCents;
        $this->touch();
    }

    public function depositCents(int $amountCents): void
    {
        if ($amountCents <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be greater than zero.');
        }

        $this->balanceCents += $amountCents;
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner' => $this->owner,
            'currency' => $this->currency,
            'balance' => $this->getBalance(),
            'balanceCents' => $this->balanceCents,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}
