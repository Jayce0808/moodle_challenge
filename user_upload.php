<?php 

include "./models/User.php";
include "./exceptions/InvalidUserException.php";

define("DB_NAME", "moodle_test_db");

// var_dump(phpinfo());
// die;
function main(): void {
    try {
        $options = getopt("u:p:h:", ["create_table", "dry_run", "file:", "help"]);
        if (isset($options["help"])) {
            displayCommandLineDirectives();
        } else {
            $conn = connectToDB($options);
            if (isset($options["create_table"])) { //do not take any further action if create table is specified 
                buildUsersTable($conn);
            } else if (!isset($options["file"])) {
                throw new Exception("A file must be provided!");
            } else {
                validateFile($options["file"]); //throws exception if invalid
                $users = readCSV($options["file"]);
                if (!isset($options['dry_run'])) {
                    insertUsers($conn, $users);                    
                }
            }
        } 
    } catch (Exception $e) {
        echo $e->getMessage() . "\n"; 
    } 
}

function readCSV($filename): array {
    $validRows = [];
    $stream = fopen($filename, "r");
    fgetcsv($stream); //skip header
    while ($row = fgetcsv($stream)) {
        try {
            $row = array_map("trim", $row); //trim all what space from each user field
            $user = new User($row[0], $row[1], $row[2]);
            $validRows[] = $user;
        } catch (InvalidUserException $e) {
            //we only want to catch exceptions thrown from an invalid row here, 
            //other exceptions should still be caught in the main function as they are unintentional 
            echo $e->getMessage() . "\n";
        }
    }
    return $validRows;
}

function insertUsers($conn, $users) {
    $sql = "INSERT INTO users (name, surname, email) VALUES (?,?,?)";
    $stmt = $conn->prepare($sql);

    try {
        $conn->beginTransaction();
        foreach ($users as $user) {
            try {
                $res = $stmt->execute([$user->getName(), $user->getSurname(), $user->getEmail()]);
            } catch (PDOException $e) {
                // Check if the error is a duplicate key violation
                if ($e->getCode() == '23505') {  // PostgreSQL unique violation
                    echo "Duplicate entry error: Email '" . $user->getEmail() . "' already exists.\n";
                    $conn->rollback();
                    continue; //TODO: fix bug where the code stops inserting users after finding a duplicate email
                } else {
                    var_dump($e);
                    throw $e;  // Re-throw other exceptions
                }
                // $conn->rollback();
                // return;  // Exit the function after rollback
            }
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function buildDB($conn) {
    try {
        // Create the new database
        $sql = "CREATE DATABASE " . DB_NAME;
        $conn->exec($sql);

        echo "Database '" . DB_NAME . "' created successfully.\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

function buildUsersTable($conn) {

    $proceed = readline("Warning, this will delete any pre existing 'users' data, do you wish to proceed? (Y/n)"); //get the user to confirm they want to drop the table
    if ($proceed === "Y") {
        $sql = "DROP TABLE users;";
        $conn->exec($sql);
        $sql = "CREATE TABLE users(
            email VARCHAR(255) UNIQUE PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            surname VARCHAR(50) NOT NULL);";
        $conn->exec($sql);
    }
}

/**
 * Returns a PDO is 
 * @param mixed $params
 * @throws \Exception
 * @return false|PDO
 */
function connectToDB($params) {
    if (!isset($params["dry_run"])) { // params are not required for a dry run
        if (!isset($params["u"])) {
            throw new Exception("Error: PostgreSQL username must be provided.");
        } else if (!isset($params["p"])) {
            throw new Exception("Error: PostgreSQL password must be provided.");
        } else if (!isset($params["h"])) {
            throw new Exception("Error: PostgreSQL host must be provided.");
        }
        $hostname = $params["h"];
        $username = $params["u"];
        $password = $params["p"];
        //check if the DB with DB_NAME already exists, if not create it first before connecting to it 
        $initConn = new PDO("pgsql:host=$hostname", $username, $password);
        $dbExists = databaseExists($initConn, DB_NAME);
        if (!$dbExists) {
            buildDB($initConn);
        }
        $conn = new PDO("pgsql:host=$hostname;dbname=" . DB_NAME, $username, $password);
        $initConn = null; // close conn
        return $conn;
    } else {
        return false;
    }
}

function validateFile($filename) {
    if (!file_exists($filename)) {
        throw new Exception("Error: File '$filename' not found.\n");
    } 
    if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'csv') {
        throw new Exception("Error: The provided file '$filename' is not a CSV.\n");
    }
    return true;
}

function displayCommandLineDirectives() {
    echo "Usage: user_upload.php [options] [--] [args...]\n";
    echo "--file [csv file name] - this is the name of the CSV to be parsed.\n";
    echo "--create_table - this will cause the PostgreSQL users table to be built (and no further action will be taken).\n";
    echo "--dry_run - this will be used with the --fi le directive in case we want to run the script but not insert into the database. All other functions will be executed, but the database won't be altered.\n";
    echo "-u - PostgreSQL username.\n";
    echo "-p - PostgreSQL password.\n";
    echo "-h - PostgreSQL host.\n";
}

function databaseExists($conn, $dbName) {
    $sql = "SELECT 1 FROM pg_database WHERE datname = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$dbName]);
    return (bool) $stmt->fetchColumn();  // Returns true if database exists, false otherwise
}

main();