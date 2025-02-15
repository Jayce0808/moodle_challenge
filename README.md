# moodle_challenge

# To Do List

1. ~~Run from command line with options~~
2. ~~Implement --help option~~ 
3. Handle different options correctly 
4. Read csv
5. Validate email 
6. Create DB programatically (implement --create_table option)
7. Implement --dry_run option  
8. Insert records 
9. Implement testing? 

# Run Instructions
Run `php user_upload.php`

# Command Line Directives 
 --file [csv file name] – this is the name of the CSV to be parsed.
 --create_table – this will cause the PostgreSQL users table to be built (and no further action will be taken).
 --dry_run – this will be used with the --file directive in case we want to run the script but not insert into the database. All other functions will be executed, but the database won't be altered.
 -u – PostgreSQL username.
 -p – PostgreSQL password.
 -h – PostgreSQL host.
 --help – which will output the above list of directives with details.