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
 * Class containing data for My Learning Block block.
 *
 * @package    block_mylearning
 * @copyright  Praj Basnet <praj.basnet@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mylearning\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

class main implements renderable, templatable {
    const ICON_NOT_STARTED = 'play';
    const ICON_IN_PROGRESS = 'step-forward';
    const ICON_COMPLETED = 'repeat';

    /**
     * @var object An object containing the configuration information for the current instance of this block.
     */
    protected $config;

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $courses = $this->get_courses_for_user($USER->id);
        $show = count($courses) ? true : false;

        return [
            'userid' => $USER->id,
            'show' => $show,
            'courses' => $courses
        ];
    }

    private function get_courses_for_user($userid) {
        global $DB;
        $courses = [];

        $params = [
            'userid' => $userid
        ];

        $sql = "
            select
                c.id as courseid,
                c.fullname as title,
                cc.name as category,
                ccmp.timestarted,
	            ccmp.timecompleted
            from {enrol} e inner join {user_enrolments} ue
            on e.id = ue.enrolid
            inner join {course} c
            on c.id = e.courseid
            inner join {course_categories} cc
            on cc.id = c.category
            left outer join {course_completions} ccmp
            on ccmp.course = e.courseid
            and ccmp.userid = ue.userid
            where ue.userid = :userid
        ";

        $results = $DB->get_records_sql($sql, $params);

        foreach ($results as $result) {
            $result->status = get_string('statusnotstarted', 'block_mylearning');
            $result->icon = self::ICON_NOT_STARTED;
            $result->progress = 0;
            $result->link = "/course/view.php?id=$result->courseid";

            if ($this->get_course_progress($result->courseid, $userid) > 0) {
                $result->status = get_string('statusinprogress', 'block_mylearning');
                $result->icon = self::ICON_IN_PROGRESS;
                $result->progress = $this->get_course_progress($result->courseid, $userid);
            }

            if (isset($result->timecompleted)) {
                $result->status = get_string('statuscompleted', 'block_mylearning');
                $result->icon = self::ICON_COMPLETED;
                $result->progress = 100;
            }
            $courses[] = $result;
        }

        return $courses;
    }

    /**
     * Get course progress based on completion criteria if defined
     * or otherwise activities completed/total activities if not
     * defined.
     *
     */

    private function get_course_progress($courseid, $userid) {
        global $DB;

        // First try course completion criteria.
        $params = ['course' => $courseid];
        $totalcriteria = $DB->count_records('course_completion_criteria', $params);

        if ($totalcriteria > 0) {
            $sql = "
                select count(*)
                from {course_completion_crit_compl}
                where userid = :userid
                and course = :courseid
                and timecompleted > 0";

            $params = [
                'courseid' => $courseid,
                'userid' => $userid
            ];
            $completedcriteria = $DB->count_records_sql($sql, $params);
            return intval(($completedcriteria / $totalcriteria) * 100);
        }

        // Next try course module/activity completion.
        $sql = "
            select
                count(cmc.id) as completed_modules,
                count(cm.course) as total_modules
            from
                {course_modules} cm left outer join {course_modules_completion} cmc
                on cmc.coursemoduleid = cm.id
                and cmc.userid = :userid
            where
                cm.course = :courseid
                and cm.completion = 1
        ";

        $params = [
            'courseid' => $courseid,
            'userid' => $userid
        ];

        $activitycriteria = $DB->get_record_sql($sql, $params);

        if ($activitycriteria) {
            return intval((
                    $activitycriteria->completed_modules / $activitycriteria->total_modules) * 100
            );
        }

        return 0; // No progress found.
    }
}