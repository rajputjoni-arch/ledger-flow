<?php

namespace App\Service;

use App\Entity\Transfer;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class TransferService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountRepository $accountRepository,
        private CacheInterface $accountSnapshotCache
    ) {
    }

    public function transfer(string $fromAccountId, string $toAccountId, float $amount, string $currency = 'USD'): array
    {
        if ($fromAccountId === $toAccountId) {
            throw new \InvalidArgumentException('Source and destination accounts must differ.');
        }

        $amountCents = $this->normalizeAmount($amount);

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $fromAccount = $this->accountRepository->findForUpdate($fromAccountId);
            $toAccount = $this->accountRepository->findForUpdate($toAccountId);

            if ($fromAccount === null || $toAccount === null) {
                throw new \InvalidArgumentException('One or more accounts were not found.');
            }

            if ($fromAccount->getCurrency() !== strtoupper($currency) || $toAccount->getCurrency() !== strtoupper($currency)) {
                throw new \InvalidArgumentException('Currency mismatch between accounts and transfer request.');
            }

            $fromAccount->withdrawCents($amountCents);
            $toAccount->depositCents($amountCents);

            $transfer = new Transfer($fromAccount, $toAccount, $amountCents, $currency);
            $this->entityManager->persist($transfer);
            $this->entityManager->flush();
            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        $this->invalidateAccountSnapshot($fromAccountId);
        $this->invalidateAccountSnapshot($toAccountId);

        return [
            'transactionId' => $transfer->getId(),
            'fromAccount' => $fromAccount->toArray(),
            'toAccount' => $toAccount->toArray(),
            'amount' => $transfer->getAmount(),
            'currency' => $transfer->getCurrency(),
            'createdAt' => $transfer->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    private function normalizeAmount(float $amount): int
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be greater than zero.');
        }

        return (int) round($amount * 100, 0, PHP_ROUND_HALF_UP);
    }

    private function invalidateAccountSnapshot(string $accountId): void
    {
        $this->accountSnapshotCache->delete(sprintf('account_snapshot_%s', $accountId));
    }
}
