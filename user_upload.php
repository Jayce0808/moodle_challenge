<?php 

include "./models/User.php";
include "./exceptions/InvalidUserException.php";

function main(): void {
    try {
        $options = getopt("u:p:h:", ["create_table", "dry_run", "file:", "help", "db:"]);
        $dbName = isset($options["db"]) ? $options["db"] : "postgres";
        if (isset($options["help"])) {
            displayCommandLineDirectives();
        } else {
            $conn = connectToDB($options, $dbName);
            if (isset($options["create_table"])) { //do not take any further action if create table is specified 
                buildUsersTable($conn);
            } else if (!isset($options["file"])) {
                throw new Exception("Error: A file must be provided!");
            } else {
                validateFile($options["file"]); //throws exception if invalid
                $users = readCSV($options["file"]);
                if (!isset($options['dry_run'])) {
                    if (tableExists($conn, "users")) {
                        insertUsers($conn, $users);                    
                    } else {
                        throw new Exception("Error: There is no users table insert data into.");
                    }
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
        foreach ($users as $user) {
            try {
                $res = $stmt->execute([$user->getName(), $user->getSurname(), $user->getEmail()]);
                if ($res) {
                    echo $user->print() . " inserted successfully!\n";
                }
            } catch (PDOException $e) {
                // Check if the error is a duplicate key violation
                if ($e->getCode() == '23505') {  // PostgreSQL unique violation
                    echo "Duplicate entry error: Email '" . $user->getEmail() . "' already exists.\n";
                } else {
                    var_dump($e);
                    throw $e;  // Re-throw other exceptions
                }
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function buildDB($conn, $dbName) {
    try {
        // Create the new database
        $sql = "CREATE DATABASE $dbName";
        $conn->exec($sql);

        echo "Database '$dbName' created successfully.\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

function buildUsersTable($conn) {

    if (tableExists($conn, "users")) {
        $proceed = readline("Warning, this will delete the pre existing 'users' table, do you wish to proceed? (Y/n)"); //get the user to confirm they want to drop the table
        if ($proceed === "Y") {
            $sql = "DROP TABLE users;";
            $conn->exec($sql);
        } else {
            return;
        }
    }
    $sql = "CREATE TABLE users(
        email VARCHAR(255) UNIQUE PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        surname VARCHAR(50) NOT NULL);";
    $conn->exec($sql);
}

/**
 * Returns a PDO is 
 * @param mixed $params
 * @throws \Exception
 * @return false|PDO
 */
function connectToDB($params, $dbName) {
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
        $dbExists = databaseExists($initConn, $dbName);
        if (!$dbExists) {
            buildDB($initConn, $dbName);
        }
        $conn = new PDO("pgsql:host=$hostname;dbname=$dbName", $username, $password);
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
    echo "--dry_run - this will be used with the --file directive in case we want to run the script but not insert into the database. All other functions will be executed, but the database won't be altered.\n";
    echo "--db [db name] - this is optional and will determine which DB the script updates. If the DB with this name does not exist, it will automatically be created, if left blank the default is the 'postgres' DB\n";
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

function tableExists($conn, $tableName) {
    $sql = "SELECT 1 FROM pg_tables WHERE tablename = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([strtolower($tableName)]); //Returns true if table exists, false otherwise
    return (bool) $stmt->fetchColumn();
}

main();