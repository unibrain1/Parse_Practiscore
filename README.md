# Parse_Practiscore

Download and parse practiscore data into several CSV files

 Practiscore data is not of very high quality.  Much effort is made to reject matches that are 
 not USPSA matches or have data that makes them look like not USPSA matches.


Files
-----
* get_files.sh  - Download local copy of PS files.  Requires AWS command line
* parse.php - Walks through all downloaded files and parses USPSA data into several CSV files

Reports
-------

* division.csv - Information on division/pf/class and number of shooters for a match
* dq.csv       - Information on DQ rule for each match.  This data is unreliable
* matches.csv  - Information on matches, number of shooters, number of stages, data/time, club, etc
* Report.xlsx - Spreadsheet with data and sample reports that can be created

Todo
----
* The data is large and is better suited to being added to a database
* Change parse.php to only parse new records vs all records
* Ignore known bad matches
* Fix dates that are obviously incorrect

