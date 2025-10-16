<?php
namespace App\Utils;

use App\Core\Database;
use App\Core\Request;

/**
 * Application logger writing audit trail to database.
 */
class Logger
{
    public static function audit(Request $request, string $action, string $entityType, ?int $entityId, array $oldValues, array $newValues): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent)');
        $stmt->execute([
            'user_id' => $request->getAttribute('user_id') ?? 0,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => json_encode($oldValues, JSON_UNESCAPED_UNICODE),
            'new_values' => json_encode($newValues, JSON_UNESCAPED_UNICODE),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
