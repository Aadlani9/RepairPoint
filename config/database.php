<?php
/**
 * RepairPoint - Sistema de Gestión de Talleres de Reparación
 * Configuración de Base de Datos
 *
 * @author TecnoFix Team
 * @version 1.0
 */

// Prevenir acceso directo
if (!defined('SECURE_ACCESS')) {
    die('Acceso denegado');
}

// ===================================================
// CONFIGURACIÓN DE BASE DE DATOS
// ===================================================

// Configuración para entorno de desarrollo
$config['database'] = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'repairpoint',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Configuración para entorno de producción
// Descomenta y modifica estas líneas en producción
/*
$config['database'] = [
    'host' => 'tu_servidor_mysql',
    'username' => 'tu_usuario_mysql',
    'password' => 'tu_contraseña_mysql',
    'database' => 'repairpoint_prod',
    'charset' => 'utf8mb4',
    'port' => 3306
];
*/

// ===================================================
// CLASE DE CONEXIÓN A BASE DE DATOS
// ===================================================

class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $username;
    private $password;
    private $database;
    private $charset;
    private $port;

    /**
     * Constructor privado para patrón Singleton
     */
    private function __construct() {
        global $config;

        $this->host = $config['database']['host'];
        $this->username = $config['database']['username'];
        $this->password = $config['database']['password'];
        $this->database = $config['database']['database'];
        $this->charset = $config['database']['charset'];
        $this->port = $config['database']['port'];

        $this->connect();
    }

    /**
     * Obtener instancia única de la base de datos
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Conectar a la base de datos
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset={$this->charset}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);

        } catch (PDOException $e) {
            error_log("Error de conexión a base de datos: " . $e->getMessage());
            die("Error de conexión a la base de datos. Contacte al administrador.");
        }
    }

    /**
     * Obtener conexión PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Ejecutar consulta SELECT
     */
    public function select($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en SELECT: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecutar consulta INSERT
     */
    public function insert($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $result = $stmt->execute($params);
            return $result ? $this->connection->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error en INSERT: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecutar consulta UPDATE
     */
    public function update($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error en UPDATE: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecutar consulta DELETE
     */
    public function delete($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error en DELETE: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener un solo registro
     */
    public function selectOne($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error en SELECT ONE: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Confirmar transacción
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Revertir transacción
     */
    public function rollback() {
        return $this->connection->rollback();
    }

    /**
     * Verificar si hay conexión activa
     */
    public function isConnected() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Cerrar conexión
     */
    public function closeConnection() {
        $this->connection = null;
    }

    /**
     * Prevenir clonación del objeto
     */
    private function __clone() {}

    /**
     * Prevenir deserialización del objeto
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar la instancia de Database");
    }
}

// ===================================================
// FUNCIONES AUXILIARES DE BASE DE DATOS
// ===================================================

/**
 * Obtener instancia de base de datos
 */
function getDB() {
    return Database::getInstance();
}

/**
 * Escapar cadena para SQL
 */
function escapeString($string) {
    $db = Database::getInstance();
    return $db->getConnection()->quote($string);
}

/**
 * Verificar conexión a base de datos
 */
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        return $db->isConnected();
    } catch (Exception $e) {
        error_log("Test de conexión falló: " . $e->getMessage());
        return false;
    }
}

?>