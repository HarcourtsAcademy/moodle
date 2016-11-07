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

defined('MOODLE_INTERNAL') || die();

// Get the HTML for the settings bits.
$html = theme_academy_clean_get_html_for_settings($OUTPUT, $PAGE);

// Add course shortname name to body class
$courseclass = ' course-shortname-'.trim($COURSE->shortname);

// Add user's country to body class
$countryclass = ' user-profile-country-'.strtolower(trim($USER->country));

// Enable CSS to target pages presented to guest users.
$roleclass = '';
if (is_guest(context_course::instance($COURSE->id), $USER)) {
    $roleclass = 'guest';
}

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body <?php echo $OUTPUT->body_attributes($roleclass.$courseclass.$countryclass); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<header role="banner" class="navbar navbar-fixed-top<?php echo $html->navbarclass ?> moodle-has-zindex">
    <nav role="navigation" class="navbar-inner">
        <div class="container-fluid">
            <a class="logo" href="<?php echo $CFG->wwwroot;?>"></a>
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            <?php echo $OUTPUT->user_menu(); ?>
            <div class="nav-collapse collapse">
              <div class="custom_narrow_menu"><?php echo $OUTPUT->custom_menu(); ?></div>
                <ul class="nav">
                    <li><?php echo $OUTPUT->page_heading_menu(); ?></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<header class="clearfix custom_wide_menu nav-collapse collapse visible-desktop">
    <div class="navbar"><?php echo $OUTPUT->custom_menu(); ?></div>
</header>

<header class="clearfix page_heading">
    <div class="page-heading-inner">
        <nav class="breadcrumb-button"><?php echo $OUTPUT->page_heading_button(); ?></nav>
        <?php echo $html->heading; ?>
    </div>
</header>

<div id="page" class="container-fluid">

    <div id="page-content" class="row-fluid">
        <section id="region-main" class="span12">

            <div class="clearfix">
                <div id="page-navbar">
                    <?php echo $OUTPUT->navbar(); ?>
                </div>
                <div id="course-header">
                    <?php echo $OUTPUT->course_header(); ?>
                </div>
            </div>

            <?php
            $auth_instructions = '';

            if ( file_exists( $CFG->dirroot . '/auth/lenauth/out.php' ) ) :
                include_once $CFG->dirroot . '/auth/lenauth/out.php';
                $auth_instructions = auth_lenauth_out::getInstance()->lenauth_output('style3-text');
            endif;

            $CFG->auth_instructions = $CFG->auth_instructions . $auth_instructions;

            echo $OUTPUT->course_content_header();
            echo $OUTPUT->main_content();
            echo $OUTPUT->course_content_footer();
            ?>
        </section>
    </div>

</div>

<footer id="page-footer">
    <div id="course-footer"><?php echo $OUTPUT->course_footer(); ?></div>
    <p class="helplink"><?php echo $OUTPUT->page_doc_link(); ?></p>
    <?php
    echo $html->footnote;
    echo $html->piwik;
    echo $OUTPUT->login_info();
    echo $OUTPUT->standard_footer_html();
    ?>
</footer>

<?php echo $OUTPUT->standard_end_of_body_html() ?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
</body>
</html>
