# Parse_Practiscore

=======
Download and parse practiscore data into several CSV files

 Practiscore data is not of very high quality.  Much effort is made to reject matches that are 
 not USPSA matches or have data that makes them look like not USPSA matches.



Files
-----
get_files.sh  - Download local copy of PS files.  Requires AWS command line
parse.php - Walks through all downloaded files and parses USPSA data into several CSV files

Output
-------

division.csv - Information on division/pf/class and number of shooters for a match
dq.csv       - Information on DQ rule for each match.  This data is unreliable
matches.csv  - Information on matches, number of shooters, number of stages, data/time, club, etc

