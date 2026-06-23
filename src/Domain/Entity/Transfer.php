<?php

namespace App\Domain\Entity;

use App\Domain\Repository\TransferRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransferRepository::class)]
#[ORM\Table(name: 'transfers')]
final class Transfer
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Account $fromAccount;

    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Account $toAccount;

    #[ORM\Column(type: 'bigint')]
    private int $amountCents;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Account $fromAccount, Account $toAccount, int $amountCents, string $currency)
    {
        $this->id = bin2hex(random_bytes(16));
        $this->fromAccount = $fromAccount;
        $this->toAccount = $toAccount;
        $this->amountCents = $amountCents;
        $this->currency = strtoupper($currency);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFromAccount(): Account
    {
        return $this->fromAccount;
    }

    public function getToAccount(): Account
    {
        return $this->toAccount;
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    public function getAmount(): string
    {
        return number_format($this->amountCents / 100, 2, '.', '');
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fromAccount' => $this->fromAccount->getId(),
            'toAccount' => $this->toAccount->getId(),
            'amount' => $this->getAmount(),
            'amountCents' => $this->amountCents,
            'currency' => $this->currency,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
        ];
    }
}
