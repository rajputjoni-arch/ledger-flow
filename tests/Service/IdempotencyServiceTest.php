<?php

namespace App\Tests\Service;

use App\Application\Service\IdempotencyService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class IdempotencyServiceTest extends TestCase
{
    public function testGetReturnsNullWhenKeyDoesNotExist(): void
    {
        $service = new IdempotencyService(new ArrayAdapter());

        $this->assertFalse($service->has('missing-key'));
        $this->assertNull($service->get('missing-key'));
    }

    public function testStoreAndGetRoundTripPayload(): void
    {
        $service = new IdempotencyService(new ArrayAdapter());
        $payload = ['status' => 'ok', 'transactionId' => 'tx-123'];

        $service->store('idempotency-key-1', $payload);

        $this->assertTrue($service->has('idempotency-key-1'));
        $this->assertSame($payload, $service->get('idempotency-key-1'));
    }

    public function testStoreOverwritesExistingValueForSameKey(): void
    {
        $service = new IdempotencyService(new ArrayAdapter());

        $service->store('idempotency-key-2', ['status' => 'old']);
        $service->store('idempotency-key-2', ['status' => 'new']);

        $this->assertSame(['status' => 'new'], $service->get('idempotency-key-2'));
    }
}
