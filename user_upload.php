<?php 
/**
 * This script is responsible for managing a users DB table
 * It has two process, creating the table with --create_table and processing the data in the csv provided with --file
 * @version PHP8.3 
 */

include "./models/User.php";
include "./exceptions/InvalidUserException.php";
include "./utils/db_utils.php";

/**
 * The entry to this script, will read the command line directives and move to the appropriate function depending on the directives.
 * If --help is listed, all other command line directives are ignored and the info is displayed to STDOUT 
 * If --create_table is listed, create or recreate the users table 
 * Other --file must be provided with a valid file 
 * This file must contain a list of users which will be processed and inserted into the users DB 
 * Any error are displayed to STDOUT   
 * @return void
 */
function main(): void {
    try {
        $options = getopt("u:p:h:", ["create_table", "dry_run", "file:", "help", "db:"]);
        $dbName = isset($options["db"]) ? $options["db"] : "postgres"; //default db is 'postgres' unless another is provided
        if (isset($options["help"]) || count($options) === 0) {
            displayCommandLineDirectives(); //if help directive is provided or no directives are provided then we display the info and exit 
        } else {
            $conn = setupDBConnection($options, $dbName);
            if (isset($options["create_table"])) { //do not take any further action if create table is specified 
                buildUsersTable($conn);
            } else if (!isset($options["file"])) { //if we are not creating a table then a file must be provided 
                throw new Exception("Error: A file must be provided!");
            } else {
                processFile($conn, $options); //a file is provided and needs to be validated and processed 
            }
        } 
    } catch (Exception $e) {
        echo $e->getMessage() . "\n"; 
    } 
}

/**
 * Reads the csv at $filename and creates a list of user objects, validating each user during the process and only inserting valid users 
 * @param string $filename
 * @return User[]
 */
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

/**
 * This will insert an array User objects into the 'users' table of the specified conn
 * @param PDO $conn
 * @param User[] $users
 * @return void
 */
function insertUsers($conn, $users): void {
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

/**
 * This will check if a users table already exists. If it does not then the table will be created
 * with 3 fields; email, name and surname. 
 * If it does, the user will be prompted with an option to delete the pre exisiting table before creating a new table. 
 * @param PDO $conn
 * @return void
 */
function buildUsersTable($conn): void {

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
 * Validates the required paramaters are provided within the command line directives, throws an exception if not and creates a PDO object if they are. 
 * @param mixed $params
 * @param string $dbName
 * @throws \Exception
 * @return bool|PDO
 */
function setupDBConnection($params, $dbName) {
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
        return connectToDB($hostname, $username, $password, $dbName);
    } else {
        return false;
    }
}

/**
 * Check if the provided $filename exists on the server and is a CSV. 
 * @param mixed $filename
 * @throws \Exception
 * @return bool
 */
function validateFile($filename) {
    if (!file_exists($filename)) {
        throw new Exception("Error: File '$filename' not found.\n");
    } 
    if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'csv') {
        throw new Exception("Error: The provided file '$filename' is not a CSV.\n");
    }
    return true;
}

/**
 * Echos the command line directives for this script 
 * @return void
 */
function displayCommandLineDirectives(): void {
    echo "Usage: user_upload.php [options] [--] [args...]\n";
    echo "--file [csv file name] - this is the name of the CSV to be parsed.\n";
    echo "--create_table - this will cause the PostgreSQL users table to be built (and no further action will be taken).\n";
    echo "--dry_run - this will be used with the --file directive in case we want to run the script but not insert into the database. All other functions will be executed, but the database won't be altered.\n";
    echo "--db [db name] - this is optional and will determine which DB the script updates. If the DB with this name does not exist, it will automatically be created, if left blank the default is the 'postgres' DB.\n";
    echo "-u - PostgreSQL username.\n";
    echo "-p - PostgreSQL password.\n";
    echo "-h - PostgreSQL host.\n";
}

/**
 * Checks if the file is valid and reads the data from the csv. 
 * If a dry_run is not specified in the options then the data is inserted into the users table 
 * Throws an exception if there is no users table. 
 * @param PDO $conn
 * @param mixed $options
 * @throws \Exception
 * @return void
 */
function processFile($conn, $options): void {
    validateFile($options["file"]); //throws exception if invalid
    $users = readCSV($options["file"]);
    if (!isset($options['dry_run'])) {
        if (tableExists($conn, "users")) {
            insertUsers($conn, $users);                    
        } else {
            throw new Exception("Error: There is no users table to insert data into.");
        }
    }
}

main();