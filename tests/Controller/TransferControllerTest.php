<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TransferControllerTest extends WebTestCase
{
    public function testRequiresApiKey(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/transfers', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'fromAccountId' => 'acct-1',
            'toAccountId' => 'acct-2',
            'amount' => 100.00,
            'currency' => 'USD',
        ]));

        self::assertResponseStatusCodeSame(401);
        self::assertStringContainsString('Unauthorized request', $client->getResponse()->getContent());
    }

    public function testTransferSucceedsWithValidApiKey(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/transfers', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Api-Key' => 'super_secret_api_key',
            'HTTP_X-Idempotency-Key' => 'transfer-test-1',
        ], json_encode([
            'fromAccountId' => 'acct-1',
            'toAccountId' => 'acct-2',
            'amount' => 100.00,
            'currency' => 'USD',
        ]));

        self::assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('success', $data['status']);
        self::assertArrayHasKey('transfer', $data);
        self::assertSame('acct-1', $data['transfer']['fromAccount']);
        self::assertSame('acct-2', $data['transfer']['toAccount']);
    }
}
