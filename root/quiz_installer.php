<?php
// Ultimate Quiz MOD, SQL installer
// Only works on MySQL presently. Will move to UMIL in the near future.

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/quiz/quiz.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('mods/quiz');

if( !$auth->acl_get('a_') )
{
	trigger_error('UQM_INSTALLING_ADMIN_ONLY');
}

page_header($user->lang['UQM_INSTALLING']);

$sql = array();
$sql[QUIZ_TABLE] = "CREATE TABLE " . QUIZ_TABLE . " (
quiz_id INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
quiz_name VARCHAR(255) NOT NULL,
quiz_time INT(20) NOT NULL,
quiz_category INT(5) NOT NULL,
user_id INT(8) NOT NULL,
username VARCHAR(255) NOT NULL,
user_colour VARCHAR(10) NOT NULL
)";

$sql[QUIZ_QUESTIONS_TABLE] = "CREATE TABLE " . QUIZ_QUESTIONS_TABLE . " (
question_id INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
question_name VARCHAR(255) NOT NULL ,
question_correct VARCHAR(255) NOT NULL ,
question_answers VARCHAR(255) NOT NULL ,
question_quiz INT(5) NOT NULL
)";

$sql[QUIZ_STATISTICS_TABLE] = "CREATE TABLE " . QUIZ_STATISTICS_TABLE . " (
quiz_statistic_id INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
quiz_question_id INT(5) NOT NULL,
quiz_user_answer VARCHAR(255) NOT NULL ,
quiz_actual_answer VARCHAR(255) NOT NULL ,
quiz_is_correct INT(5) NOT NULL,
quiz_user INT(5) NOT NULL
)";

$sql[QUIZ_CATEGORIES_TABLE] = "CREATE TABLE " . QUIZ_CATEGORIES_TABLE . " (
quiz_category_id INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
quiz_category_name VARCHAR(255) NOT NULL
)";

$sql[CONFIG_TABLE] = "INSERT INTO " . CONFIG_TABLE . " (config_name, config_value, is_dynamic) VALUES
('qc_minimum_questions', '2', 1),
('qc_maximum_questions', '5', 1),
('qc_maximum_choices', '5', 1),
('qc_show_answers', '0', 1),
('qc_quiz_author_edit', '0', 1),
('qc_admin_submit_only', '0', 1),
('qc_cash_enabled', '0', 1),
('qc_cash_column', 'user_points', 1),
('qc_cash_correct', '50', 1),
('qc_cash_incorrect', '150', 1)";


$errors = false;
$value = array();

foreach($sql as $name => $query)
{
	if( !$db->sql_query($query) )
	{
		$errors = true;
		$value[] = sprintf($user->lang['UQM_ERROR_INSTALLING_TABLE'], $name);
	}

	else
	{
		$value[] = sprintf($user->lang['UQM_INSTALLING_TABLE'], $name);
	}
}


$value[] = ($errors) ? $user->lang['UQM_INSTALLING_FINISHED_ERRORS'] : $user->lang['UQM_INSTALLING_FINISHED'];
$value[] = $user->lang['UQM_INSTALLING_SUPPORT'];

foreach($value as $entry)
{
	$template->assign_block_vars('progress', array(
		'U_PROGRESS'	=> $entry,
	));
}

$template->set_filenames(array(
	'body' => 'quiz_installer_body.html')
);

page_footer();
