<?php
// Ultimate Quiz MOD language file

if( !defined('IN_PHPBB') )
{
	exit;
}

if( empty($lang) || !is_array($lang) )
{
	$lang = array();
}

$lang = array_merge($lang, array(
	// Admin
	'ACP_UQM_QUIZ'				=> 'Quiz',
	'ACP_UQM_QUIZ_CATEGORIES'		=> 'Categories',
	'ACP_UQM_QUIZ_CONFIGURATION'		=> 'Configuration',

	// Quiz
	'UQM_QUIZ'				=> 'Quiz',
	'UQM_QUIZ_EXPLAIN'			=> 'Ultimate Quiz MOD',
	'UQM_QUIZ_CONFIG_ERROR'			=> 'A configuration error has been detected.',
	'UQM_QUIZ_FORM_INVALID'			=> 'This quiz form is invalid. Please try again.',
	'UQM_QUIZ_FOR_REGISTERED_USERS'		=> 'Only registered users may view quizzes!',

	'UQM_SUBMIT_QUIZ'			=> 'Submit quiz',
	'UQM_STATS_QUIZ'			=> 'Statistics',
	'UQM_RECENTLY_ADDED_QUIZZES'	=> 'Recently added quizzes',
	'UQM_CATEGORY_NO_QUIZZES'		=> 'There are no quizzes in this category.',
	'UQM_CATEGORY_NO_QUIZZES_FOUND'	=> 'No quizzes found on this page number.',
	'UQM_CATEGORY_VIEW_ALL'			=> '%sClick here%s to view all quizzes from this category.',
	'UQM_CATEGORY_NO_PERMISSION'	=> 'You do not have the required permissions to view this category.',
	'UQM_CATEGORY_QUIZ_NO_PERMISSION'	=> 'You do not have the required permissions to view quizzes from this category.',
	'UQM_CATEGORIES_NOT_AVAILABLE'	=> 'There are no quiz categories to display.',

	'UQM_SUBMIT_NO_PERMISSIONS'		=> 'Only administrators are permitted to submit quizzes.',
	'UQM_ENTER_QUESTION'			=> 'Enter the question',
	'UQM_ENTER_QUESTION_EXPLAIN'		=> 'bbCode is not permitted.',
	'UQM_ENTER_ANSWERS'			=> 'Enter the answers',
	'UQM_ENTER_ANSWERS_EXPLAIN'		=> 'Separate each answer by a new line.',
	'UQM_PLUS_QUESTION'			=> 'Add question',
	'UQM_MINUS_QUESTION'			=> 'Remove question',
	'UQM_QUESTION_BOUNDARY_VIOLATE'		=> 'Please ensure the number of questions stays between %d and %d as set by the administrator.',	
	'UQM_CONFIRM_CORRECT_ANSWER'		=> 'In the answers below, please select the correct answer.',
	'UQM_ENSURE_FIELDS_ARE_FILLED'		=> 'Please ensure no fields are left empty!',
	'UQM_SELECT_ANSWERS'			=> 'Select the correct answer',
	'UQM_SELECT_ANSWERS_EXPLAIN'		=> 'In the answers below, please select the correct answer.',
	'UQM_ENTER_ALL_CORRECT'			=> 'Please select a correct answer for each question and a name for this quiz.',
	'UQM_ENTER_VALID_CATEGORY'		=> 'Please select another category, as you do not have access to the category currently selected.',
	'UQM_QUIZ_SUBMITTED'			=> 'The quiz has now been submitted into the database.<br />Click %shere%s to return to the quiz index page.',
	'UQM_ENTER_QUIZ_NAME'			=> 'Please enter the quiz name',
	'UQM_ENTER_QUIZ_CATEGORY'		=> 'Please select a quiz category',

	'UQM_QUIZ_NAME'				=> 'Quiz name',
	'UQM_QUIZ_AUTHOR'			=> 'Quiz author',
	'UQM_QUIZ_SUBMITTED_BY'			=> 'Submitted by %s',
	'UQM_QUIZ_DATE'				=> 'Quiz date',
	'UQM_QUIZ_INFO'				=> 'Quiz tasks',
	'UQM_QUIZZES_NO_ENTRIES'		=> 'No quizzes have been submitted yet. You can %ssubmit the first%s right now!',

	'UQM_QUIZ_PLAY'				=> 'Play quiz',
	'UQM_QUIZ_PLAY_NO_ID'			=> 'No quiz has been selected. Please select a quiz.',
	'UQM_QUIZ_AUTHOR_DETAILS'		=> 'Submitted by %s on %s',
	'UQM_QUIZ_CORRECT'			=> 'Correct',
	'UQM_QUIZ_INCORRECT'			=> 'Incorrect',
	'UQM_QUIZ_USER_SELECTED'		=> 'You selected <strong>%s</strong>, the correct answer was <strong>%s</strong>.',
	'UQM_QUIZ_CASH_GAIN'			=> 'You have gained <strong>%s</strong> points from playing this quiz.',
	'UQM_QUIZ_CASH_LOST'			=> 'You have lost <strong>%s</strong> points from playing this quiz.',
	'UQM_RESULTS_FOR_QUIZ'			=> 'Results for %s',
	'UQM_RESULTS_SUMMARY'			=> 'You correctly answered <strong>%d</strong> and incorrectly answered <strong>%d</strong> questions, a result of <strong>%d&#37;</strong>.',
	'UQM_RESULTS_GROUP_REWARD'		=> 'For achieving at least <strong>%d&#37;</strong> in each of the quizzes in this category, you have been moved to the <strong>%s</strong> usergroup.',
	'UQM_RESULTS_RETURN_TO_INDEX'		=> 'Click %shere%s to return to the quiz index.',

	'UQM_QUIZ_STATISTICS'			=> 'Quiz statistics',

	'UQM_QUIZ_STATISTICS_QUESTION'		=> 'Question',
	'UQM_QUIZ_STATISTICS_QUESTIONS'		=> 'Questions',
	'UQM_QUIZ_STATISTICS_CORRECT'		=> 'Times answered correctly',
	'UQM_QUIZ_STATISTICS_INCORRECT'		=> 'Times answered incorrectly',
	'UQM_QUIZ_STATISTICS_PERCENT'		=> 'Correct percentage',
	'UQM_QUIZ_STATISTICS_PERCENT_WRONG'	=> 'Incorrect percentage',
	'UQM_QUIZ_STATISTICS_PLAYS'		=> 'Times played',
	'UQM_QUIZ_STATISTICS_AVERAGE_SCORE'	=> 'Average score',
	'UQM_QUIZ_STATISTICS_CANNOT_VIEW'	=> 'Only administrators, the quiz author and users who have played the quiz may view the quiz statistics.',
	'UQM_STATISTICS_NO_ENTRIES'		=> 'There are no entries',
	'UQM_QUIZ_STATISTICS_ANSWER'		=> 'User answer',
	'UQM_QUIZ_STATISTICS_UNANSWERED'	=> 'Unanswered',

	'UQM_EDIT_NOT_ALLOWED'			=> 'You do not have the required permissions to edit this quiz.',
	'UQM_EDIT_QUIZ'				=> 'Edit quiz',
	'UQM_DELETE_QUIZ'			=> 'Delete quiz?',
	'UQM_DELETE_QUIZ_EXPLAIN'		=> 'Tick the following box only if wish to delete this quiz and remove any associated database entries such as statistics for this quiz.',
	'UQM_EDIT_VERIFY_ANSWERS'		=> 'An error occured, please ensure you have selected a valid answer(s) for each question and that each question is valid.',
	'UQM_EDIT_QUIZ_SUBMITTED'		=> 'The changes to the quiz have now been submitted into the database.<br />Click %shere%s to return to the quiz index page.',
	'UQM_DELETE_QUIZ_SUBMITTED'		=> 'The quiz has now been completely removed from the database.<br />Click %shere%s to return to the quiz index page.',
	'UQM_EDIT_NO_QUIZ'			=> 'This quiz could not be found.',

	'UQM_INDEX_STATS'			=> '%sStatistics%s',
	'UQM_INDEX_EDIT'			=> '%sEdit or Delete%s',

	'UQM_INSTALLING'			=> 'Ultimate Quiz MOD installing...',
	'UQM_INSTALLING_TABLE'			=> 'Creating table <strong>%s</strong>...',
	'UQM_ERROR_INSTALLING_TABLE'		=> 'There was an error creating table <strong>%s</strong>...',
	'UQM_INSTALLING_FINISHED_ERRORS'	=> 'Installation is finished with errors.',
	'UQM_INSTALLING_FINISHED'		=> 'Installation is finished with no errors.',
	'UQM_INSTALLING_SUPPORT'		=> 'For support, please visit <a href="http://forums.cricketmx.com/viewforum.php?f=63">CricketMX.com</a> or <a href="http://www.phpbb.com">phpBB MOD support topic</a>.',
	'UQM_INSTALLING_ADMIN_ONLY'		=> 'Only administrators can run this installer.',

	'UQM_TIME_LIMIT_VIOLATED'	=> 'You cannot play this quiz for another <strong>%d</strong> minutes as you have violated the time limit on a previous attempt.',
	'UQM_TIME_LIMIT_EXCEEDED'	=> 'You have exceeded the time limit allowed for this quiz.',
	'UQM_TIME_LIMIT_EXCEEDED_REDIRECT'	=> 'You have exceeded the time limit allowed for this quiz. Click OK to be redirected back to the quiz index page.',
	'UQM_END_SESSION_ERROR'		=> 'Unable to end the session, as no session for this quiz could be found.',
	'UQM_ENTER_TIME_LIMIT'		=> 'Enter the time limit for this quiz, or set to 0 for no time limit.',
	'UQM_TIME_LIMIT_MINUTES'	=> 'minutes',
	'UQM_TIME_LIMIT_SECONDS'	=> 'seconds',
	'UQM_BUTTON_QUIZ_NEW'		=> 'Create a new quiz',
));

?>
