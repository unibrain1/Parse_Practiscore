<?php
ini_set('memory_limit', '2G');

// Get the list of matches to parse
$dir        = "./files";

require_once 'class/dbHelper.php';

$db = new dbHelper();


define('DIVISIONS', 'OPEN,LIMITED,LIMITED 10,CARRY OPTICS,PRODUCTION,SINGLE STACK,REVOLVER,PCC,Invalid');
define('PF', 'MAJOR,MINOR,SUBMINOR,Invalid');
define('CL', 'G,M,A,B,C,D,U');

$matches = array();
$handle  = opendir($dir);

echo "Getting list of matches/n";


if ($handle) {
    while (($entry = readdir($handle)) !== FALSE) {
        $matches[] = $dir . "/" . $entry;
            echo chr(27) . "[0G";  //Backs cursor to the beginning of the line
            printf("%s", $entry );
    }
}
closedir($handle);
echo "Done list of matches \n";
printf("\n");


// Remove . .. and .DS_Store
$pos = array_search('./files/..', $matches);
unset($matches[$pos]);
$pos = array_search('./files/.', $matches);
unset($matches[$pos]);
$pos = array_search('./files/.DS_Store', $matches);
unset($matches[$pos]);

$numMatches = sizeof($matches);

$loopCNT   = 0; // How   many times we've gone throigh the loop.  Used for %complete

foreach ($matches as $match) {
    $matchData = getMatchData($match);

    // Recreate the match results URL
    // $match is in the form  files/guid/ - I need the guid
    $segments  = explode('/', $match);
    $matchGUID = $segments[2];

    // Display % complete
    $percentComplete = ($loopCNT++ / $numMatches) * 100;

    echo chr(27) . "[0G";  //Backs cursor to the beginning of the line
    printf("%06.3f %% complete - %s - %u of %u", $percentComplete, $matchGUID, $loopCNT, $numMatches);

    // Skip any NULL results or "My First Match" or other matches in the list of bad matches
    if (
        $matchData == NULL ||
        (getData('match_name', $matchData) == 'My First Match') ||
        (getData('match_name', $matchData) == 'My First Match') ||
        (getData('match_name', $matchData) == 'Test Post')
    ) {
        continue;
    }

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
                case 'LIMITED':
                case 'Limited':
                case 'LTD':
                case 'Limited 10':
                case 'Limited-10':
                case 'LTDTEN':
                case 'Production':
                case 'PRODUCTION':
                case 'PROD':
                case 'SS':
                case 'SINGLE STACK':
                case 'Single Stack':
                case 'REVOLVER':
                case 'Revolver':
                case 'REV':
                case 'Pistol Caliber Carbine':
                case 'PCC':
                case 'PCCO':
                    break;

                default:
                    $data = array(
                        'match_guid' => $matchGUID,
                        'Comment' => 'Invalid',
                        'A' => 'Match Division',
                        'B' => $division,
                        'C' => '',
                    );
                    dbUpsert('log', $data);

                    $valid = FALSE;  // Invalid division in shooter record            
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
        // Normailze Division/PF/Class Names for each shooter record
        //
        foreach ($matchData['match_shooters'] as $shooter) {

            // Division
            switch ($division = strtoupper(getData('sh_dvp', $shooter))) {
                case 'OPEN':
                    $division = 'OPEN';
                    break;

                case 'CARRY OPTICS':
                case 'CO':
                    $division = 'CARRY OPTICS';
                    break;

                case 'LIMITED':
                case 'LTD':
                    $division = 'LIMITED';
                    break;

                case 'LIMITED 10':
                case 'LIMITED-10':
                case 'LTDTEN':
                    $division = 'LIMITED 10';
                    break;

                case 'PRODUCTION':
                case 'PROD':
                    $division = 'PRODUCTION';
                    break;

                case 'SS':
                case 'SINGLE STACK':
                case 'SINGLESTACK':
                    $division = 'SINGLE STACK';
                    break;

                case 'REVOLVER':
                case 'REV':
                    $division = 'REVOLVER';
                    break;

                case 'PISTOL CALIBER CARBINE':
                case 'PCC':
                case 'PCCO':
                    $division = 'PCC';
                    break;

                default:
                    $data = array(
                        'match_guid' => $matchGUID,
                        'Comment' => 'Invalid',
                        'A' => 'Shooter Division',
                        'B' => $division,
                        'C' => '',
                    );
                    dbUpsert('log', $data);
                    $valid = FALSE;  // Invalid division in shooter record
            }
            if (!$valid) {
                break;
            }

            // Classification
            switch ($class = strtoupper(getData('sh_grd', $shooter))) {
                case 'GM':
                case 'G':
                case 'GRAND MASTER':
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
                case 'UNCLASSIFIED':
                case '':
                    $class = 'U';
                    break;

                default:
                    // FIX a common error where classification is something else
                    $data = array(
                        'match_guid' => $matchGUID,
                        'Comment' => 'Fix',
                        'A' => 'Class',
                        'B' => $class,
                        'C' => '',
                    );
                    dbUpsert('log', $data);
                    $class = 'U';
            }

            // Power Factor
            switch ($pf = strtoupper(getData('sh_pf', $shooter))) {
                case 'MAJOR':
                case 'MINOR':
                case 'SUBMINOR':
                    break;

                default:
                    $valid = FALSE;
                    $data = array(
                        'match_guid' => $matchGUID,
                        'Comment' => 'Invalid',
                        'A' => 'PF',
                        'B' => $pf,
                        'C' => '',
                    );
                    dbUpsert('log', $data);
            }
            if (!$valid) {
                break;
            }

            // FIX:  Some Divisions are MINOR only.  This is a common error in match registration
            //
            if ($pf == 'MAJOR' && ($division == 'PCC' || $division == 'Production' ||   $division == 'Carry Optics')) {
                $data = array(
                    'match_guid' => $matchGUID,
                    'Comment' => 'Fix',
                    'A' => 'Division/PF',
                    'B' => $division,
                    'C' => $pf,
                );
                dbUpsert('log', $data);
                $pf = 'MINOR';
            }
            // This should all now make sense so count it
            $divisionPfClass[$division][$pf][$class]++;

            // And save it
            $data = array(
                'match_guid' => $matchGUID,
                'sh_grd' => strtoupper(getData('sh_grd', $shooter)),
                'sh_ln' => strtoupper(getData('sh_ln', $shooter)),
                'sh_fn' => strtoupper(getData('sh_fn', $shooter)),
                'sh_dvp' => strtoupper(getData('sh_dvp', $shooter)),
                'sh_pf' => strtoupper(getData('sh_pf', $shooter)),
                'sh_id' => strtoupper(getData('sh_id', $shooter)),
                'sh_dq' => strtoupper(getData('sh_dq', $shooter)),
                'sh_dqrule' => strtoupper(getData('sh_dqrule', $shooter)),
            );
            dbUpsert('shooter', $data);
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

                            $data = array(
                                'match_guid' => $matchGUID,
                                'Division' => $division,
                                'PF' => $pf,
                                'Class' => $division,
                                'Count' => $divisionPfClass[$div][$pf][$class],
                            );
                            dbUpsert('division', $data);
                        }
                    }
                }
            }

            //
            // Now walk through the matchScores and find any shooter who as DQ'd
            // If there is information at the stage score level it will be a DQ code that we need to translate to the actual DQ rule and reason..
            //


            if (array_key_exists('match_dqs', $matchData)) {
                $match_dqs = $matchData["match_dqs"];
                $dqArray = [];  // One row per DQ

                $scoreData = getScoreData($match);

                // Walk through the stage scores looking for a DQ
                // TODO:  Only record the oldest (i.e. first recorded) DQ for a shooter
                foreach ($scoreData['match_scores'] as $stage) {
                    foreach ($stage['stage_stagescores'] as $score) {
                        if (array_key_exists('dqs', $score) && $score['dqs'] != "") {
                            // Found a DQ
                            // Translate the DQ code to Rule and Description
                            $dq_key      = array_search($score['dqs'][0], array_column($match_dqs, 'uuid'));
                            array_push($dqArray, $match_dqs[$dq_key]['name']);
                        }
                    }
                }

                $dqTypeCount = array_count_values($dqArray);  // Count # of DQ's per type
                arsort($dqTypeCount); // TODO Why am I sorting??
                foreach ($dqTypeCount as $dq => $count) {
                    $strArray = explode(' ', $dq, 2);  // DQ Record is RULE SPACE DESCRIPTION.  Split on space

                    $data = array(
                        'match_guid' => $matchGUID,
                        'rule' => $strArray[0],
                        'description' =>  $strArray[1],
                        'count' => $count,
                    );
                    dbUpsert('dq', $data);
                }
            }

            // TODO
            // 
            // Fix obviuosly incorrect timestamps

            // 
            // Print out basic match information
            //

            $data = array(
                'match_guid'                        =>  $matchGUID,
                'date'                        =>  getData('match_date',         $matchData),
                'ctime'                       =>  getData('match_creationdate', $matchData),
                'mtime'                       => getData('match_modifieddate', $matchData),
                'name'                        => getData('match_name',         $matchData),
                'club'                        =>   getData('match_clubname',     $matchData),
                'club_code'                   =>  getData('match_clubcode',     $matchData),
                'match_type'                  =>  getData('match_subtype',      $matchData),
                'match_subtype'               =>  getData('match_subtype',      $matchData),
                'match_level'                 =>  getData('match_level',        $matchData),
                'device_arch'                 => getData('device_arch',        $matchData),
                'device_model'                =>  getData('device_model',       $matchData),
                'app_version'                 =>  getData('app_version',        $matchData),
                'os_version'                  =>   getData('os_version',         $matchData),
                'count_shooters'              =>   countData('match_shooters',   $matchData),
                'count_stages'                =>  countData('match_stages', $matchData),
            );
            dbUpsert('matches', $data,);
        }
    }
}
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

function dbUpsert($table, $data, $match = NULL)
{
    global $db;

    $rows = $db->select($table, array('match_guid' => $data['match_guid']));
    if ($rows['status'] == 'success') {
        if (is_null($match)) {
            $match = $data;
        }
        $rows = $db->update($table, $data, $match, array('match_guid'));
    } else {
        $rows = $db->insert($table, $data, array('match_guid'));
    }
}


function dbInsert($table, $data)
{
    global $db;

    $rows = $db->insert($table, $data, array('match_guid'));
}
