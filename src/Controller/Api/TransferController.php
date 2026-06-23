<?php

namespace App\Controller\Api;

use App\Application\Service\IdempotencyService;
use App\Application\Service\RequestValidationService;
use App\Application\Service\TransferService;
use App\Application\DTO\TransferRequest;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/transfer', name: 'api_transfer_')]
final class TransferController extends AbstractController
{
    public function __construct(
        private TransferService $transferService,
        private RequestValidationService $requestValidationService,
        private IdempotencyService $idempotencyService,
        private LoggerInterface $logger
    ) {
    }

   #[Route('', name:'', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
        } catch (\JsonException $exception) {
            return new JsonResponse([
                'error' => 'Invalid JSON payload.',
                'details' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $transferRequest = new TransferRequest($payload);
        $errors = $this->requestValidationService->validate($transferRequest);

        if ($errors !== []) {
            $this->logger->warning('Transfer request validation failed.', [
                'errors' => $errors,
                'payload' => $payload,
            ]);

            return new JsonResponse([
                'errors' => $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $idempotencyKey = $request->headers->get('X-Idempotency-Key');

        if (!$idempotencyKey) {
            $this->logger->warning('Transfer request missing X-Idempotency-Key header.');

            return new JsonResponse([
                'error' => 'The X-Idempotency-Key header is required.',
                'details' => 'Provide a unique identifier to prevent duplicate transfers.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($this->idempotencyService->has($idempotencyKey)) {
            $this->logger->info('Replaying idempotent transfer response.', [
                'idempotency_key' => $idempotencyKey,
            ]);

            return new JsonResponse($this->idempotencyService->get($idempotencyKey), Response::HTTP_OK);
        }

        try {
            $result = $this->transferService->transfer(
                $transferRequest->fromAccountId,
                $transferRequest->toAccountId,
                (float) $transferRequest->amount,
                $transferRequest->currency
            );
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            $this->logger->warning('Transfer service rejected the request.', [
                'exception' => $exception,
                'payload' => $payload,
            ]);

            return new JsonResponse([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            $this->logger->error('Unexpected error during transfer.', [
                'exception' => $exception,
                'payload' => $payload,
            ]);

            return new JsonResponse([
                'error' => 'An internal error occurred while processing the transfer.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response = [
            'status' => 'success',
            'transfer' => $result,
        ];

        $this->idempotencyService->store($idempotencyKey, $response);

        return new JsonResponse($response, Response::HTTP_OK);
    }
}
