<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Http\Response;

/**
 * Helper class for building common response scenarios.
 *
 * Provides static methods for rapid response construction with appropriate
 * headers and status codes.
 */
class ResponseBuilder
{
    /**
     * Build a successful JSON response.
     *
     * @param array<string, mixed> $data Response payload
     * @param int $status HTTP status (default 200)
     */
    public static function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * Build a success response with message.
     *
     * @param string $message Success message
     * @param int $status HTTP status (default 200)
     * @param array<string, mixed> $data Additional data
     */
    public static function success(string $message, int $status = 200, array $data = []): Response
    {
        return Response::json(array_merge(['success' => true, 'message' => $message], $data), $status);
    }

    /**
     * Build an error response with message.
     *
     * @param string $message Error message
     * @param int $status HTTP status (default 400)
     * @param array<string, mixed> $data Additional error details
     */
    public static function error(string $message, int $status = 400, array $data = []): Response
    {
        return Response::json(
            array_merge(['success' => false, 'error' => $message], $data),
            $status
        );
    }

    /**
     * Build a validation error response.
     *
     * @param array<string, string> $errors Field-specific errors
     * @param string $message General validation message
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): Response
    {
        return Response::json([
            'success' => false,
            'error' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Build a "not found" JSON response.
     *
     * @param string $message Not found message
     */
    public static function notFound(string $message = 'Resource not found'): Response
    {
        return Response::json(['success' => false, 'error' => $message], 404);
    }

    /**
     * Build an "unauthorized" response.
     *
     * @param string $message Unauthorized message
     */
    public static function unauthorized(string $message = 'Unauthorized'): Response
    {
        return Response::json(['success' => false, 'error' => $message], 401);
    }

    /**
     * Build a "forbidden" response.
     *
     * @param string $message Forbidden message
     */
    public static function forbidden(string $message = 'Forbidden'): Response
    {
        return Response::json(['success' => false, 'error' => $message], 403);
    }

    /**
     * Build a "too many requests" response.
     *
     * @param string $message Rate limit message
     * @param int $retryAfter Seconds to wait before retry
     */
    public static function tooManyRequests(string $message = 'Too many requests', int $retryAfter = 60): Response
    {
        $response = Response::json(['success' => false, 'error' => $message], 429);
        $response->setHeader('Retry-After', (string) $retryAfter);

        return $response;
    }

    /**
     * Build a redirect response with message in headers (for HTMX).
     *
     * @param string $url Redirect URL
     * @param string $message Optional message for display
     */
    public static function redirectWithMessage(string $url, string $message = ''): Response
    {
        $response = Response::redirect($url);

        if ($message !== '') {
            $response->setHeader('X-Flash-Message', $message);
        }

        return $response;
    }
}
