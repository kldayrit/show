<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Block show is defined here.
 *
 * @package     block_tulad
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_tulad extends block_base {

    /**
     * Initializes class member variables.
     */
    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_tulad');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {

        global $DB, $USER, $CFG;

        $this->page->requires->jquery();

        $this->page->requires->js_call_amd('block_tulad/main', 'initialize');

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if (!empty($this->config->text)) {
            $this->content->text = $this->config->text;
        } else {
            // FORUM.
            $text = '<h6>' . get_string('title', 'block_tulad') . '</h6>';

            // Course Filter.
            $coursesql = "SELECT * FROM mdl_course";
            $course = $DB->get_records_sql($coursesql);

            $coursefilter = '<form method="post">
            <span>Course: </span>
            <select name="filterByCourse">';
            $coursefilter .= "<option value='-1'> ... </option>";
            foreach ($course as $c) {
                $coursefilter .= "<option value='$c->id'> $c->id $c->fullname </option>";
            }

            $coursefilter .= '</select>
            <input type="submit" name="coursefilter">
            </form>';
            $text .= $coursefilter;

            // Forum Filter.
            $forumsql = "SELECT * FROM mdl_forum";
            $forum = $DB->get_records_sql($forumsql);
            if (isset($_POST['coursefilter']) && $_POST['filterByCourse'] != '-1') {
                $id = $_POST['filterByCourse'];
                $forumsql = "SELECT * FROM mdl_forum where course=$id";
                $forum = $DB->get_records_sql($forumsql);
            }

            $forumfilter = '<form method="post">
            <span>Forum: </span>
            <select name="filterByForum">';
            $forumfilter .= "<option value='-1'> ... </option>";
            foreach ($forum as $f) {
                $forumfilter .= "<option value='$f->id'> $f->id $f->name </option>";
            }

            $forumfilter .= '</select>
            <input type="submit" name="forumfilter">
            </form>';
            $text .= $forumfilter;

            // Show table of forum messages.
            $table = "<table> <tbody>";
            $table .= "<tr class=table_header>
            <th>Course </th>
            <th>Forum </th>
            <th>Discussion </th>
            <th>Poster </th>";
            $table .= "<th>Subject </th>";
            $table .= "<th>Message </th>";
            $table .= "<th>Similarity (?)</th>";
            $table .= "<th>Semantic (?)</th>";
            $table .= "<th></th>";
            $table .= "</tr>";

            $arr = array();
            $index = 0;
            if (isset($_POST['forumfilter']) && $_POST['filterByForum'] != '-1') {
                $id = $_POST['filterByForum'];
                $forum = $forum[$id];
                $discussionsql = "SELECT * FROM mdl_forum_discussions WHERE forum=$forum->id";
                $discussion = $DB->get_records_sql($discussionsql);
                foreach ($discussion as $d) {
                    $postsql = "SELECT * FROM mdl_forum_posts WHERE discussion=$d->id";
                    $post = $DB->get_records_sql($postsql);
                    foreach ($post as $p) {
                        array_push($arr, $p->message);
                    }
                    $arr = array_map( 'strip_tags', $arr );
                    $a = implode("||", $arr);
                }
                foreach ($discussion as $d) {
                    $postsql = "SELECT * FROM mdl_forum_posts WHERE discussion=$d->id";
                    $post = $DB->get_records_sql($postsql);
                    foreach ($post as $p) {
                        $usersql = "SELECT * FROM mdl_user WHERE id=$p->userid ";
                        $user = $DB->get_record_sql($usersql);
                        $coursesql = "SELECT * FROM mdl_course where id=$d->course";
                        $course = $DB->get_record_sql($coursesql);

                        $table .= "<tr id=$index>
                        <td> $course->fullname</td>
                        <td> $forum->name</td>
                        <td> $d->name</td>
                        <td> $user->firstname $user->lastname</td>";
                        $table .= "<td> $p->subject</td>";
                        $table .= "<td> $p->message  </td>";
                        $table .= "<td class=$index> -- </td>";
                        $table .= "<td class='sem$index'> -- </td>";
                        $s = strip_tags($p->message);
                        array_unshift($arr, $index);
                        $a = implode("||", $arr);
                        $table .= "<td><button class='check' value='$a' >CHECK</button></td>";
                        $table .= "</tr>";
                        array_shift($arr);

                        $index++;
                    }
                }
            } else {
                foreach ($forum as $f) {
                    $discussionsql = "SELECT * FROM mdl_forum_discussions WHERE forum=$f->id";
                    $discussion = $DB->get_records_sql($discussionsql);
                    foreach ($discussion as $d) {
                        $postsql = "SELECT * FROM mdl_forum_posts WHERE discussion=$d->id";
                        $post = $DB->get_records_sql($postsql);
                        foreach ($post as $p) {
                            array_push($arr, $p->message);
                        }
                        $arr = array_map( 'strip_tags', $arr );
                        $a = implode("||", $arr);
                    }
                }
                foreach ($forum as $f) {
                    $discussionsql = "SELECT * FROM mdl_forum_discussions WHERE forum=$f->id";
                    $discussion = $DB->get_records_sql($discussionsql);
                    foreach ($discussion as $d) {
                        $postsql = "SELECT * FROM mdl_forum_posts WHERE discussion=$d->id";
                        $post = $DB->get_records_sql($postsql);
                        foreach ($post as $p) {
                            $usersql = "SELECT * FROM mdl_user WHERE id=$p->userid ";
                            $user = $DB->get_record_sql($usersql);
                            $coursesql = "SELECT * FROM mdl_course where id=$d->course";
                            $course = $DB->get_record_sql($coursesql);

                            $table .= "<tr id=$index>
                            <td> $course->fullname</td>
                            <td> $f->name</td>
                            <td> $d->name</td>
                            <td> $user->firstname $user->lastname</td>";
                            $table .= "<td> $p->subject</td>";
                            $table .= "<td> $p->message  </td>";
                            $table .= "<td class=$index> -- </td>";
                            $table .= "<td class='sem$index'> -- </td>";
                            $s = strip_tags($p->message);
                            array_unshift($arr, $index);
                            $a = implode("||", $arr);
                            $table .= "<td><button class='check' value='$a' >CHECK</button></td>";
                            $table .= "</tr>";
                            array_shift($arr);
                            $index++;
                        }
                    }
                }
            }
            $table .= "</tbody> </table>";

            $text .= $table;

            $assignsql = "SELECT * FROM mdl_assign";
            if (isset($_POST['coursefilter']) && $_POST['filterByCourse'] != '-1') {
                $id = $_POST['filterByCourse'];
                $assignsql = "SELECT * FROM mdl_assign where course=$id";
            }
            $assign = $DB->get_records_sql($assignsql);

            $assignfilter = '<br><br>
                            <h6> ASSIGNMENTS </h6>
                            <form method="post">
                                <span>Assignment: </span>
                                <select name="filterByAssign">
                                    <option value="-1"> ... </option>';
            foreach ($assign as $a) {
                $assignfilter .= "  <option value='$a->id'> $a->id $a->name </option>";
            }
            $assignfilter .= '  </select>
                                <input type="submit" name="assignfilter">
                            </form>';
            $text .= $assignfilter;

            $assigntable = '<table>
                                <thead>
                                    <tr class="table_header">
                                        <th>Course</th>
                                        <th>Assignment</th>
                                        <th>Author</th>
                                        <th>Online Text</th>
                                        <th>Similarity</th>
                                        <th>Semantic</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>';

            $assignarr = array();
            $assignindex = 0;
            if (isset($_POST['assignfilter']) && $_POST['filterByAssign'] != '-1') {
                $assignid = $_POST['filterByAssign'];
                $assignselect = $assign[$assignid];
                $assigntextsubsql = "SELECT * FROM mdl_assignsubmission_onlinetext o
                                     JOIN mdl_assign_submission a ON a.id = o.submission
                                     WHERE o.assignment=$assignselect->id";
                $submissions = $DB->get_records_sql($assigntextsubsql);
                foreach ($submissions as $s) {
                    array_push($assignarr, $s->onlinetext);
                }
                $assignarr = array_map('strip_tags', $assignarr);
                $assignlist = implode("||", $assignarr);

                foreach ($submissions as $s) {
                    $usersql = "SELECT * FROM mdl_user WHERE id=$p->userid ";
                    $user = $DB->get_record_sql($usersql);
                    $coursesql = "SELECT * FROM mdl_course where id=$assignselect->course";
                    $course = $DB->get_record_sql($coursesql);

                    $assigntable .= "<tr id = 'a$assignindex'>
                                        <td> $course->fullname </td>
                                        <td> $assignselect->name </td>
                                        <td> $user->firstname $user->lastname </td>
                                        <td> $s->onlinetext </td>
                                        <td class='a$assignindex'> -- </td>
                                        <td class='asem$assignindex'> -- </td>";
                    $stripped = strip_tags($s->onlinetext);
                    array_unshift($assignarr, $assignindex);
                    $assigntext = implode("||", $assignarr);
                    $assigntable .= "   <td> <button class='checka' value='$assigntext'> CHECK </button> </td>
                                    </tr>";
                    array_shift($assignarr);

                    $assignindex++;
                }
            } else {
                foreach ($assign as $a) {
                    $assigntextsubsql = "SELECT * FROM mdl_assignsubmission_onlinetext o
                                         JOIN mdl_assign_submission a ON a.id = o.submission
                                         WHERE o.assignment=$a->id";
                    $submissions = $DB->get_records_sql($assigntextsubsql);
                    foreach ($submissions as $s) {
                        array_push($assignarr, $s->onlinetext);
                    }
                    $assignarr = array_map("strip_tags", $assignarr);
                    $assignlist = implode("||", $assignarr);
                }

                foreach ($assign as $a) {
                    $assigntextsubsql = "SELECT * FROM mdl_assignsubmission_onlinetext o
                                         JOIN mdl_assign_submission a ON a.id = o.submission
                                         WHERE o.assignment=$a->id";
                    $submissions = $DB->get_records_sql($assigntextsubsql);
                    foreach ($submissions as $s) {
                        $usersql = "SELECT * FROM mdl_user WHERE id=$p->userid ";
                        $user = $DB->get_record_sql($usersql);
                        $coursesql = "SELECT * FROM mdl_course where id=$a->course";
                        $course = $DB->get_record_sql($coursesql);

                        $assigntable .= "<tr id = 'a$assignindex'>
                                            <td> $course->fullname </td>
                                            <td> $a->name </td>
                                            <td> $user->firstname $user->lastname </td>
                                            <td> $s->onlinetext </td>
                                            <td class='a$assignindex'> -- </td>
                                            <td class='asem$assignindex'> -- </td>";
                        $stripped = strip_tags($s->onlinetext);
                        array_unshift($assignarr, $assignindex);
                        $assigntext = implode("||", $assignarr);
                        $assigntable .= "   <td> <button class='checka' value='$assigntext'> CHECK </button> </td>
                                        </tr>";
                        array_shift($assignarr);

                        $assignindex++;
                    }
                }
            }
            $assigntable .= "   </tbody>
                            </table>";
            $text .= $assigntable;

            $this->content->text = $text;
        }

        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediately after init().
     */
    public function specialization() {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_tulad');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Allow multiple instances in a single course?
     *
     * @return bool True if multiple instances are allowed, false otherwise.
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    public function has_config() {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats() {
        return array(
            'all' => true,
        );
    }
    public function self_test() {
        return true;
    }
}
