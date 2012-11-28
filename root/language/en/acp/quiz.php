<?php
// Ultimate Quiz MOD ACP Quiz

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// Common
$lang = array_merge($lang, array(
	// Quiz Admin
	'ACP_UQM_QUIZ'					=> 'Quiz',
	'ACP_UQM_QUIZ_CATEGORIES'		=> 'Categories',
	'ACP_UQM_QUIZ_CONFIGURATION'	=> 'Configuration',
	'ACP_UQM_QUIZ_FORM_INVALID'		=> 'This quiz form is invalid. Please resubmit it.',

	'ACP_UQM_EDIT_CATEGORY'			=> 'Edit category',
	'ACP_UQM_DELETE_CATEGORY'		=> 'Delete category',
	'ACP_UQM_ADD_CATEGORY'			=> '%sAdd new category%s',

	'ACP_UQM_CATEGORY_NAME'									=> 'Enter the category name',
	'ACP_UQM_CATEGORY_NAME_EXPLAIN'							=> 'You must enter a name for this category.',
	'ACP_UQM_CATEGORY_DESCRIPTION'							=> 'Enter the category description',
	'ACP_UQM_CATEGORY_DESCRIPTION_EXPLAIN'					=> 'Leave blank for no description.',
	'ACP_UQM_CATEGORY_NAME_VALIDATE'						=> 'Quiz category names cannot be empty or match an existing quiz category name.',
	'ACP_UQM_CATEGORY_GROUP_REWARDS'						=> 'Group rewards',
	'ACP_UQM_CATEGORY_GROUP_REWARDS_EXPLAIN'				=> 'If a user completes each of the quizzes (at a given point in time) to at least the percentage specified, they can be moved to a specified usergroup. Do you want to enable this feature for this category?',
	'ACP_UQM_CATEGORY_GROUP_REWARDS_GROUP' 					=> 'Destination usergroup',
	'ACP_UQM_CATEGORY_GROUP_REWARDS_GROUP_SELECT'			=> 'Select a usergroup...',
	'ACP_UQM_CATEGORY_GROUP_REWARDS_GROUP_EXPLAIN' 			=> 'This is the usergroup that successful users will be moved to.',
	'ACP_UQM_CATEGORY_GROUP_REWARDS_GROUP_VALIDATE' 		=> 'The usergroup must already exist and must not be a default usergroup.',
	'ACP_UQM_CATEGORY_GROUP_REWARDS_PERCENTAGE'				=> 'Minimum percentage',
	'ACP_UQM_CATEGORY_GROUP_REWARDS_PERCENTAGE_EXPLAIN'		=> 'The user must achieve this percentage as a minimum in each of the quizzes in the category.',
	'ACP_UQM_CATEGORY_GROUP_REWARDS_PERCENTAGE_VALIDATE'	=> 'The minimum percentage must be a whole number between 0 and 100.',
	'ACP_UQM_CATEGORY_ADDED'		=> 'The category <strong>%s</strong> has been successfully added.',
	'ACP_UQM_CATEGORY_UPDATED'		=> 'The category <strong>%s</strong> has been successfully updated.',
	'ACP_UQM_CATEGORY_GROUP_PERMISSIONS_GROUP'				=> 'Permitted groups',
	'ACP_UQM_CATEGORY_GROUP_PERMISSIONS_GROUP_EXPLAIN'		=> 'Select the groups that should be able to play quizzes within this category.',

	'ACP_UQM_DELETE_TITLE'			=> 'Delete category',
	'ACP_UQM_DELETE_TITLE_CONFIRM'	=> 'Are you sure you wish to delete this category and all of the quizzes inside it?',

	'ACP_UQM_DELETE_SUCCESSFUL'		=> 'The quiz category (and all quizzes inside it) has been deleted.',

	'ACP_UQM_CONFIG_UPDATED'		=> 'The quiz configuration settings have been successfully updated.',

	'ACP_UQM_CONFIG_DEFINITIONS'	=> array(
		'qc_minimum_questions'			=> 'Minimum number of questions',
		'qc_minimum_questions_explain'	=> 'What is the minimum number of questions permitted per quiz?',
		'qc_maximum_questions'			=> 'Maximum number of questions',
		'qc_maximum_questions_explain'	=> 'What is the maximum number of questions permitted per quiz?',
		'qc_maximum_choices'			=> 'Maximum multiple choices',
		'qc_maximum_choices_explain'	=> 'What is the maximum number of multiple choices permitted for a question?',
		'qc_show_answers'				=> 'Show quiz answers',
		'qc_show_answers_explain'		=> 'Should answers be shown to the user once they have completed the quiz?',
		'qc_quiz_author_edit'			=> 'Edit permissions',
		'qc_quiz_author_edit_explain'	=> 'Can quiz authors edit and delete their own quizzes as well as any associated data such as statistics for that quiz?',
		'qc_admin_submit_only'			=> 'Submit permissions',
		'qc_admin_submit_only_explain'	=> 'Should administrators be the only users permitted to submit quizzes?',
		'qc_enable_time_limits'			=> 'Enable time limits',	
		'qc_enable_time_limits_explain'	=> 'If time limits are enabled then quiz submitters can specify the maximum amount of time allowed for users to complete the quiz.',
		'qc_exclusion_time'				=> 'Exclusion time',
		'qc_exclusion_time_explain'		=> 'If a user does not finish a quiz or violates the time limit, how many seconds do they need to wait until they can play the quiz again?',
		'qc_cash_enabled'				=> 'Enable cash/points integration',
		'qc_quizzes_on_index'			=> 'Quiz index page',
		'qc_quizzes_on_index_explain'	=> 'Specify the number of quizzes from each category you would like displayed on the quiz index.',
		'qc_quizzes_per_page'			=> 'Quizzes per page',
		'qc_quizzes_per_page_explain'	=> 'Specify the number of quizzes you would like to appear per page in each category.',		
		'qc_cash_enabled_explain'		=> 'If you have installed a cash/points modification and wish to integrate it with the Ultimate Quiz MOD set this option to on.',
		'qc_cash_column'				=> 'Cash/points column',
		'qc_cash_column_explain'		=> 'List the column in the users table associated with cash or points.',
		'qc_cash_correct'				=> 'Cash/points for correct answers',
		'qc_cash_correct_explain'		=> 'How many cash/points should be awarded for correct answers?',
		'qc_cash_incorrect'				=> 'Cash/points lost for incorrect answers',
		'qc_cash_incorrect_explain'		=> 'How many cash/points should be deducted for wrong answers?')
	));
