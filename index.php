<?php

declare(strict_types=1);

const WEBHOOK_TOKEN       = 'e3f1c9b7a4d2f5e6c8b1a9d0f7e3c2b1';
const LOG_DIRECTORY       = __DIR__ . '/webhook_logs';
const DEFAULT_FILE_PREFIX = 'no_reference_';

function sendJsonResponse(int $statusCode, array $body): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($body);
    exit;
}

function logPayloadToFile(array $orderDetails): void
{
    // Log the payload to Render logs
    error_log("Received payload: " . json_encode($orderDetails));

    // Optionally, log to a file (ensure directory exists)
    if (!is_dir(LOG_DIRECTORY)) {
        mkdir(LOG_DIRECTORY, 0777, true);
    }

    foreach ($orderDetails as $order) {
        $referenceCode = $order['reference_code'] ?? (DEFAULT_FILE_PREFIX . uniqid());
        $safeFileName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $referenceCode);
        $filePath = LOG_DIRECTORY . '/' . $safeFileName . '.json';

        file_put_contents($filePath, json_encode($order, JSON_PRETTY_PRINT));
    }
}

function main(): void
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = trim(substr($authHeader, 7));
    if (stripos($authHeader, 'Bearer ') !== 0 || $token !== WEBHOOK_TOKEN) {
        sendJsonResponse(401, ['error' => 'Unauthorized']);
    }

    $rawBody = file_get_contents('php://input');
    $decodedPayload = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedPayload)) {
        sendJsonResponse(400, ['error' => 'Invalid JSON payload']);
    }

    try {
        logPayloadToFile($decodedPayload);
        sendJsonResponse(200, ['status' => 'Payload logged']);
    } catch (Throwable $e) {
        error_log('[Webhook Log Error] ' . $e->getMessage());
        sendJsonResponse(500, ['error' => 'Internal Server Error']);
    }
}

main();
