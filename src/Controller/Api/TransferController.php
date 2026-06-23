<?php

namespace App\Controller\Api;

use App\Service\IdempotencyService;
use App\Service\TransferService;
use App\Application\DTO\TransferRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/transfer', name: 'api_transfer_')]
final class TransferController extends AbstractController
{
    public function __construct(
        private TransferService $transferService,
        private ValidatorInterface $validator,
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

        $transferRequest = new TransferRequest($payload);
        $violations = $this->validator->validate($transferRequest);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
            }

            return new JsonResponse([
                'errors' => $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $idempotencyKey = $request->headers->get('X-Idempotency-Key');

        if ($idempotencyKey && $this->idempotencyService->has($idempotencyKey)) {
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
