# moodle_challenge

## Task Description 
Create a command line executable PHP script, which accepts a CSV file as input and processes the CSV file (according to the command line directives and assumptions covered later in this document). The parsed file data is to be inserted into a PostgreSQL database. A CSV file containing test data is provided as part of this task, your script must be able to process that file appropriately.

## Required Libraries
- php8.3
- pdo_pgsql
- pgsql

## Run Instructions
1. Ensure all required libraries are installed
2. To create a new users table or wipe the current users table run `php user_upload.php -u [username] -p [password] -h [host] --create_table`
3. To create insert users into the table run `php user_upload.php -u [username] -p [password] -h [host] --file [file name]`
4. To process the users but not insert them into the DB run `php user_upload.php -u [username] -p [password] -h [host] --file [file name] --dry_run`
5. To do the above in a custom DB run `php user_upload.php -u [username] -p [password] -h [host] --create_table --db [db name]` & `php user_upload.php -u [username] -p [password] -h [host] --file [file name] --db [db name]`

## Command Line Directives 
```
 --file [csv file name] – this is the name of the CSV to be parsed.
 --create_table – this will cause the PostgreSQL users table to be built (and no further action will be taken).
 --dry_run – this will be used with the --file directive in case we want to run the script but not insert into the database. All other functions will be executed, but the database won't be altered.
 --db [db name] - this is optional and will determine which DB the script updates. If the DB with this name does not exist, it will automatically be created, if left blank the default is the 'postgres' DB.
 -u – PostgreSQL username.
 -p – PostgreSQL password.
 -h – PostgreSQL host.
 --help – which will output the above list of directives with details.
 ```

## Project Strucure
```
├───exceptions
    ├───InvalidUserException.php
├───models
    ├───User.php 
├───utils
    ├───db_utils.php 
├───user_upload.php
├───users.csv
```

## Future Improvements
- Logging Errors 
- GUI for users to upload csv
- Only allow certain non-alphabet characters in names such as - and '
- Implement automated testing

## Assumptions
- CSV will always be in the order name, surname, email
- The CSV will be in the root directory
- Names & surnames can have non-alphabet characters in them (e.g. Sam!! and O'connor are valid)
- All fields should be trimmed as white spaces shouldn't be allowed 
- Names such as o'connor should be converted into O'connor and not O'Connor
- The input file will not contain enough users to require inserting multiple users in one query 
- If one of the emails in the csv is a duplicate, other correctly formatted users should still be inserted 
- The default DB to use is 'postgres'
- An additional --db option was included, it is okay to create a new DB using this paramater if it does not exist
- The provided user credentials have permission to create new tables and DBs 
- Creating the users table if it already exists will drop the old table first

## Bugs
- ~~Unintuative error message when trying to insert records without table exists~~ 
- ~~Scripts stops importing records when one duplicate email is found~~ 

## To Do List

1. ~~Run from command line with options~~
2. ~~Implement --help option~~ 
3. ~~Handle different options correctly~~ 
4. ~~Read csv~~
5. ~~Make a user class~~
6. ~~Validate email~~
7. ~~Create DB programatically (implement --create_table option)~~
8. ~~Implement --dry_run option~~
9. ~~Insert records~~
10. ~~Bug Fixes~~