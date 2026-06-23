<?php

namespace App\Tests\Service;

use App\Application\DTO\TransferRequest;
use App\Application\Service\RequestValidationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RequestValidationServiceTest extends KernelTestCase
{
    public function testValidTransferRequestReturnsNoErrors(): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => true]);

        $validator = self::getContainer()->get('validator');
        $service = new RequestValidationService($validator);

        $request = new TransferRequest([
            'fromAccountId' => 'acct-1',
            'toAccountId' => 'acct-2',
            'amount' => 100.0,
            'currency' => 'USD',
        ]);

        $errors = $service->validate($request);

        $this->assertCount(0, $errors);
    }

    public function testInvalidTransferRequestReturnsErrors(): void
    {
        self::bootKernel(['environment' => 'test', 'debug' => true]);

        $validator = self::getContainer()->get('validator');
        $service = new RequestValidationService($validator);

        $request = new TransferRequest([
            'fromAccountId' => '',
            'toAccountId' => '',
            'amount' => 0,
            'currency' => '',
        ]);

        $errors = $service->validate($request);

        $this->assertSame(4, count($errors));
        $this->assertStringContainsString('fromAccountId', $errors[0]);
    }
}
