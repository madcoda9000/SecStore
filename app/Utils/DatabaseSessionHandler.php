<?php
namespace App\Utils;

use SessionHandlerInterface;
use PDO;
use PDOException;

/**
 * Class Name: DatabaseSessionHandler
 *
 * Session-Handler für MariaDB/MySQL-basierte Session-Storage
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-09-15
 */
class DatabaseSessionHandler implements SessionHandlerInterface
{
    /** @var PDO */
    private $pdo;
    
    /** @var string */
    private $table = 'sessions';
    
    /** @var int */
    private $maxLifetime;

    /**
     * Constructor
     */
    public function __construct(PDO $pdo, int $maxLifetime = 3600)
    {
        $this->pdo = $pdo;
        $this->maxLifetime = $maxLifetime;
    }

    /**
     * Open session
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    /**
     * Close session
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Read session data
     */
    public function read($sessionId): string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT data FROM {$this->table} 
                 WHERE id = :id AND expires > :now"
            );
            
            $stmt->execute([
                ':id' => $sessionId,
                ':now' => time()
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_COLUMN);
            
            return $result ? $result : '';
            
        } catch (PDOException $e) {
            error_log("Session read error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Write session data
     */
    public function write($sessionId, $sessionData): bool
    {
        try {
            $expires = time() + $this->maxLifetime;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ipAddress = $this->getClientIp();
            $userId = $_SESSION['user_id'] ?? null;
            
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} 
                 (id, data, expires, user_id, ip_address, user_agent, created_at, updated_at) 
                 VALUES (:id, :data, :expires, :user_id, :ip_address, :user_agent, :created_at, :updated_at)
                 ON DUPLICATE KEY UPDATE 
                 data = VALUES(data), 
                 expires = VALUES(expires),
                 user_id = VALUES(user_id),
                 ip_address = VALUES(ip_address),
                 user_agent = VALUES(user_agent),
                 updated_at = VALUES(updated_at)"
            );
            
            return $stmt->execute([
                ':id' => $sessionId,
                ':data' => $sessionData,
                ':expires' => $expires,
                ':user_id' => $userId,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent,
                ':created_at' => date('Y-m-d H:i:s'),
                ':updated_at' => date('Y-m-d H:i:s')
            ]);
            
        } catch (PDOException $e) {
            error_log("Session write error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Destroy session
     */
    public function destroy($sessionId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->table} WHERE id = :id"
            );
            
            return $stmt->execute([':id' => $sessionId]);
            
        } catch (PDOException $e) {
            error_log("Session destroy error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Garbage collection - remove expired sessions
     */
    public function gc($maxLifetime): int|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->table} WHERE expires < :now"
            );
            
            $stmt->execute([':now' => time()]);
            
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Session GC error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get active sessions count
     */
    public function getActiveSessionsCount(): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE expires > :now"
            );
            
            $stmt->execute([':now' => time()]);
            
            return (int)$stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Session count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get active sessions for a specific user
     */
    public function getUserSessions(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, ip_address, user_agent, created_at, updated_at, expires 
                 FROM {$this->table} 
                 WHERE user_id = :user_id AND expires > :now
                 ORDER BY updated_at DESC"
            );
            
            $stmt->execute([
                ':user_id' => $userId,
                ':now' => time()
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get user sessions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Terminate all sessions for a user (except current)
     */
    public function terminateUserSessions(int $userId, string $exceptSessionId = ''): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
            $params = [':user_id' => $userId];
            
            if ($exceptSessionId) {
                $sql .= " AND id != :except_id";
                $params[':except_id'] = $exceptSessionId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("Terminate user sessions error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get session statistics for admin dashboard
     */
    public function getSessionStats(): array
    {
        try {
            $stats = [];
            
            // Total active sessions
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as total FROM {$this->table} WHERE expires > :now"
            );
            $stmt->execute([':now' => time()]);
            $stats['active_sessions'] = (int)$stmt->fetchColumn();
            
            // Unique users with active sessions
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(DISTINCT user_id) as unique_users 
                 FROM {$this->table} 
                 WHERE expires > :now AND user_id IS NOT NULL"
            );
            $stmt->execute([':now' => time()]);
            $stats['active_users'] = (int)$stmt->fetchColumn();
            
            // Anonymous sessions
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as anonymous 
                 FROM {$this->table} 
                 WHERE expires > :now AND user_id IS NULL"
            );
            $stmt->execute([':now' => time()]);
            $stats['anonymous_sessions'] = (int)$stmt->fetchColumn();
            
            // Sessions by hour (last 24h)
            $stmt = $this->pdo->prepare(
                "SELECT DATE_FORMAT(FROM_UNIXTIME(created_at), '%H:00') as hour, 
                        COUNT(*) as count
                 FROM {$this->table} 
                 WHERE created_at > :yesterday
                 GROUP BY hour 
                 ORDER BY hour"
            );
            $stmt->execute([':yesterday' => time() - 86400]);
            $stats['hourly_sessions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Session stats error: " . $e->getMessage());
            return [];
        }
    }
}