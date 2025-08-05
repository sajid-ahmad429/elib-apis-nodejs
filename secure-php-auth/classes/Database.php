<?php
/**
 * Database Class using MySQLi
 * Singleton pattern with connection pooling and security features
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $transaction_level = 0;
    private static $query_count = 0;
    private static $total_query_time = 0;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get database instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            // Create MySQLi connection
            $this->connection = new mysqli(
                DB_HOST, 
                DB_USER, 
                DB_PASS, 
                DB_NAME, 
                DB_PORT
            );
            
            // Check for connection errors
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset for security
            if (!$this->connection->set_charset(DB_CHARSET)) {
                throw new Exception("Error setting charset: " . $this->connection->error);
            }
            
            // Optimize MySQL settings
            $this->connection->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            $this->connection->query("SET SESSION time_zone = '+00:00'");
            
            // Enable autocommit
            $this->connection->autocommit(true);
            
            logSecurityEvent('database_connected', [
                'host' => DB_HOST,
                'database' => DB_NAME,
                'charset' => DB_CHARSET
            ]);
            
        } catch (Exception $e) {
            logSecurityEvent('database_connection_failed', [
                'error' => $e->getMessage(),
                'host' => DB_HOST,
                'database' => DB_NAME
            ]);
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get MySQLi connection
     */
    public function getConnection() {
        // Check if connection is still alive
        if (!$this->connection->ping()) {
            $this->connect(); // Reconnect if connection lost
        }
        return $this->connection;
    }
    
    /**
     * Execute a prepared statement
     */
    public function execute($sql, $params = [], $types = '') {
        start_profiling('database_query');
        
        try {
            // Auto-detect parameter types if not provided
            if (empty($types) && !empty($params)) {
                $types = $this->detectParamTypes($params);
            }
            
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            
            // Bind parameters if provided
            if (!empty($params)) {
                if (!$stmt->bind_param($types, ...$params)) {
                    throw new Exception("Bind param failed: " . $stmt->error);
                }
            }
            
            // Execute statement
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $stmt->close();
            
            self::$query_count++;
            self::$total_query_time += end_profiling('database_query');
            
            return $result;
            
        } catch (Exception $e) {
            logSecurityEvent('database_query_failed', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Select query with automatic parameter binding
     */
    public function select($sql, $params = [], $types = '') {
        $result = $this->execute($sql, $params, $types);
        
        if ($result === false) {
            return [];
        }
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    /**
     * Select single row
     */
    public function selectOne($sql, $params = [], $types = '') {
        $result = $this->select($sql, $params, $types);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Insert query
     */
    public function insert($sql, $params = [], $types = '') {
        $this->execute($sql, $params, $types);
        return $this->connection->insert_id;
    }
    
    /**
     * Update query
     */
    public function update($sql, $params = [], $types = '') {
        $this->execute($sql, $params, $types);
        return $this->connection->affected_rows;
    }
    
    /**
     * Delete query
     */
    public function delete($sql, $params = [], $types = '') {
        $this->execute($sql, $params, $types);
        return $this->connection->affected_rows;
    }
    
    /**
     * Count query
     */
    public function count($sql, $params = [], $types = '') {
        $result = $this->selectOne($sql, $params, $types);
        return (int) reset($result);
    }
    
    /**
     * Check if record exists
     */
    public function exists($sql, $params = [], $types = '') {
        return $this->count($sql, $params, $types) > 0;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        if ($this->transaction_level === 0) {
            $this->connection->begin_transaction();
        }
        $this->transaction_level++;
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        $this->transaction_level--;
        if ($this->transaction_level === 0) {
            $this->connection->commit();
        } elseif ($this->transaction_level < 0) {
            $this->transaction_level = 0;
            throw new Exception("No transaction to commit");
        }
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        if ($this->transaction_level > 0) {
            $this->transaction_level = 0;
            $this->connection->rollback();
        }
    }
    
    /**
     * Escape string for security
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    /**
     * Get affected rows
     */
    public function affectedRows() {
        return $this->connection->affected_rows;
    }
    
    /**
     * Get database info
     */
    public function getInfo() {
        return [
            'server_info' => $this->connection->server_info,
            'server_version' => $this->connection->server_version,
            'client_info' => $this->connection->client_info,
            'client_version' => $this->connection->client_version,
            'host_info' => $this->connection->host_info,
            'protocol_version' => $this->connection->protocol_version
        ];
    }
    
    /**
     * Get query statistics
     */
    public static function getStats() {
        return [
            'query_count' => self::$query_count,
            'total_query_time' => self::$total_query_time,
            'average_query_time' => self::$query_count > 0 ? self::$total_query_time / self::$query_count : 0
        ];
    }
    
    /**
     * Auto-detect parameter types for prepared statements
     */
    private function detectParamTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_double($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 's'; // Default to string
            }
        }
        return $types;
    }
    
    /**
     * Simple query builder for basic operations
     */
    public function table($table) {
        return new QueryBuilder($this, $table);
    }
    
    /**
     * Database health check
     */
    public function healthCheck() {
        try {
            $result = $this->selectOne("SELECT 1 as status, NOW() as current_time");
            return [
                'status' => 'healthy',
                'connection' => 'active',
                'current_time' => $result['current_time'],
                'stats' => self::getStats()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'connection' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Clean up expired sessions and rate limits
     */
    public function cleanup() {
        try {
            // Clean expired sessions
            $this->delete("DELETE FROM user_sessions WHERE expires_at < NOW()");
            
            // Clean expired rate limits
            $this->delete("DELETE FROM rate_limits WHERE expires_at < NOW()");
            
            // Clean old security logs (keep last 30 days)
            $this->delete("DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            
            // Clean old login attempts (keep last 7 days)
            $this->delete("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 7 DAY)");
            
            logSecurityEvent('database_cleanup', ['status' => 'completed']);
            
        } catch (Exception $e) {
            logSecurityEvent('database_cleanup_failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    private function __wakeup() {}
    
    /**
     * Close connection on destruction
     */
    public function __destruct() {
        if ($this->connection && !$this->connection->connect_error) {
            $this->connection->close();
        }
    }
}

/**
 * Simple Query Builder Class
 */
class QueryBuilder {
    private $db;
    private $table;
    private $select = '*';
    private $where = [];
    private $joins = [];
    private $orderBy = [];
    private $limit = null;
    private $groupBy = [];
    
    public function __construct($db, $table) {
        $this->db = $db;
        $this->table = $table;
    }
    
    public function select($columns = '*') {
        $this->select = $columns;
        return $this;
    }
    
    public function where($column, $operator, $value) {
        $this->where[] = [$column, $operator, $value];
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = "$column $direction";
        return $this;
    }
    
    public function limit($limit, $offset = 0) {
        $this->limit = $offset > 0 ? "$offset, $limit" : $limit;
        return $this;
    }
    
    public function get() {
        $sql = "SELECT {$this->select} FROM {$this->table}";
        $params = [];
        $types = '';
        
        if (!empty($this->where)) {
            $conditions = [];
            foreach ($this->where as $condition) {
                $conditions[] = "{$condition[0]} {$condition[1]} ?";
                $params[] = $condition[2];
                $types .= is_int($condition[2]) ? 'i' : 's';
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }
        
        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        return $this->db->select($sql, $params, $types);
    }
    
    public function first() {
        $this->limit(1);
        $result = $this->get();
        return !empty($result) ? $result[0] : null;
    }
    
    public function count() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        $types = '';
        
        if (!empty($this->where)) {
            $conditions = [];
            foreach ($this->where as $condition) {
                $conditions[] = "{$condition[0]} {$condition[1]} ?";
                $params[] = $condition[2];
                $types .= is_int($condition[2]) ? 'i' : 's';
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $result = $this->db->selectOne($sql, $params, $types);
        return (int) $result['count'];
    }
}
?>