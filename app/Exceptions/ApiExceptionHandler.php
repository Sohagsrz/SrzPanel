<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ApiExceptionHandler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                return $this->handleApiException($e);
            }
        });
    }

    /**
     * Handle API exceptions
     *
     * @param Throwable $e
     * @return JsonResponse
     */
    protected function handleApiException(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e);
        }

        if ($e instanceof AuthenticationException) {
            return $this->unauthenticated($e);
        }

        if ($e instanceof AuthorizationException) {
            return $this->unauthorized($e);
        }

        if ($e instanceof ModelNotFoundException) {
            return $this->modelNotFound($e);
        }

        if ($e instanceof TokenMismatchException) {
            return $this->tokenMismatch($e);
        }

        if ($e instanceof HttpException) {
            return $this->httpException($e);
        }

        return $this->serverError($e);
    }

    /**
     * Convert validation exception to response
     *
     * @param ValidationException $e
     * @return JsonResponse
     */
    protected function convertValidationExceptionToResponse(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    }

    /**
     * Handle unauthenticated exception
     *
     * @param AuthenticationException $e
     * @return JsonResponse
     */
    protected function unauthenticated(AuthenticationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    /**
     * Handle unauthorized exception
     *
     * @param AuthorizationException $e
     * @return JsonResponse
     */
    protected function unauthorized(AuthorizationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    /**
     * Handle model not found exception
     *
     * @param ModelNotFoundException $e
     * @return JsonResponse
     */
    protected function modelNotFound(ModelNotFoundException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Resource not found'
        ], 404);
    }

    /**
     * Handle token mismatch exception
     *
     * @param TokenMismatchException $e
     * @return JsonResponse
     */
    protected function tokenMismatch(TokenMismatchException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'CSRF token mismatch'
        ], 419);
    }

    /**
     * Handle HTTP exception
     *
     * @param HttpException $e
     * @return JsonResponse
     */
    protected function httpException(HttpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage() ?: 'HTTP error'
        ], $e->getStatusCode());
    }

    /**
     * Handle server error
     *
     * @param Throwable $e
     * @return JsonResponse
     */
    protected function serverError(Throwable $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Server error'
        ], 500);
    }
} 