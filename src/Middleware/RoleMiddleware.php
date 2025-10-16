<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Middleware verifying user role matches allowed roles.
 */
class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->getAttribute('user');
        if (!$user || !in_array($user['role'], $this->allowedRoles, true)) {
            Response::error('권한이 없습니다.', 403);
            return;
        }

        return $next($request);
    }
}
