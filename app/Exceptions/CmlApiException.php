<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception personnalisée pour les erreurs d'API CML
 */
class CmlApiException extends Exception
{
    protected string $endpoint;
    protected int $httpStatus;
    protected array $responseData;
    protected bool $isConnectionError;

    public function __construct(
        string $message,
        string $endpoint,
        int $httpStatus = 0,
        array $responseData = [],
        bool $isConnectionError = false,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $httpStatus, $previous);
        $this->endpoint = $endpoint;
        $this->httpStatus = $httpStatus;
        $this->responseData = $responseData;
        $this->isConnectionError = $isConnectionError;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public function isConnectionError(): bool
    {
        return $this->isConnectionError;
    }

    /**
     * Convertir l'exception en tableau pour les logs
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'endpoint' => $this->endpoint,
            'http_status' => $this->httpStatus,
            'response_data' => $this->responseData,
            'is_connection_error' => $this->isConnectionError,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}

