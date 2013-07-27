<?php
/**
 *
 * @author battye (battye)
 * @version $Id$
 * @copyright (c) 2013 battye
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * @ignore
 */
define('UMIL_AUTO', true);
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/quiz/quiz.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup();


if (!file_exists($phpbb_root_path . 'umil/umil_auto.' . $phpEx))
{
	trigger_error('Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>', E_USER_ERROR);
}

// The name of the mod to be displayed during installation.
$mod_name = 'Ultimate Quiz MOD';

/*
* The name of the config variable which will hold the currently installed version
* UMIL will handle checking, setting, and updating the version itself.
*/
$version_config_name = 'uqm_version';


// The language file which will be included when installing
$language_file = 'mods/quiz';


/*
* Optionally we may specify our own logo image to show in the upper corner instead of the default logo.
* $phpbb_root_path will get prepended to the path specified
* Image height should be 50px to prevent cut-off or stretching.
*/
//$logo_img = 'styles/prosilver/imageset/site_logo.gif';

/*
* The array of versions and actions within each.
* You do not need to order it a specific way (it will be sorted automatically), however, you must enter every version, even if no actions are done for it.
*
* You must use correct version numbering.  Unless you know exactly what you can use, only use X.X.X (replacing X with an integer).
* The version numbering must otherwise be compatible with the version_compare function - http://php.net/manual/en/function.version-compare.php
*/
$versions = array(
	'2.1.2' => array(

		'table_add' => array(
			array(QUIZ_TABLE, array(
				'COLUMNS' => array(
					'quiz_id' => array('INT:5', NULL, 'auto_increment'),
					'quiz_name' => array('VCHAR:255', ''),
					'quiz_time' => array('INT:20', 0),
					'quiz_category' => array('INT:5', 0),
					'user_id' => array('INT:8', 0),
					'username' => array('VCHAR:255', ''),
					'user_colour' => array('VCHAR:10', ''),
					'quiz_time_limit' => array('INT:5', 0),
				),

				'PRIMARY_KEY'	=> 'quiz_id',
			)),

			array(QUIZ_CATEGORIES_TABLE, array(
				'COLUMNS' => array(
					'quiz_category_id' => array('INT:5', NULL, 'auto_increment'),
					'quiz_category_name' => array('VCHAR:255', ''),
					'quiz_category_destination_group_id' => array('INT:8', 0),
					'quiz_category_destination_group_percentage' => array('INT:3', 0),
					'quiz_category_description' => array('VCHAR:255', ''),
					'quiz_category_group_ids' => array('VCHAR:255', '2'),
				),

				'PRIMARY_KEY'	=> 'quiz_category_id',
			)),

			array(QUIZ_QUESTIONS_TABLE, array(
				'COLUMNS' => array(
					'question_id' => array('INT:5', NULL, 'auto_increment'),
					'question_name' => array('VCHAR:255', ''),
					'question_correct' => array('VCHAR:255', ''),
					'question_answers' => array('VCHAR:255', ''),
					'question_quiz' => array('INT:5', 0),
					'question_bbcode_bitfield' => array('VCHAR:255', ''),
					'question_bbcode_uid' => array('VCHAR:8', ''),
					'question_bbcode_options' => array('INT:11', 0),
				),

				'PRIMARY_KEY'	=> 'question_id',
			)),

			array(QUIZ_SESSIONS_TABLE, array(
				'COLUMNS' => array(
					'quiz_session_id' => array('INT:8', NULL, 'auto_increment'),
					'quiz_id' => array('INT:5', 0),
					'user_id' => array('INT:8', 0),
					'started' => array('INT:11', 0),
					'ended' => array('INT:11', 0),
				),

				'PRIMARY_KEY'	=> 'quiz_session_id',
			)),

			array(QUIZ_STATISTICS_TABLE, array(
				'COLUMNS' => array(
					'quiz_statistic_id' => array('INT:5', NULL, 'auto_increment'),
					'quiz_question_id' => array('INT:5', 0),
					'quiz_user_answer' => array('VCHAR:255', ''),
					'quiz_actual_answer' => array('VCHAR:255', ''),
					'quiz_is_correct' => array('INT:5', 0),
					'quiz_user' => array('INT:5', 0),
					'quiz_session_id' => array('INT:5', 0),
				),

				'PRIMARY_KEY'	=> 'quiz_statistic_id',
			)),

		),

		'config_add' => array(
			array('qc_cash_column', 'user_points', 1),
			array('qc_cash_enabled', '0', 1),
			array('qc_admin_submit_only', '0', 1),
			array('qc_quiz_author_edit', '0', 1),
			array('qc_show_answers', '1', 1),
			array('qc_maximum_choices', '5', 1),
			array('qc_maximum_questions', '5', 1),
			array('qc_minimum_questions', '2', 1),
			array('qc_cash_correct', '50', 1),
			array('qc_cash_incorrect', '150', 1),
			array('qc_exclusion_time', '600', 1),
			array('qc_enable_time_limits', '1', 1),
			array('qc_quizzes_on_index', '2', 1),
			array('qc_quizzes_per_page', '3', 1),
		),

		'module_add' => array(
	            array('acp', 'ACP_CAT_DOT_MODS', 'ACP_UQM_QUIZ'),
	            array('acp', 'ACP_UQM_QUIZ', array(
	                    'module_basename'	=> 'quiz',
	                ),    
	            ),
		),
	),
);

// Include the UMIL Auto file, it handles the rest
include($phpbb_root_path . 'umil/umil_auto.' . $phpEx);
