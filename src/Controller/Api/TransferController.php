<?php

namespace App\Controller\Api;

use App\Service\IdempotencyService;
use App\Service\TransferService;
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
        private IdempotencyService $idempotencyService
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

        $requiredFields = ['fromAccountId', 'toAccountId', 'amount', 'currency'];

        foreach ($requiredFields as $field) {
            if (empty($payload[$field])) {
                return new JsonResponse([

                    'error' => sprintf('Missing required field: %s.', $field),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $idempotencyKey = $request->headers->get('X-Idempotency-Key');

        if ($idempotencyKey && $this->idempotencyService->has($idempotencyKey)) {
            return new JsonResponse($this->idempotencyService->get($idempotencyKey), Response::HTTP_OK);
        }

        try {
            $result = $this->transferService->transfer(
                (string) ($payload['fromAccountId'] ?? ''),
                (string) ($payload['toAccountId'] ?? ''),
                (float) ($payload['amount'] ?? 0),
                (string) ($payload['currency'] ?? 'USD')
            );
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            return new JsonResponse([
                'error' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $response = [
            'status' => 'success',
            'transfer' => $result,
        ];

        if ($idempotencyKey) {
            $this->idempotencyService->store($idempotencyKey, $response);
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }
}
