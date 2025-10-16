<?php
namespace App\Core;

use App\Utils\Logger;

/**
 * Base Controller providing helper methods.
 */
abstract class Controller
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function json(array $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function log(string $action, string $entityType, ?int $entityId, array $oldValues, array $newValues): void
    {
        Logger::audit($this->request, $action, $entityType, $entityId, $oldValues, $newValues);
    }
}
