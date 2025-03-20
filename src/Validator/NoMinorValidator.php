<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class NoMinorValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var NoMinor $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        // Ensure $value is a valid date
        if (!$value instanceof \DateTimeInterface) {
            $this->context->buildViolation('Invalid date format.')
                ->setParameter('{{ value }}', $value)
                ->addViolation();
            return;
        }

        $today = new \DateTime();
        $age = $today->diff($value)->y;

        if ($age < 18) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value->format('Y-m-d'))
                ->addViolation();
        }
    }
}
