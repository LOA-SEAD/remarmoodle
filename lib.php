<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants for module remarmoodle
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the remarmoodle specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_remarmoodle
 * @copyright  2015 Rener Baffa da Silva <renerbaffa@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Example constant, you probably want to remove this :-)
 */
define('REMARMOODLE_ULTIMATE_ANSWER', 42);

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function remarmoodle_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the remarmoodle into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $remarmoodle Submitted data from the form in mod_form.php
 * @param mod_remarmoodle_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted remarmoodle record
 */
function remarmoodle_add_instance(stdClass $remarmoodle, mod_remarmoodle_mod_form $mform = null) {
    global $DB, $USER;

    $remarmoodle->timecreated = time();
    
    $remarmoodle->game_id = $remarmoodle->game;

    //get the current user's hash
    $user = $DB->get_record('remarmoodle_user', array('moodle_username' => $USER->username));

    $curl = new curl();
    $path = 'http://localhost:8080/moodle/resources_list';
    $json = $curl->post($path, array('hash' => $user->hash));

    $obj = json_decode($json);
    
    foreach($obj->resources as $resource) {
        if(property_exists($resource, "moodleUrl") && $resource->id == $remarmoodle->game_id) {
            $remarmoodle->url = $resource->moodleUrl;
        }
    }

    $remarmoodle->id = $DB->insert_record('remarmoodle', $remarmoodle);

    remarmoodle_grade_item_update($remarmoodle);

    return $remarmoodle->id;
}

/**
 * Updates an instance of the remarmoodle in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $remarmoodle An object from the form in mod_form.php
 * @param mod_remarmoodle_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function remarmoodle_update_instance(stdClass $remarmoodle, mod_remarmoodle_mod_form $mform = null) {
    global $DB;

    $remarmoodle->timemodified = time();
    $remarmoodle->id = $remarmoodle->instance;

    // You may have to add extra stuff in here.

    $result = $DB->update_record('remarmoodle', $remarmoodle);

    remarmoodle_grade_item_update($remarmoodle);

    return $result;
}

/**
 * Removes an instance of the remarmoodle from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function remarmoodle_delete_instance($id) {
    global $DB;

    if (! $remarmoodle = $DB->get_record('remarmoodle', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.

    $DB->delete_records('remarmoodle', array('id' => $remarmoodle->id));

    remarmoodle_grade_item_delete($remarmoodle);

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $remarmoodle The remarmoodle instance record
 * @return stdClass|null
 */
function remarmoodle_user_outline($course, $user, $mod, $remarmoodle) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $remarmoodle the module instance record
 */
function remarmoodle_user_complete($course, $user, $mod, $remarmoodle) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in remarmoodle activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function remarmoodle_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link remarmoodle_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function remarmoodle_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link remarmoodle_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function remarmoodle_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function remarmoodle_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function remarmoodle_get_extra_capabilities() {
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of remarmoodle?
 *
 * This function returns if a scale is being used by one remarmoodle
 * if it has support for grading and scales.
 *
 * @param int $remarmoodleid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given remarmoodle instance
 */
function remarmoodle_scale_used($remarmoodleid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('remarmoodle', array('id' => $remarmoodleid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of remarmoodle.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any remarmoodle instance
 */
function remarmoodle_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('remarmoodle', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given remarmoodle instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $remarmoodle instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function remarmoodle_grade_item_update($remarmoodle, $reset=NULL, $grades=NULL) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }
    
    //return grade_update('mod/remarmoodle', $remarmoodle->course, 'mod', 'remarmoodle', $remarmoodle->id, 0, $grades, $params);

    $item = array();
    $item['itemname'] = clean_param($remarmoodle->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($remarmoodle->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $remarmoodle->grade;
        $item['grademin']  = 0;
    } else if ($remarmoodle->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$remarmoodle->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('mod/remarmoodle', $remarmoodle->course, 'mod', 'remarmoodle',
            $remarmoodle->id, 0, $grades, $item);
}

/**
 * Delete grade item for given remarmoodle instance
 *
 * @param stdClass $remarmoodle instance object
 * @return grade_item
 */
function remarmoodle_grade_item_delete($remarmoodle) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/remarmoodle', $remarmoodle->course, 'mod', 'remarmoodle',
            $remarmoodle->id, 0, null, array('deleted' => 1));
}

/**
 * Update remarmoodle grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $remarmoodle instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function remarmoodle_update_grades($remarmoodle, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }
    
    // Populate array of grade objects indexed by userid.
    $newgrade = new stdClass();
    $newgrade->userid = $userid;
    
    if ($remarmoodle->rawgrade != null) {
        $newgrade->rawgrade = $remarmoodle->rawgrade;
    }
    else {
        $newgrade->rawgrade = NULL;
    }

    grade_update('mod/remarmoodle', $remarmoodle->course, 'mod', 'remarmoodle', $remarmoodle->id, 0, $newgrade);
}



/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function remarmoodle_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for remarmoodle file areas
 *
 * @package mod_remarmoodle
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function remarmoodle_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the remarmoodle file areas
 *
 * @package mod_remarmoodle
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the remarmoodle's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function remarmoodle_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding remarmoodle nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the remarmoodle module instance
 * @param stdClass $course current course record
 * @param stdClass $module current remarmoodle instance record
 * @param cm_info $cm course module information
 */
function remarmoodle_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the remarmoodle settings
 *
 * This function is called when the context for the page is a remarmoodle module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $remarmoodle remarmoodle administration node
 */
function remarmoodle_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $remarmoodlenode=null) {
    // TODO Delete this function and its docblock, or implement it.
}


function getError($error_code, $detail = null) {
    $ret = array (
        'message' => '',
        'description' => ''
    );

    switch ($error_code) {
        case 01:
            $ret['message'] = 'ERROR 01: Wrong type';
            $ret['description'] = '\''.$detail.'\' is not a valid type. You should check the documentation to know what types are supported.';
            break;
        
        case 02:
            $ret['message'] = 'ERROR 02: Wrong length';
            $ret['description'] = 'The field \''.$detail.'\' has a invalid length. Length options for BINARY and TEXT field type are only "small", "medium" or "big".';
            break;
        
        case 03:
            $ret['message'] = 'ERROR 03: Wrong length';
            $ret['description'] = 'The field \''.$detail.'\' has a invalid length. Length should be numeric and greater than 0.';
            break;
        
        case 04:
            $ret['message'] = 'ERROR 04: Wrong default value';
            $ret['description'] = 'The field \''.$detail.'\' has a invalid default value. The default value type doesn\'t match with the field type.';
            break;
        
        case 05:
            $ret['message'] = 'ERROR 05: Primary key is needed';
            $ret['description'] = 'Table needs to have at least one primary key.';
            break;
        
        case 06:
            $ret['message'] = 'ERROR 06: Table already exists';
            $ret['description'] = 'A table with same name already exists in the moodle. Please contact the developers to delete or solve this problem.';
            break;

        default:
            $ret['message'] = 'Error code not found.';
            $ret['description'] = 'Please contact the developers';
            break;
    }

    return $ret;
}