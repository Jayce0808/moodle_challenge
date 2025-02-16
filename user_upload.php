<?php 

function main() {
    try {
        $options = getopt("u:p:h", ["create_table", "dry_run", "file:", "help"]);
        if (isset($options["help"])) {
            displayCommandLineDirectives();
        }
        else if (isset($options["create_table"])) {
            buildUsersTable();
        }
        else if (!isset($options["file"])) {
            throw new Exception("A file must be provided!");
        } else {
            validateFile($options["file"]); //throws exception if invalid
            $users = readCSV($options["file"]);
            insertUsers($users);
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    } 
}

function readCSV($filename) {
    $validRows = [];
    $stream = fopen($filename, "r");
    while ($row = fgetcsv($stream)) {
        try {
            //TODO: make a class to handle user data
            validateRow($row); //throw exception if row is invalid 
            $validRows[] = $row;
        } catch (Exception $e) {//TODO: create custom exception to use here instead
            echo $e->getMessage();
        }
    }
    return $validRows;
}

function validateRow() {
    return true;
}

function insertUsers() {

}

function buildUsersTable() {

}

function connectToDB() {

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