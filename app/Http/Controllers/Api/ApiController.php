<?php

namespace Vanguard\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Vanguard\Http\Controllers\Controller;

abstract class ApiController extends Controller
{
    protected int $statusCode = Response::HTTP_OK;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode($statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    protected function respondWithSuccess($statusCode = Response::HTTP_OK): JsonResponse
    {
        return $this->setStatusCode($statusCode)
            ->respondWithArray(['success' => true]);
    }

    protected function respondWithArray(array $array, array $headers = []): JsonResponse
    {
        $response = \Response::json($array, $this->statusCode, $headers);

        $response->header('Content-Type', 'application/json');

        return $response;
    }

    protected function respondWithError($message): JsonResponse
    {
        if ($this->statusCode === Response::HTTP_OK) {
            trigger_error(
                'You better have a really good reason for erroring on a 200...',
                E_USER_WARNING
            );
        }

        return $this->respondWithArray([
            'message' => $message,
        ]);
    }

    public function errorForbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->setStatusCode(Response::HTTP_FORBIDDEN)
            ->respondWithError($message);
    }

    public function errorInternalError(string $message = 'Internal Error'): JsonResponse
    {
        return $this->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->respondWithError($message);
    }

    public function errorNotFound(string $message = 'Resource Not Found'): JsonResponse
    {
        return $this->setStatusCode(Response::HTTP_NOT_FOUND)
            ->respondWithError($message);
    }

    public function errorUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->setStatusCode(Response::HTTP_UNAUTHORIZED)
            ->respondWithError($message);
    }
}
