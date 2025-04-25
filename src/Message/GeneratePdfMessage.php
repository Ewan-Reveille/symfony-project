<?php

namespace App\Message;

class GeneratePdfMessage
{
    public function __construct(
        private string $type,
        private string $payload
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }
}