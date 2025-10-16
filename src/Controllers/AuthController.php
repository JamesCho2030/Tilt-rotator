<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Utils\JWT;
use App\Utils\Validator;
use InvalidArgumentException;

/**
 * Authentication controller managing login and profile retrieval.
 */
class AuthController extends Controller
{
    private User $users;
    private JWT $jwt;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->users = new User();
        $config = require __DIR__ . '/../../config/app.php';
        $this->jwt = new JWT($config['jwt']);
    }

    public function login(): void
    {
        try {
            $payload = $this->request->all();
            Validator::validate($payload, [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = $this->users->findByEmail($payload['email']);
            if (!$user || !password_verify($payload['password'], $user['password'])) {
                Response::error('아이디 또는 비밀번호가 올바르지 않습니다.', 401);
                return;
            }

            if (!$user['is_active']) {
                Response::error('비활성화된 계정입니다.', 403);
                return;
            }

            $token = $this->jwt->encode([
                'sub' => $user['id'],
                'role' => $user['role'],
                'name' => $user['name'],
            ]);

            $this->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => htmlspecialchars($user['email']),
                    'name' => htmlspecialchars($user['name']),
                    'role' => $user['role'],
                    'department' => $user['department'],
                    'position' => $user['position'],
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            Response::error('검증 오류', 422, json_decode($e->getMessage(), true));
        }
    }

    public function profile(): void
    {
        $user = $this->request->getAttribute('user');
        if (!$user) {
            Response::error('인증 정보가 없습니다.', 401);
            return;
        }

        $this->json([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => htmlspecialchars($user['email']),
                'name' => htmlspecialchars($user['name']),
                'role' => $user['role'],
                'department' => $user['department'],
                'position' => $user['position'],
            ],
        ]);
    }
}
