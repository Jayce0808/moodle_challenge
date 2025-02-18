<?php 

/**
 * This script contains generic DB functions
 * @version PHP8.3 
 */

/**
 * Checks if DB $dbName exists at $conn
 * @param PDO $conn
 * @param string $dbName
 * @return bool
 */
function databaseExists($conn, $dbName): bool {
    try {
        $sql = "SELECT 1 FROM pg_catalog.pg_database WHERE datname = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$dbName]);
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Checks if table $tableName exists at $conn
 * @param PDO $conn
 * @param string $tableName
 * @return bool
 */
function tableExists($conn, $tableName): bool {
    $sql = "SELECT 1 FROM information_schema.tables WHERE table_name = ? AND table_schema = 'public'"; //information_scheme can have multiple entries so specify table_schema as public for desired result
    $stmt = $conn->prepare($sql);
    $stmt->execute([strtolower($tableName)]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Builds a DB with $dbName at $conn
 * @param PDO $conn
 * @param string $dbName
 * @return void
 */
function buildDB($conn, $dbName): void {
    try {
        //sanitise database name (PostgreSQL requires valid identifiers)
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);

        //create the new database
        $sql = "CREATE DATABASE $dbName";
        $conn->exec($sql);

        echo "Database '$dbName' created successfully.\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

/**
 * Connects to the DB $dbName, creating it if it does not exist
 * Uses $hostName, $userName & $password as credentials
 * @param string $hostName
 * @param string $userName
 * @param string $password
 * @param string $dbName
 * @return PDO|null
 */
function connectToDB($hostName, $userName, $password, $dbName): PDO|null {
    try {
        // Connect to default database (postgres)
        $initConn = new PDO("pgsql:host=$hostName;dbname=postgres", $userName, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        //check if DB exists, if not, create it
        if (!databaseExists($initConn, $dbName)) {
            buildDB($initConn, $dbName);
        }

        //connect to the actual database
        $conn = new PDO("pgsql:host=$hostName;dbname=$dbName", $userName, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        return $conn;
    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage() . "\n";
        return null;
    }
}
