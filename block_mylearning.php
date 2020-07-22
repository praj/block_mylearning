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
 * Block class to display my learning status to students.
 *
 * @package    block_mylearning
 * @copyright  Praj Basnet <praj.basnet@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_mylearning extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_mylearning');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        require_once('classes/output/main.php');
        require_once('classes/output/renderer.php');
        $renderable = new block_mylearning\output\main();
        $renderer = $this->page->get_renderer('block_mylearning');

        $this->content = (object) [
            'text' => $renderer->render($renderable),
            'footer' => ''
        ];

        return $this->content;
    }

    public function has_config() {
        return false;
    }

    public function instance_allow_multiple() {
        // Allow more than one instance on a page.
        return false;
    }

    public function applicable_formats() {
        // Only on the dashboard page.
        return array('my' => true);
    }
}