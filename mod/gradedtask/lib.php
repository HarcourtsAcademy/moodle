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
 * Library of functions and constants for module gradedtask
 *
 * @package mod_gradedtask
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/** GRADEDTASK_MAX_NAME_LENGTH = 50 */
define("GRADEDTASK_MAX_NAME_LENGTH", 50);
define("GRADEDTASK_STATUS_COMPLETE", 1);
define("GRADEDTASK_STATUS_INCOMPLETE", 0);

/**
 * @uses GRADEDTASK_MAX_NAME_LENGTH
 * @param object $gradedtask
 * @return string
 */
function get_gradedtask_name($gradedtask) {
    $name = strip_tags(format_string($gradedtask->intro,true));
    if (core_text::strlen($name) > GRADEDTASK_MAX_NAME_LENGTH) {
        $name = core_text::substr($name, 0, GRADEDTASK_MAX_NAME_LENGTH)."...";
    }

    if (empty($name)) {
        // arbitrary name
        $name = get_string('modulename','gradedtask');
    }

    return $name;
}
/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $gradedtask
 * @return bool|int
 */
function gradedtask_add_instance($gradedtask) {
    global $DB;

    $gradedtask->name = get_gradedtask_name($gradedtask);
    $gradedtask->timemodified = time();

    return $DB->insert_record("gradedtask", $gradedtask);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $gradedtask
 * @return bool
 */
function gradedtask_update_instance($gradedtask) {
    global $DB;

    $gradedtask->name = get_gradedtask_name($gradedtask);
    $gradedtask->timemodified = time();
    $gradedtask->id = $gradedtask->instance;

    return $DB->update_record("gradedtask", $gradedtask);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function gradedtask_delete_instance($id) {
    global $DB;

    if (! $gradedtask = $DB->get_record("gradedtask", array("id"=>$id))) {
        return false;
    }

    $result = true;
    
    if (! $DB->delete_records("gradedtask_status", array("gradedtask"=>$gradedtask->id))) {
        $result = false;
    }

    if (! $DB->delete_records("gradedtask", array("id"=>$gradedtask->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
function gradedtask_get_coursemodule_info($coursemodule) {
    global $DB;

    if ($gradedtask = $DB->get_record('gradedtask', array('id'=>$coursemodule->instance), 'id, name, intro, introformat')) {
        if (empty($gradedtask->name)) {
            // gradedtask name missing, fix it
            $gradedtask->name = "gradedtask{$gradedtask->id}";
            $DB->set_field('gradedtask', 'name', $gradedtask->name, array('id'=>$gradedtask->id));
        }
        $info = new cached_cm_info();
        // no filtering hre because this info is cached and filtered later
        $info->content = format_module_intro('gradedtask', $gradedtask, $coursemodule->id, false);
        $info->name  = $gradedtask->name;
        return $info;
    } else {
        return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function gradedtask_reset_userdata($data) {
    return array();
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function gradedtask_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * Delete grade item for given graded task
 *
 * @category grade
 * @param object $gradedtask object
 * @return object gradedtask
 */
function gradedtask_grade_item_delete($gradedtask) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/gradedtask', $gradedtask->course, 'mod', 'gradedtask', $gradedtask->id, 0,
            null, array('deleted' => 1));
}

/**
 * Create or update the grade item for given graded task
 *
 * @category grade
 * @param object $gradedtask object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function gradedtask_grade_item_update($gradedtask, $grades = null) {
  /* TODO: Create gradedtask_grade_item_update */
}

/**
 * Update grades in central gradebook
 *
 * @category grade
 * @param object $gradedtask the graded task settings.
 * @param int $userid specific user only, 0 means all users.
 * @param bool $nullifnone If a single user is specified and $nullifnone is true a grade item with a null rawgrade will be inserted
 */
function gradedtask_update_grades($gradedtask, $userid = 0, $nullifnone = true) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if ($gradedtask->grade == 0) {
        gradedtask_grade_item_update($gradedtask);

    } else if ($grades = gradedtask_get_user_grades($gradedtask, $userid)) {
        gradedtask_grade_item_update($gradedtask, $grades);

    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        gradedtask_grade_item_update($gradedtask, $grade);

    } else {
        gradedtask_grade_item_update($gradedtask);
    }
}

/**
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function gradedtask_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:                return false;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_NO_VIEW_LINK:            return true;

        default: return null;
    }
}

/**
 * Resize the image, if required, then generate an img tag and, if required, a link to the full-size image
 * @param stored_file $file the image file to process
 * @param int $maxwidth the maximum width allowed for the image
 * @param int $maxheight the maximum height allowed for the image
 * @return string HTML fragment to add to the gradedtask
 */
function gradedtask_generate_resized_image(stored_file $file, $maxwidth, $maxheight) {
    global $CFG;

    $fullurl = moodle_url::make_draftfile_url($file->get_itemid(), $file->get_filepath(), $file->get_filename());
    $link = null;
    $attrib = array('alt' => $file->get_filename(), 'src' => $fullurl);

    if ($imginfo = $file->get_imageinfo()) {
        // Work out the new width / height, bounded by maxwidth / maxheight
        $width = $imginfo['width'];
        $height = $imginfo['height'];
        if (!empty($maxwidth) && $width > $maxwidth) {
            $height *= (float)$maxwidth / $width;
            $width = $maxwidth;
        }
        if (!empty($maxheight) && $height > $maxheight) {
            $width *= (float)$maxheight / $height;
            $height = $maxheight;
        }

        $attrib['width'] = $width;
        $attrib['height'] = $height;

        // If the size has changed and the image is of a suitable mime type, generate a smaller version
        if ($width != $imginfo['width']) {
            $mimetype = $file->get_mimetype();
            if ($mimetype === 'image/gif' or $mimetype === 'image/jpeg' or $mimetype === 'image/png') {
                require_once($CFG->libdir.'/gdlib.php');
                $tmproot = make_temp_directory('mod_gradedtask');
                $tmpfilepath = $tmproot.'/'.$file->get_contenthash();
                $file->copy_content_to($tmpfilepath);
                $data = generate_image_thumbnail($tmpfilepath, $width, $height);
                unlink($tmpfilepath);

                if (!empty($data)) {
                    $fs = get_file_storage();
                    $record = array(
                        'contextid' => $file->get_contextid(),
                        'component' => $file->get_component(),
                        'filearea'  => $file->get_filearea(),
                        'itemid'    => $file->get_itemid(),
                        'filepath'  => '/',
                        'filename'  => 's_'.$file->get_filename(),
                    );
                    $smallfile = $fs->create_file_from_string($record, $data);

                    // Replace the image 'src' with the resized file and link to the original
                    $attrib['src'] = moodle_url::make_draftfile_url($smallfile->get_itemid(), $smallfile->get_filepath(),
                                                                    $smallfile->get_filename());
                    $link = $fullurl;
                }
            }
        }

    } else {
        // Assume this is an image type that get_imageinfo cannot handle (e.g. SVG)
        $attrib['width'] = $maxwidth;
    }

    $img = html_writer::empty_tag('img', $attrib);
    if ($link) {
        return html_writer::link($link, $img);
    } else {
        return $img;
    }
}
