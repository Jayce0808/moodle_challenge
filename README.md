# moodle_challenge

# To Do List

1. ~~Run from command line with options~~
2. ~~Implement --help option~~ 
3. ~~Handle different options correctly~~ 
4. ~~Read csv~~
5. ~~Make a user class~~
6. ~~Validate email~~
7. Create DB programatically (implement --create_table option)
8. Implement --dry_run option  
9. Insert records 
10. Implement testing? 

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

# Project Strucure
```
├───exceptions
    ├───InvalidUserException.php
├───models
    ├───User.php 
├───user_upload.php
├───users.csv
```

# Future Improvements
- Logging Errors 
- GUI for users to upload docs
- Only allow certain non-alphabet characters in names such as - and '

# Assumptions
- CSV will always be in the order name, surname, email
- Names & surnames can have non-alphabet characters in them (e.g. Sam!! and O'connor are valid)
- All fields should be trimmed as white spaces shouldn't be allowed 
- Names such as o'connor should be converted into O'connor and not O'Connor