<?php
// Configuración de la base de datos
define('DB_SERVER', 'hercules');
define('DB_DATABASE', 'calidad');
define('DB_USERNAME', 'sa');
define('DB_PASSWORD', 'Sky2022*!');

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Consulta de Nómina');
define('APP_VERSION', '1.0.0');

// Zona horaria
date_default_timezone_set('America/Bogota');

// Función para conectar a la base de datos
function getDBConnection() {
    try {
        $pdo = new PDO(
            "sqlsrv:server=" . DB_SERVER . ";Database=" . DB_DATABASE, 
            DB_USERNAME, 
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
            ]
        );
        return $pdo;
    } catch(PDOException $e) {
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        throw new Exception("Error de conexión a la base de datos");
    }
}
?>
