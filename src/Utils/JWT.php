<?php
namespace App\Utils;

use DateTimeImmutable;
use Exception;

/**
 * Lightweight JWT encoder/decoder for HS256 tokens.
 */
class JWT
{
    private string $secret;
    private string $issuer;
    private string $audience;
    private int $expiration;

    public function __construct(array $config)
    {
        $this->secret = $config['secret'];
        $this->issuer = $config['issuer'];
        $this->audience = $config['audience'];
        $this->expiration = $config['expiration'];
    }

    public function encode(array $payload): string
    {
        $issuedAt = new DateTimeImmutable();
        $expire = $issuedAt->modify("+{$this->expiration} seconds");

        $tokenPayload = array_merge($payload, [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $issuedAt->getTimestamp(),
            'nbf' => $issuedAt->getTimestamp(),
            'exp' => $expire->getTimestamp(),
        ]);

        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = $this->base64UrlEncode(json_encode($tokenPayload));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', "$header.$body", $this->secret, true));

        return "$header.$body.$signature";
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;
        $signature = $this->base64UrlEncode(hash_hmac('sha256', "$headerB64.$payloadB64", $this->secret, true));

        if (!hash_equals($signature, $signatureB64)) {
            throw new Exception('Invalid signature.');
        }

        $payload = json_decode($this->base64UrlDecode($payloadB64), true);
        if (!$payload) {
            throw new Exception('Invalid payload.');
        }

        $now = time();
        if (($payload['nbf'] ?? 0) > $now || ($payload['exp'] ?? 0) < $now) {
            throw new Exception('Token expired.');
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
