<?php
// quiz.php
// Ultimate Quiz MOD includes file

if( !defined('IN_PHPBB') )
{
	exit;
}

// Include the classes
include($phpbb_root_path . 'includes/quiz/quiz_configuration.' . $phpEx);
include($phpbb_root_path . 'includes/quiz/quiz_question.' . $phpEx);
include($phpbb_root_path . 'includes/quiz/quiz_statistics.' . $phpEx);

// Define the table constants
define('QUIZ_TABLE',		$table_prefix . 'quiz');
define('QUIZ_QUESTIONS_TABLE',	$table_prefix . 'quiz_questions');
define('QUIZ_STATISTICS_TABLE',	$table_prefix . 'quiz_statistics');
define('QUIZ_CATEGORIES_TABLE', $table_prefix . 'quiz_categories');
?>
