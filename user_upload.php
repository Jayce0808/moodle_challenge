<?php 

include "./models/User.php";
include "./exceptions/InvalidUserException.php";

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
                buildUsersTable();
            } else if (!isset($options["file"])) {
                throw new Exception("A file must be provided!");
            } else {
                validateFile($options["file"]); //throws exception if invalid
                $users = readCSV($options["file"]);
                insertUsers($users);
            }
        } 
    } catch (Exception $e) {
        var_dump($e);
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

function insertUsers($users) {

}

function buildUsersTable() {

}

/**
 * Returns a PDO is 
 * @param mixed $params
 * @throws \Exception
 * @return false|PDO
 */
function connectToDB($params) {
    if (!isset($params["dry_run"])) {
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
        // $conn = new PDO("pgsql:host=$hostname;dbname=template1", $username, $password);
        $conn = new PDO("pgsql:host=$hostname;dbname=template1", $username, $password);

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

main();