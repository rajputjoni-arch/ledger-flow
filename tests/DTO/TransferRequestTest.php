<?php

namespace App\Tests\DTO;

use App\Application\DTO\TransferRequest;
use PHPUnit\Framework\TestCase;

final class TransferRequestTest extends TestCase
{
    public function testConstructorMapsProvidedPayloadValues(): void
    {
        $dto = new TransferRequest([
            'fromAccountId' => 'acct-1',
            'toAccountId' => 'acct-2',
            'amount' => 42.75,
            'currency' => 'USD',
        ]);

        $this->assertSame('acct-1', $dto->fromAccountId);
        $this->assertSame('acct-2', $dto->toAccountId);
        $this->assertSame(42.75, $dto->amount);
        $this->assertSame('USD', $dto->currency);
    }

    public function testConstructorUsesSafeDefaultsForMissingFields(): void
    {
        $dto = new TransferRequest([]);

        $this->assertSame('', $dto->fromAccountId);
        $this->assertSame('', $dto->toAccountId);
        $this->assertSame(0, $dto->amount);
        $this->assertSame('', $dto->currency);
    }

    public function testConstructorNormalizesNonNumericAmountToZero(): void
    {
        $dto = new TransferRequest([
            'fromAccountId' => 'acct-1',
            'toAccountId' => 'acct-2',
            'amount' => 'not-a-number',
            'currency' => 'USD',
        ]);

        $this->assertSame(0, $dto->amount);
    }
}
