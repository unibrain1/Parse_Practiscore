<?php
ini_set('memory_limit', '2G');

// Get the list of matches to parse
$dir = "./files";
$reportsDir = "./Reports/";


define('DIVISIONS', 'Open,Limited,Limited 10,Carry Optics,Production,Single Stack,Revolver,PCC,Invalid');
define('PF', 'MAJOR,MINOR,SUBMINOR,Invalid');
define('CL', 'G,M,A,B,C,D,U');

$matches = array();
$handle = opendir($dir);

if ($handle) {
    while (($entry = readdir($handle)) !== FALSE) {
        $matches[] = $dir . "/" . $entry;
    }
}
closedir($handle);

// Remove . .. and .DS_Store
$pos = array_search('./files/..', $matches);
unset($matches[$pos]);
$pos = array_search('./files/.', $matches);
unset($matches[$pos]);
$pos = array_search('./files/.DS_Store', $matches);
unset($matches[$pos]);

$logFile = fopen($reportsDir . "log.csv", "w+");  // Truncates file
$fields = array(
    "Match",
    "Comment",
    'A',
    'B',
    'C'
);
fputcsv($logFile, $fields);

// Create CSV files with headers.  Truncate if they exist
$matchFile = fopen($reportsDir . "matches.csv", "w+");  // Truncates file
$fields = array(
    "ID",
    "Match Date",
    "Results URL",
    "Create Date",
    "Mod Date",
    "Match Name",
    "Club Name",
    "Club Code",
    "Match Type",
    "Match Subtype",
    "Match Level",
    "Device Arch",
    "Device Model",
    "App Version",
    "OS Version",
    "Num Shooters",
    "Num Stages",
    // "Num Match Notes",
    // "Match Notes",
);
fputcsv($matchFile, $fields);

$dqFile = fopen( $reportsDir . "dq.csv", "w+");  // Truncates file
$fields = array(
    "ID",
    "Match Date",
    "Results URL",
    "Create Date",
    "Mod Date",
    "Match Name",
    "Club Name",
    "Club Code",
    "Match Type",
    "Match Subtype",
    "Match Level",
    "DQ Rule",
    "DQ Description",
    "DQ Count",
);
fputcsv($dqFile, $fields);

$shooterFile = fopen($reportsDir . "division.csv", "w+");  // Truncates file
$fields = array(
    "ID",
    "Match Date",
    "Results URL",
    "Create Date",
    "Mod Date",
    "Match Name",
    "Club Name",
    "Club Code",
    "Match Type",
    "Match Subtype",
    "Match Level",
    "Division",
    "PF",
    "Class",
    "Count",
);

fputcsv($shooterFile, $fields);

$numMatches = sizeof($matches);
echo "Parsing $numMatches Match Data\n";

$matchID = 0; // Match ID
$dqID = 0; // 
$shooterID = 0; // 

foreach ($matches as $match) {
    $matchData = getMatchData($match);
    // Skip any NULL results or "My First Match"
    if (
        $matchData == NULL ||
        (getData('match_name', $matchData) == 'My First Match') ||
        (getData('match_name', $matchData) == 'My First Match')
        || (getData('match_name', $matchData) == 'Test Post')
    ) {
        continue;
    }

    // Recreate the match results URL
    // $match is in the form  files/guid/ - I need the guid
    $segments = explode('/', $match);
    $guid = $segments[2];

    $matchURL = 'https://practiscore.com/results/new/' . $guid;
    // Display % complete
    $percentComplete = ($matchID / $numMatches) * 100;


    echo chr(27) . "[0G";  //Backs cursor to the beginning of the line
    printf("%06.3f %% complete - %s", $percentComplete, $matchURL);

    // Get ] information for USPSA matches.  This filters out a lot of the noise but there are still non-USPSA matches that slip through
    if (
        getData('match_type', $matchData) == 'uspsa_p' &&
        (getData('match_subtype', $matchData) == 'uspsa'  ||
            getData('match_subtype', $matchData) == 'none'  ||
            getData('match_subtype', $matchData) == ''  ||
            getData('match_subtype', $matchData) == 'null'
        )
    ) {

        // This looks like a USPSA match.  Check to see if match_cats (i.e. Division ) makes sense
        $valid = TRUE; // Assume division are valid

        // Are divisions valid?
        foreach ($matchData['match_cats'] as $division) {
            switch ($division) {
                case 'Open':
                case 'OPEN':
                case 'Carry Optic':
                case 'Carry Optics':
                case 'CARRY OPTICS':
                case 'CO':
                case 'Limited':
                case 'LTD':
                case 'Limited 10':
                case 'Limited-10':
                case 'LTDTEN':
                case 'Production':
                case 'PROD':
                case 'SS':
                case 'Single Stack':
                case 'Revolver':
                case 'REV':
                case 'Pistol Caliber Carbine':
                case 'PCC':
                case 'PCCO':
                    break;

                default:
                    $valid = FALSE;
            }
        }
        if (!$valid) {
            continue;
        }

        // Get Division/PF/Class info 
        // A place to hold Div/PF/Class counts 
        $divisionPfClass = array();
        foreach (explode(',', DIVISIONS) as $div) {
            foreach (explode(',', PF) as $pf) {
                foreach (explode(',', CL) as $class) {
                    $divisionPfClass[$div][$pf][$class] = 0;
                }
            }
        }

        //
        // Data quaility is poor so make the data better.
        //

        // 
        // Normailze Division Names
        //

        foreach ($matchData['match_shooters'] as $shooter) {
            switch ($division = getData('sh_dvp', $shooter)) {
                case 'Open':
                case 'OPEN':
                    $division = 'Open';
                    break;

                case 'Carry Optic':
                case 'carry optics':
                case 'CARRY OPTICS':
                case 'CO':
                    $division = 'Carry Optics';
                    break;

                case 'Limited':
                case 'LTD':
                    $division = 'Limited';
                    break;

                case 'Limited 10':
                case 'Limited-10':
                case 'LTDTEN':
                    $division = 'Limited 10';
                    break;

                case 'PRODUCTION':
                case 'Production':
                case 'PROD':
                    $division = 'Production';
                    break;

                case 'SS':
                case 'Single Stack':
                case 'SingleStack':
                    $division = 'Single Stack';
                    break;

                case 'Revolver':
                case 'revolver':
                case 'REV':
                    $division = 'Revolver';
                    break;

                case 'Pistol Caliber Carbine':
                case 'PCC':
                case 'Pcc':
                case 'PCCO':
                    $division = 'PCC';
                    break;

                default:
                    $fields = array(
                        $matchURL,
                        'Invalid', 'Division', $division,
                    );
                    fputcsv($logFile, $fields);
                    $valid = FALSE;  // Invalid division in shooter record
            }
            if (!$valid) {
                break;
            }
            //
            // Deal with class inconsistencies
            //
            switch ($class = getData('sh_grd', $shooter)) {
                case 'GM':
                case 'G':
                    $class = 'G';
                    break;

                case 'M':
                case 'A':
                case 'B':
                case 'C':
                case 'D':
                    break;

                case 'U':
                case 'X':
                case 'Unclassified':
                case '':
                    $class = 'U';
                    break;

                default:
                    $class = 'U';
            }
            //
            // Fix PF data
            //
            switch ($pf = getData('sh_pf', $shooter)) {
                case 'MAJOR':
                case 'MINOR':
                case 'SUBMINOR':
                    break;

                default:
                    $valid = FALSE;

                    $fields = array(
                        $matchURL,
                        'Invalid', 'PF', $pf,
                    );
                    fputcsv($logFile, $fields);
                    $pf = 'Invalid';
            }
            if (!$valid) {
                break;
            }

            // Check to see if Div/PF makes sense
            if (
                $pf == 'MAJOR' && ($division == 'PCC' ||
                    $division == 'Production' ||
                    $division == 'Carry Optics')
            ) {
                $fields = array(
                    $matchURL,
                    'Fix', 'Division/PF', $division, $pf,
                );
                fputcsv($logFile, $fields);
                $pf = 'MINOR';
            }
            if (!$valid) {
                break;
            }
            $divisionPfClass[$division][$pf][$class]++;
        }

        // Looks to be a valid match
        if ($valid) {
            //
            // Output 1 row per division/pf/class if non-zero
            //
            foreach (explode(',', DIVISIONS) as $div) {
                foreach (explode(',', PF) as $pf) {
                    foreach (explode(',', CL) as $class) {
                        if ($divisionPfClass[$div][$pf][$class] != 0) {
                            $fields = array(
                                $shooterID++,
                                getData('match_date', $matchData),
                                $matchURL,
                                getData('match_creationdate', $matchData),
                                getData('match_modifieddate', $matchData),
                                getData('match_name', $matchData),
                                getData('match_clubname', $matchData),
                                getData('match_clubcode', $matchData),
                                getData('match_type', $matchData),
                                getData('match_subtype', $matchData),
                                getData('match_level', $matchData),
                                $div,
                                $pf,
                                $class,
                            );
                            array_push($fields, $divisionPfClass[$div][$pf][$class]);

                            fputcsv($shooterFile, $fields);
                        }
                    }
                }
            }

            //
            // Now walk through the matchScores and find any shooter who as DQ'd
            // If there is information at the stage score level it will be a DQ code that we need to translate to the actual DQ rule and reason..
            //

            $dqArray = [];

            $scoreData = getScoreData($match);
            if (!array_key_exists('match_dqs', $matchData)) {
                continue;
            }
            $match_dqs = $matchData["match_dqs"];

            foreach ($scoreData['match_scores'] as $stage) {
                foreach ($stage['stage_stagescores'] as $score) {
                    if (array_key_exists('dqs', $score) && $score['dqs'] != "") {
                        // Found a DQ
                        $dq_key      = array_search($score['dqs'][0], array_column($match_dqs, 'uuid'));
                        // // Translate the DQ code to Rule and Description
                        array_push($dqArray, $match_dqs[$dq_key]['name']);
                    }
                }
            }

            $dqTypeCount = array_count_values($dqArray);
            arsort($dqTypeCount);
            foreach ($dqTypeCount as $dq => $count) {
                $strArray = explode(' ', $dq, 2);
                // Print it all out
                $fields = array(
                    $dqID++,
                    getData('match_date', $matchData),
                    $matchURL,
                    getData('match_creationdate', $matchData),
                    getData('match_modifieddate', $matchData),
                    getData('match_name', $matchData),
                    getData('match_clubname', $matchData),
                    getData('match_clubcode', $matchData),
                    getData('match_type', $matchData),
                    getData('match_subtype', $matchData),
                    getData('match_level', $matchData),

                    $strArray[0],
                    $strArray[1],
                    $count,
                );
                fputcsv($dqFile, $fields);
            }


            // 
            // Print out basic match information
            //
            $fields = array(
                $matchID,
                getData('match_date', $matchData),
                $matchURL,
                getData('match_creationdate', $matchData),
                getData('match_modifieddate', $matchData),
                getData('match_name', $matchData),
                getData('match_clubname', $matchData),
                getData('match_clubcode', $matchData),
                getData('match_type', $matchData),
                getData('match_subtype', $matchData),
                getData('match_level', $matchData),

                getData('device_arch', $matchData),
                getData('device_model', $matchData),
                getData('app_version', $matchData),
                getData('os_version', $matchData),

                countData('match_shooters', $matchData),
                countData('match_stages', $matchData),
            );
            fputcsv($matchFile, $fields);
        }
    }
    $matchID++;
}

fclose($matchFile);
fclose($dqFile);

echo "\nDone\n";

// Get the match data
function getMatchData($match)
{
    $file = $match . "/match_def.json";

    if (file_get_contents($file)) {
        $string = file_get_contents($file);
        // Decoding JSON data
        return json_decode($string, true);
    } else {
        return NULL;
    }
}
function getScoreData($match)
{
    $file = $match . "/match_scores.json";
    if (file_get_contents($file)) {
        $string = file_get_contents($file);
        return json_decode($string, true);
    } else {
        return NULL;
    }
}
// Some of the keys may not be present.  Return something useful if they are not
function getData($key, $array)
{
    if (array_key_exists($key, $array)) {
        return $array[$key];
    } else {
        return "";
    }
}

function countData($key, $array)
{
    if (array_key_exists($key, $array)) {
        return count($array[$key]);
    } else {
        return 0;
    }
}
