<?php
class Response
{
    public static function success(mixed $data = null, string $message = "OK", int $status = 200): void
    {
        self::send(["success" => true, "message" => $message, "data" => $data], $status);
    }

    public static function error(string $message, int $code = -1, int $status = 400): void
    {
        self::send(["success" => false, "message" => $message, "code" => $code], $status);
    }

    private static function send(array $body, int $status): void
    {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($body, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
