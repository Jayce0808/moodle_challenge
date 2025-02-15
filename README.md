# moodle_challenge

# To Do List

1. Run from command line with options
2. Implement --help option 
3. Implement --dry_run option  
2. Handle different options correctly 
3. Read csv
4. Validate email 
5. Create DB programatically (implement --create_table option)
6. Insert records 

# Run Instructions
Run `php user_upload.php`

# Command Line Directives 
 --file [csv file name] – this is the name of the CSV to be parsed.
 --create_table – this will cause the PostgreSQL users table to be built (and no further action will be taken).
 --dry_run – this will be used with the --fi le directive in case we want to run the script but not insert into the database. All other functions will be executed, but the database won't be altered.
 -u – PostgreSQL username.
 -p – PostgreSQL password.
 -h – PostgreSQL host.
 --help – which will output the above list of directives with details.