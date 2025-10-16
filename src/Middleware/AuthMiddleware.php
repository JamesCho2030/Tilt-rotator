<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Utils\JWT;
use Closure;
use Exception;

/**
 * Middleware ensuring user is authenticated via JWT.
 */
class AuthMiddleware
{
    private JWT $jwt;
    private User $users;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $this->jwt = new JWT($config['jwt']);
        $this->users = new User();
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $request->bearerToken();
            if (!$token) {
                Response::error('인증 토큰이 필요합니다.', 401);
                return;
            }

            $payload = $this->jwt->decode($token);
            $user = $this->users->findById((int) ($payload['sub'] ?? 0));
            if (!$user || !$user['is_active']) {
                Response::error('유효하지 않은 사용자입니다.', 401);
                return;
            }

            $request->setAttribute('user', $user);
            $request->setAttribute('user_id', $user['id']);

            return $next($request);
        } catch (Exception $e) {
            Response::error('인증 실패: ' . $e->getMessage(), 401);
            return;
        }
    }
}
