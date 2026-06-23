<?php

namespace App\Application\Service;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestValidationService
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * Validate a DTO and return an array of human-readable errors.
     *
     * @return list<string>
     */
    public function validate(object $dto): array
    {
        return $this->formatViolations($this->validator->validate($dto));
    }

    /**
     * @return list<string>
     */
    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        return $errors;
    }
}
