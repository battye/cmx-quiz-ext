<?php
/**
*
* @package phpBB3
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('mods/quiz');

// Only allow registered users to see quizzes
if( !$user->data['is_registered'] )
{
	trigger_error('UQM_QUIZ_FOR_REGISTERED_USERS');
}

include($phpbb_root_path . 'includes/quiz/quiz.' . $phpEx);

$mode 		= request_var('mode', '');
$quiz_id 	= request_var('q', 0);

$quiz_configuration = new quiz_configuration;
$quiz_configuration->load();

// Put in "quiz" breadcrumb
$quiz_configuration->breadcrumbs( array($user->lang['UQM_QUIZ'] => append_sid('quiz.'. $phpEx)) );

switch($mode)
{
	case 'submit':
		// Create the breadcrumbs
		$quiz_configuration->breadcrumbs( array($user->lang['UQM_SUBMIT_QUIZ'] => append_sid('quiz.'. $phpEx, 'mode=submit')) );

		$quiz_name = request_var('quiz_name', '');
		$time_limit_minutes = request_var('time_limit_minutes', -1);
		$time_limit_seconds = request_var('time_limit_seconds', -1);

		// Set the page header
		page_header($user->lang['UQM_SUBMIT_QUIZ']);

		$auth_params = array(
			'administrator'		=> $auth->acl_get('a_'),
			'submit_setting'	=> $quiz_configuration->value('qc_admin_submit_only'),
			'return_value'		=> false,
		);

		$quiz_configuration->auth('submit', $auth_params);

		$enter_answers  = (!empty($_POST['submit'])) ? true : false;
		$alter_question = (!empty($_POST['alter_question'])) ? true : false;
		$submit_db	= (!empty($_POST['submit_db'])) ? true : false;

		$quiz_message	   = ''; // a message in case something unexpected happens and goes wrong
		$current_questions = request_var('question_number', $quiz_configuration->value('qc_minimum_questions'));

		// Create a variable seeing we are checking a few times...
		$time_limits_enabled = ($quiz_configuration->value('qc_enable_time_limits')) ? true : false;

		// Enter the questions and answers into the database
		if ($submit_db)
		{
			if (!check_form_key('uqm_submit'))
			{
				trigger_error('UQM_QUIZ_FORM_INVALID');
			}

			// See if the user has left any empty
			$check_correct	= $quiz_configuration->check_correct_checked($current_questions); 		
			$quiz_category	= request_var('category', 1); // default to the first category

			// Make sure this is definitely a category the user can access. 
			// If it's not, a message will be returned to the user
			$category_is_valid = in_array($quiz_category, $quiz_configuration->qc_user_viewable_categories);

			// Time limit
			$quiz_time_limit = null;

			if ($time_limits_enabled)
			{
				// Calculate the time limit in seconds
				$quiz_time_limit = (request_var('time_limit_minutes', 0) * 60) + request_var('time_limit_seconds', 0);
				$quiz_time_limit = ($quiz_time_limit < 1) ? null : $quiz_time_limit;
			}

			if ($check_correct && $quiz_name && $category_is_valid)
			{
				$quiz_question = new quiz_question;
				$quiz_question->insert($quiz_question->refresh_obtain(), $quiz_name, $quiz_category, $quiz_time_limit);

				trigger_error( sprintf($user->lang['UQM_QUIZ_SUBMITTED'], '<a href="' . append_sid("{$phpbb_root_path}quiz.$phpEx") . '">', '</a>') );
			}
	
			else
			{
				// If the user has missed an answer, bring the page back up with a message
				$alter_question	= true;
				$enter_answers	= true;

				// Either select all answers, a quiz name or select another category - will be the message displayed
				$quiz_message	= ($category_is_valid) ? $user->lang['UQM_ENTER_ALL_CORRECT'] : $user->lang['UQM_ENTER_VALID_CATEGORY'];
			}
		}

		$empty_twist = false;

		// We want to populate the fields if the page has been submitted, otherwise do not worry
		$populate_fields = null;
		$populate_size	 = 0;

		// If the user wants to add or remove a question from the quiz (and not trying, for some reason, to submit
		// the quiz at the same time or submit to the database!)
		if ($enter_answers && !$submit_db)
		{
			// And here we begin the populating, but with a twist
			// if $empty_twist is TRUE after being passed by reference then there are some empty fields still
			$empty_twist = false;

			$quiz_question = new quiz_question;
			$populate_fields = $quiz_question->refresh_obtain($empty_twist);
			$populate_size = sizeof($populate_fields);

			if ($empty_twist)
			{
				$quiz_message = $user->lang['UQM_ENSURE_FIELDS_ARE_FILLED'];
				$enter_answers = false;
			}
		}

		else if ($alter_question) // if the user is trying to add or remove a question
		{
			// And here we begin the populating
			$quiz_question = new quiz_question;
			$populate_fields = $quiz_question->refresh_obtain();
			$populate_size = sizeof($populate_fields);

			// Now we want a mechanism so users don't somehow add outside the allowed number of questions
			switch (request_var('alter_question', ''))
			{
				case $user->lang['UQM_PLUS_QUESTION']:
					$alter_question_value = 1;
					break;
				case $user->lang['UQM_MINUS_QUESTION']:
					$alter_question_value = -1;
					break;
				default:
					$alter_question_value = 0;
			}

			if ($quiz_configuration->check_question_boundaries($current_questions, $alter_question_value))
			{
				$current_questions = $current_questions + $alter_question_value;
			}

			else // show a message to the user
			{
				$quiz_message = sprintf($user->lang['UQM_QUESTION_BOUNDARY_VIOLATE'], $quiz_configuration->value('qc_minimum_questions'), $quiz_configuration->value('qc_maximum_questions'));	
			}
		}

		// Show the questions, and if the add or remove button has been clicked act accordingly
		for ($i = 0; $i < $current_questions; $i++)
		{
			// If confirming the answers, get the array. Otherwise condense the answer, or show nothing.
			$existing_answers  = ($i < $populate_size) ? $populate_fields[$i]->show_answers(true) : '';
			$existing_question = ($i < $populate_size) ? $populate_fields[$i]->show_question() : '';

			$template->assign_block_vars('question_row', array(
				'U_QUESTION'			=> $existing_question,
				'U_ANSWERS'			=> $existing_answers,

				'U_QUESTION_ID'			=> $i,
				'U_MAX_MULTIPLE_CHOICE' 	=> $quiz_configuration->value('qc_maximum_choices'),
			));

			// Have the user select the correct answer
			if ($enter_answers)
			{
				// Deal with the form key
				add_form_key('uqm_submit');

				$temp_answer = $populate_fields[$i]->show_answers();
				$answer_id = 0;

				foreach( $temp_answer as $answer )
				{
					$template->assign_block_vars('question_row.answer_row', array(
					'U_ANSWER_ID'			=> $answer_id++,
					'U_ANSWER_NAME'			=> $answer,	
					));
				}
			}

		}

		$s_hidden_fields = build_hidden_fields(array(
			'question_number'	=> $current_questions,
			'submit_db'			=> ($enter_answers) ? true : false,
		));

		// Only show the add and remove buttons if within the boundaries
		$allow_adding 	= ($current_questions < $quiz_configuration->value('qc_maximum_questions')) ? true : false;
		$allow_removing	= ($current_questions > $quiz_configuration->value('qc_minimum_questions')) ? true : false;

		// Time limits
		$minutes_dropdown = '';
		$seconds_dropdown = '';

		if ($time_limits_enabled)
		{
			// Create the drop down menus
			if ($time_limit_minutes > -1 && $time_limit_seconds > -1)
			{
				// If the user already selected it, but had validation issues with their quiz, we want to retain it.
				$minutes_dropdown = $quiz_configuration->create_time_limit_dropdown('time_limit_minutes', 0, 60, $time_limit_minutes);
				$seconds_dropdown = $quiz_configuration->create_time_limit_dropdown('time_limit_seconds', 0, 59, $time_limit_seconds);
			}

			else
			{
				$minutes_dropdown = $quiz_configuration->create_time_limit_dropdown('time_limit_minutes', 0, 60);
				$seconds_dropdown = $quiz_configuration->create_time_limit_dropdown('time_limit_seconds', 0, 59);
			}
		}

		$template->assign_vars( array(
			'S_HIDDEN_FIELDS'			=> $s_hidden_fields,
			'S_SUBMIT_QUIZ_ACTION'		=> append_sid("{$phpbb_root_path}quiz.$phpEx", 'mode=submit'),

			'U_UQM_CONFIRM'				=> $enter_answers,
			'U_UQM_DISPLAY_ADD'			=> ($enter_answers) ? false : $allow_adding,
			'U_UQM_DISPLAY_REMOVE'		=> ($enter_answers) ? false : $allow_removing,
			'U_UQM_DISPLAY_MESSAGE'		=> $quiz_message,

			'U_UQM_ENTER_QUIZ_NAME'		=> $quiz_name,
			'U_QUIZ_CATEGORY_SELECT'	=> $quiz_configuration->categories(0, true),

			// Time limit variables
			'U_TIME_LIMITS_ENABLED'		=> $time_limits_enabled,
			'U_MINUTES_SELECTION'		=> $minutes_dropdown,
			'U_SECONDS_SELECTION'		=> $seconds_dropdown,
		));

		$template->set_filenames(array(
			'body' => 'quiz_submit_body.html')
		);

		page_footer();

		break;
	
	case 'play':
		$quiz_id = request_var('q', 0);
		$quiz_configuration->breadcrumbs( array($user->lang['UQM_QUIZ_PLAY'] => append_sid('quiz.'. $phpEx, 'mode=play&amp;q=' . $quiz_id)) );	
		page_header($user->lang['UQM_QUIZ_PLAY']);

		$quiz_information = $quiz_configuration->determine_quiz_core($quiz_id);

		if (!$quiz_information['quiz_id'] || !$quiz_id)
		{
			trigger_error('UQM_EDIT_NO_QUIZ');
		}

		// Determine if the user viewing the play page is allowed to
		$auth_params = array(
			'quiz_information'	=> $quiz_information,
			'return_value'		=> false,
		);

		$quiz_configuration->auth('play', $auth_params);


		$play = new quiz_question;
		$play_quiz = $play->play($quiz_id); // Get the array of quiz question objects for this quiz
		$count = 0;

		// Check results, as the user has submitted their answers
		if (!empty($_POST['submit']))
		{
			if (!check_form_key('uqm_play'))
			{
				trigger_error('UQM_QUIZ_FORM_INVALID');
			}

			// Keep track of the users' progress
			$user_correct_answers	= 0;
			$user_incorrect_answers	= 0;

			foreach ($play_quiz as $question)
			{
				// Get the actual information
				$actual_answer 		= $question->show_correct();
				$question_answers 	= $question->show_answers();

				// Get the user submitted information, starting with the id of the user selected answer
				$user_submitted_id = request_var('answer_' . $count, -1); 

				// ensure the user has selected an answer by ensuring it is in the question boundary,
				// $db_answer is the corresponding data entry for whatever option the user
				// selected - not necessarily the correct answer
				$db_answer = ($user_submitted_id >= 0 && $user_submitted_id < sizeof($question_answers)) ? $question_answers[$user_submitted_id] : null;

				// Is the users' answer correct or not?
				$is_correct = ($db_answer == $actual_answer) ? true : false;

				// Update progress count
				($is_correct) ? $user_correct_answers++ : $user_incorrect_answers++;

				// Even if we don't use the message, we want to populate the statistics array
				$results_message = $question->obtain_result_data($actual_answer, $db_answer, $question->show_question_id());

				if ($quiz_configuration->value('qc_show_answers'))
				{
					$template->assign_block_vars('result_row', array(
						'U_QUESTION_NAME'	=> $question->show_question(),
						'U_STATUS'		=> $is_correct,
						'U_MESSAGE'		=> $results_message,
					));

				}

				$count++;
			}

			$result_percentage = $quiz_configuration->determine_percentage($user_correct_answers, $user_incorrect_answers);

			// End the quiz session - do this before updating anything else
			$quiz_session_id = $play->update_quiz_session($quiz_id);

			// Update the statistics, as the SQL array's are still stored in the static variable
			$question->obtain_result_data(null, null, null, $quiz_session_id);

			// Handle the group rewards
			$group_rewards = $quiz_configuration->group_rewards($quiz_id);

			// Finish the results by checking if cash compatibility is enabled
			$quiz_configuration->cash($user_correct_answers, $user_incorrect_answers);

			$template->assign_vars( array(
				'U_QUIZ_RESULTS'	=> sprintf($user->lang['UQM_RESULTS_FOR_QUIZ'], $quiz_information['quiz_name']),
				'U_QUIZ_SUMMARY'	=> sprintf($user->lang['UQM_RESULTS_SUMMARY'], $user_correct_answers, $user_incorrect_answers, $result_percentage),
				'U_SHOW_ANSWERS'	=> $quiz_configuration->value('qc_show_answers'),
				'U_GROUP_REWARDS'	=> (isset($group_rewards)) ? $group_rewards : false,
				'U_RETURN_TO_INDEX'	=> sprintf($user->lang['UQM_RESULTS_RETURN_TO_INDEX'], '<a href="' . append_sid('quiz.'.$phpEx) . '">', '</a>'),
			));

			$template->set_filenames(array(
				'body' => 'quiz_results_body.html')
			);

			page_footer();
		}

		// The actual play quiz page - start a new quiz session
		$play->insert_quiz_session($quiz_id);

		foreach ($play_quiz as $question)
		{
			$template->assign_block_vars('question_row', array(
				'U_QUESTION_ID'		=> $count,
				'U_QUESTION_NAME'	=> $question->show_question(),
			));

			$question_answers = $question->show_answers();
			$answer_size = sizeof($question_answers);

			for( $i = 0; $i < $answer_size; $i++ )
			{
				$template->assign_block_vars('question_row.answer_row', array(
					'U_ANSWER_ID'		=> $i,
					'U_ANSWER_NAME'		=> $question_answers[$i],
				));
			}

			$count++;	
		}

		// Add the form key
		add_form_key('uqm_play');

		$template->assign_vars( array(
			'S_SUBMIT_QUIZ_ACTION'	=> append_sid("{$phpbb_root_path}quiz.$phpEx", 'mode=play&amp;q=' . $quiz_id),
			'U_QUIZ_NAME'		=> $quiz_information['quiz_name'],
			'U_QUIZ_TIME_LIMIT'	=> $quiz_information['quiz_time_limit'],
			'U_REDIRECT_QUIZ_INDEX'	=> append_sid('quiz.' . $phpEx),
			'U_TIME_LIMIT_ENABLED'	=> $quiz_configuration->value('qc_enable_time_limits'),
			'U_POSTED_INFORMATION'	=> sprintf($user->lang['UQM_QUIZ_AUTHOR_DETAILS'], get_username_string('full', $quiz_information['user_id'], $quiz_information['username'], $quiz_information['user_colour']), $user->format_date($quiz_information['quiz_time'])),
		));
		
		$template->set_filenames(array(
			'body' => 'quiz_play_body.html')
		);

		page_footer();

		break;

	case 'statistics':
		$quiz_id = request_var('q', 0);
		$quiz_configuration->breadcrumbs( array($user->lang['UQM_QUIZ_STATISTICS'] => append_sid('quiz.'. $phpEx, 'mode=statistics&amp;q=' . $quiz_id)) );	
		page_header($user->lang['UQM_QUIZ_STATISTICS']);

		$quiz_information = $quiz_configuration->determine_quiz_core($quiz_id);

		if (!$quiz_information['quiz_id'])
		{
			trigger_error('UQM_EDIT_NO_QUIZ');
		}

		if ($quiz_id)
		{
			$quiz_statistics = new quiz_statistics;
			$quiz_statistics->initialise($quiz_id);

			// Determine if the user viewing this page is allowed to
			$auth_params = array(
				'quiz_information'	=> $quiz_information,
				'user_id'			=> (int) $user->data['user_id'],
				'played_quiz'		=> $quiz_statistics->has_user_played_quiz($quiz_id, $user->data['user_id']),
				'administrator'		=> $auth->acl_get('a_'),
				'return_value'		=> false,
			);

			$quiz_configuration->auth('statistics', $auth_params);

			$quiz_statistics->average_scores();
			$quiz_statistics->question_summary();
			$quiz_statistics->survey();
		}

		$template->set_filenames(array(
			'body' => 'quiz_statistics_body.html')
		);

		page_footer();

		break;

	case 'edit':
		$quiz_id = request_var('q', 0);
		$quiz_configuration->breadcrumbs( array($user->lang['UQM_EDIT_QUIZ'] => append_sid('quiz.'. $phpEx, 'mode=edit&amp;q=' . $quiz_id)) );	
		page_header($user->lang['UQM_EDIT_QUIZ']);

		$quiz_information = $quiz_configuration->determine_quiz_core($quiz_id);

		if( !$quiz_information['quiz_id'] )
		{
			trigger_error('UQM_EDIT_NO_QUIZ');
		}

		// Determine if the user viewing the edit page is allowed to
		$auth_params = array(
			'quiz_information'	=> $quiz_information,
			'user_id'		=> (int) $user->data['user_id'],
			'administrator'		=> $auth->acl_get('a_'),
			'return_value'		=> false,
		);

		$quiz_configuration->auth('edit', $auth_params);
		$display_message = '';

		// The enabled var will be used a few times
		$time_limits_enabled = ($quiz_configuration->value('qc_enable_time_limits')) ? true : false;

		// Try submitting, but only if everything is in order
		if (!empty($_POST['submit']))
		{
			if( !check_form_key('uqm_edit') )
			{
				trigger_error('UQM_QUIZ_FORM_INVALID');
			}

			$id_array = $quiz_configuration->determine_quiz_questions($quiz_id);

			// Does the user want to delete the quiz? If so, delete all of its contents
			if( !empty($_POST['delete_quiz']) )
			{
				$quiz_question = new quiz_question;
				$quiz_question->delete($quiz_id, $id_array);

				trigger_error( sprintf($user->lang['UQM_DELETE_QUIZ_SUBMITTED'], '<a href="' . append_sid("{$phpbb_root_path}quiz.$phpEx") . '">', '</a>') );
			}

			// On with simply editing the question then!
			$question_number = request_var('question_number', $quiz_configuration->value('qc_minimum_questions'));
			$quiz_name = request_var('quiz_name', $quiz_information['quiz_name']);
			$new_category = request_var('category', 1);
			$question_array = array();

			// Iterate through the question ids
			foreach($id_array as $i)
			{
				$quiz_question	= new quiz_question;
				$answer_array	= array();
				$answer_count	= 0;

				$question_name	= request_var('question_name_' . $i, '');
				$answer		= request_var('user_answer_' . $i . '_' . $answer_count, '');
				$correct_answer	= request_var('user_answer_' . $i . '_' . request_var('answer_' . $i, -1), '');

				// Loop through the multiple answers until there are no more
				while( !empty($answer) )
				{
					$answer_array[] = $answer;
					$answer_count++;

					// Update the answer value with the next...
					$answer = request_var('user_answer_' . $i . '_' . $answer_count, '');
				}

				// No answer was given, or no CORRECT answer was given so break from the loop and notify
				// the user of the problem
				if( $answer_count < 1 || !$correct_answer || !$question_name )
				{
					$display_message = $user->lang['UQM_EDIT_VERIFY_ANSWERS'];
					break;
				}

				$quiz_question->initialise($question_name, $answer_array, $correct_answer, $i);
				$question_array[] = $quiz_question;

				unset($quiz_question);
			}

		// Prepare the quiz for updating in the database by calling the update function in quiz_question
		$new_quiz_name 	= ($quiz_name != $quiz_information['quiz_name']) ? $quiz_name : null;

		// Time limit
		$quiz_time_limit = null;

		if ($time_limits_enabled)
		{
			// Calculate the time limit in seconds
			$quiz_time_limit = (request_var('time_limit_minutes', 0) * 60) + request_var('time_limit_seconds', 0);
			$quiz_time_limit = ($quiz_time_limit < 1) ? null : $quiz_time_limit;
		}

		$update_quiz = new quiz_question;
		$update_quiz->update($question_array, $new_quiz_name, $quiz_id, $new_category, $quiz_time_limit);
		
		trigger_error( sprintf($user->lang['UQM_EDIT_QUIZ_SUBMITTED'], '<a href="' . append_sid("{$phpbb_root_path}quiz.$phpEx") . '">', '</a>') );
		}
		
		$quiz_question = new quiz_question;
		$questions_list = $quiz_question->edit($quiz_id);

		foreach($questions_list as $question)
		{
			$answers_list = $question->show_answers();
			$correct_answer = $question->show_correct();

			$template->assign_block_vars('question_row', array(
				'U_QUESTION_ID'		=> $question->show_question_id(),
				'U_QUESTION_NAME'	=> $question->show_question(),
			));

			$i = 0;
			foreach($answers_list as $answer)
			{
				$template->assign_block_vars('question_row.answer_row', array(
					'U_ANSWER_ID'	=> $i,
					'U_ANSWER_NAME'	=> $answer,
					'U_CORRECT'	=> ($answer == $correct_answer) ? true : false,
				));
				
				$i++;			
			}
		}

		// Handle the form key
		add_form_key('uqm_edit');

		$s_hidden_fields = build_hidden_fields(array(
			'question_number'	=> sizeof($questions_list),
		));

		// Time limits
		$minutes_dropdown = '';
		$seconds_dropdown = '';

		if ($time_limits_enabled)
		{
			$current_time_limit = (int) $quiz_information['quiz_time_limit'];
			
			$current_seconds = (int) ($current_time_limit % 60);
			$current_minutes = (int) (($current_time_limit - $current_seconds) / 60);

			// Create the drop down menus for editing
			$minutes_dropdown = $quiz_configuration->create_time_limit_dropdown('time_limit_minutes', 0, 60, $current_minutes);
			$seconds_dropdown = $quiz_configuration->create_time_limit_dropdown('time_limit_seconds', 0, 59, $current_seconds);
		}

		$template->assign_vars( array(
			'S_HIDDEN_FIELDS'			=> $s_hidden_fields,

			'U_QUIZ_NAME' 				=> $quiz_information['quiz_name'],
			'U_UQM_DISPLAY_MESSAGE'		=> $display_message,
			'U_QUIZ_CATEGORY_SELECT'	=> $quiz_configuration->categories($quiz_information['quiz_category']),

			// Time limit variables
			'U_TIME_LIMITS_ENABLED'		=> $time_limits_enabled,
			'U_MINUTES_SELECTION'		=> $minutes_dropdown,
			'U_SECONDS_SELECTION'		=> $seconds_dropdown,
		));

		$template->set_filenames(array(
			'body' => 'quiz_edit_body.html')
		);

		page_footer();		

		break;

	// Quiz index page and category view
	default:
		page_header($user->lang['UQM_QUIZ']);

		$index_view 	= true;
		
		$category_id	= request_var('c', 0);
		$pagination		= null;
		$page			= null;

		// Category view
		if ($category_id)
		{
			$index_view = false;

			$start = request_var('start', 0);
			$quizzes_per_page = $quiz_configuration->value('qc_quizzes_per_page');

			$category_data = initialise_quiz_category($quizzes_per_page, $start, $category_id);

			// Display the quizzes
			foreach ($category_data as $category)
			{
				display_category($category);

				foreach ($category['quizzes'] as $quiz)
				{
					display_quiz($quiz);
				}
			}

			// Determine the number of quizzes in this category for pagination. And some other pagination stuff.
			$sql = 'SELECT COUNT(quiz_id) AS quizzes_in_this_category
					FROM ' . QUIZ_TABLE . '
					WHERE quiz_category = ' . (int) $category_id;
			$result = $db->sql_query($sql);

			$quizzes_in_this_category = $db->sql_fetchfield('quizzes_in_this_category');

			// Validate the page number
			if ($start < 0 || $start >= $quizzes_in_this_category)
			{
				trigger_error('UQM_CATEGORY_NO_QUIZZES_FOUND');
			}

			// Initialise phpBB3's pagination functions
			$page_url 	= append_sid($phpbb_root_path . 'quiz.' . $phpEx, 'c=' . $category_id);
			$pagination	= generate_pagination($page_url, $quizzes_in_this_category, $quizzes_per_page, $start);
			$page 		= on_page($quizzes_in_this_category, $quizzes_per_page, $start);
		}

		// Quiz index view
		else
		{
			$index_view = true;

			// The maximum number of quizzes we want to show for a category
			$quizzes_on_index = $quiz_configuration->value('qc_quizzes_on_index');

			$category_data = initialise_quiz_category($quizzes_on_index, 0);

			// Iterate through the quizzes in the category data to get the latest additions, to display at the top
			// of the quiz index page.
			$all_quizzes = array();

			foreach ($category_data as $category)
			{
				foreach ($category['quizzes'] as $quiz)
				{
					// TODO: Exclude this quiz if it doesn't have the correct permissions
					$all_quizzes[] = $quiz;
				}
			}

			// Sort the quizzes by latest time using the callback, then take only what we need
			usort($all_quizzes, 'sort_quiz_by_time');
			$recent_quizzes = array_slice($all_quizzes, 0, $quizzes_on_index);

			// Add the recent quizzes to the index as if it was a category. 
			// No need to do this if there are no quizzes yet though.
			if (sizeof($recent_quizzes) > 0)
			{
				// We set this as a virtual category so we know to treat it differently
				$recent_category = array(
					'virtual_category'		=> true,
					'category_name' 		=> $user->lang['UQM_RECENTLY_ADDED_QUIZZES'],
					'category_link'			=> '',
					'category_description'	=> '',
					'quizzes'				=> $recent_quizzes
				);

				// Shift all of the categories up one, so that the recent quizzes appear first...
				for ($i = sizeof($category_data); $i > 0; $i--)
				{
					$category_data[$i] = $category_data[$i - 1];
					$category_data[$i - 1] = null;
				} 

				// And add recent categories at the beginning, now that the first element has been freed.
				$category_data[0] = $recent_category;
			}

			// Now display everything on the index
			foreach ($category_data as $category)
			{
				// First the category gets displayed
				display_category($category);

				foreach ($category['quizzes'] as $quiz)
				{
					// And then we add each quiz (or at least the latest few!) to the category for viewing on the index
					display_quiz($quiz);
				}
			}
		}
		
		$submit_auth_params = array(
		 	'administrator' => $auth->acl_get('a_'), 
			'submit_setting' => $quiz_configuration->value('qc_admin_submit_only'), 
			'return_value' => true,
		);
		
		// Templating is independent of category or index
		$template->assign_vars( array(
			'L_SUBMIT_UPPER'		=> strtoupper($user->lang['UQM_SUBMIT_QUIZ']),

			'U_UQM_SUBMIT'			=> ($quiz_configuration->auth('submit', $submit_auth_params)) ? append_sid("{$phpbb_root_path}quiz.$phpEx", 'mode=submit') : '',
			'U_UQM_STATS'			=> append_sid("{$phpbb_root_path}quiz.$phpEx", 'mode=statistics'),

			'U_PAGINATION'			=> $pagination,
			'U_PAGE_NUMBER'			=> $page,

			'U_INDEX_VIEW'			=> $index_view,
		)); 

		$template->set_filenames(array(
			'body' => 'quiz_body.html')
		);

		page_footer();
}

// Take in a single quiz (information, not questions) and produce the templating variables for it.
function display_quiz($quiz)
{
	global $template, $user;

	$template->assign_block_vars('category_row.quiz_row', array(
		'U_QUIZ_NAME'			=> $quiz['quiz_name'],
		'U_QUIZ_LINK'			=> $quiz['quiz_link'],
		'U_QUIZ_AUTHOR'			=> $quiz['quiz_author'],	
		'U_QUIZ_DATE'			=> $user->format_date($quiz['quiz_time']),
		'U_QUIZ_INFO'			=> $quiz['quiz_info']
	));
}

// Take in a single category and produce the templating variables for it.
function display_category($category)
{
	global $template, $user;

	$template->assign_block_vars('category_row', array(
		'U_VIRTUAL_CATEGORY'		=> $category['virtual_category'],
		'U_CATEGORY_NAME'			=> $category['category_name'],
		'U_CATEGORY_LINK'			=> $category['category_link'],
		'U_CATEGORY_DESCRIPTION'	=> $category['category_description'],
		'U_CATEGORY_NO_QUIZZES'		=> (empty($category['quizzes']) || sizeof($category['quizzes']) == 0),
		'U_CATEGORY_VIEW_ALL'		=> sprintf($user->lang['UQM_CATEGORY_VIEW_ALL'], '<a href="' . $category['category_link'] . '">', '</a>')
	));
}

// Get all of the quiz and category data we need for the index page or the category view page
function initialise_quiz_category($limit, $start, $category_id = null)
{	
	global $auth, $db, $user, $phpEx, $phpbb_root_path, $quiz_configuration;

	// Determine if an individual category is being sought
	$individual_category_selected = isset($category_id);

	// Just a quick check to make sure we have something to display. If the user has no viewable categories, no point
	// continuing on!
	ensure_viewable_categories_exist();

	$category_data = array();
	$category_sql = 'SELECT * FROM ' . QUIZ_CATEGORIES_TABLE;

	if ($individual_category_selected)
	{
		if (!in_array($category_id, $quiz_configuration->qc_user_viewable_categories))
		{
			// Don't go any further if the user can't view this category
			trigger_error('UQM_CATEGORY_NO_PERMISSION');
		}

		else
		{
			// Getting one category
			$category_sql .= ' WHERE quiz_category_id = ' . (int) $category_id;
		}
	}

	else
	{
		// Getting all categories for the index... need to filter properly though
		$category_sql .= ' WHERE ' . $db->sql_in_set('quiz_category_id', $quiz_configuration->qc_user_viewable_categories);
	}

	// If we get to this point, hopefully we have some categories to display!
	$category_result = $db->sql_query($category_sql);

	// Get each of the categories
	while ($category_row = $db->sql_fetchrow($category_result))
	{
		$quizzes_data = array();

		$quizzes_sql = 'SELECT * FROM ' . QUIZ_TABLE . '
						WHERE quiz_category = ' . $category_row['quiz_category_id'] . ' 
						' . (($individual_category_selected) ? 'AND quiz_category = ' . (int) $category_id : '') . '
						ORDER BY quiz_time DESC';
		$quizzes_result = $db->sql_query_limit($quizzes_sql, $limit, $start);

		// Get each of the top x quizzes from the category
		while ($quizzes_row = $db->sql_fetchrow($quizzes_result))
		{
			$quiz_statistics = new quiz_statistics;

			// Some key quiz details
			$played_quiz = $quiz_statistics->has_user_played_quiz($quizzes_row['quiz_id'], $user->data['user_id']);
			$link = append_sid($phpbb_root_path . 'quiz.' . $phpEx, 'mode=play&amp;q=' . $quizzes_row['quiz_id']);
			$author = sprintf($user->lang['UQM_QUIZ_SUBMITTED_BY'], get_username_string(
					'full', 
					$quizzes_row['user_id'], 
					$quizzes_row['username'], 
					$quizzes_row['user_colour']
				)
			);

			$auth_params = array(
				'quiz_information'	=> $quizzes_row,
				'user_id'			=> (int) $user->data['user_id'],
				'played_quiz' 		=> $played_quiz,
				'administrator'		=> $auth->acl_get('a_'),
				'submit_setting'	=> $quiz_configuration->value('qc_admin_submit_only'),
				'return_value'		=> true,
			);

			$quizzes_data[] = array(
				'quiz_id'			=> $quizzes_row['quiz_id'],
				'quiz_name' 		=> $quizzes_row['quiz_name'],
				'quiz_time'			=> $quizzes_row['quiz_time'],
				'quiz_link'			=> $link,
				'quiz_author'		=> $author,
				'quiz_info'			=> $quiz_configuration->determine_quiz_information($auth_params)
			);
		}

		$category_link = append_sid($phpbb_root_path . 'quiz.' . $phpEx, 'c=' . $category_row['quiz_category_id']);

		// Construct category name, category description, x category quizzes array
		$category_data[] = array(
			'virtual_category'		=> false,
			'category_name' 		=> $category_row['quiz_category_name'],
			'category_description'	=> $category_row['quiz_category_description'],
			'category_link'			=> $category_link,
			'quizzes'				=> $quizzes_data
		);
	}

	if (sizeof($category_data) == 0)
	{
		// If nothing got returned, display a nice error message to the user
		trigger_error('UQM_CATEGORIES_NOT_AVAILABLE');
	}

	return $category_data;
}

// A callback for usort, we'll sort by time but default to id if two quizzes have the same time.
function sort_quiz_by_time($quiz_one, $quiz_two)
{
	$sort_order = $quiz_two['quiz_time'] - $quiz_one['quiz_time'];

	if ($sort_order == 0)
	{
		$sort_order = $quiz_two['quiz_id'] - $quiz_one['quiz_id'];
	}
	
	return $sort_order;
}

function ensure_viewable_categories_exist()
{
	global $quiz_configuration;

	if (sizeof($quiz_configuration->qc_user_viewable_categories) == 0)
	{
		// If the size of the viewable categories list is 0, then we have no appropriate categories to display
		trigger_error('UQM_CATEGORIES_NOT_AVAILABLE');
	}
}
?>
