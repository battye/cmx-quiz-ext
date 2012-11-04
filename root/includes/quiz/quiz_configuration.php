<?php
// quiz_configuration class
// Handle quiz configuration values and any simple functions that need to be performed

if( !defined('IN_PHPBB') )
{
	exit;
}

class quiz_configuration
{
	var $qc_config_array;
	var $qc_config_value;

	function load()
	{
		global $config;

		// Load in configuration fields from config table
		$this->qc_config_array = array(
			'qc_minimum_questions',			// minimum number of questions allowed
			'qc_maximum_questions',			// maximum number of questions allowed
			'qc_maximum_choices',			// maximum number of multiple choices
			'qc_show_answers',				// show the answers after a quiz
			'qc_quiz_author_edit',			// allow the author to edit their own quiz
			'qc_admin_submit_only',			// only administrators are allowed to submit quizzes
			'qc_enable_time_limits', 		// are time limits on or off
			'qc_exclusion_time',			// exclusion time (seconds) if a user violates the time limit
			'qc_cash_enabled',				// enable cash functionality
			'qc_cash_column', 				// associated column in the users table
			'qc_cash_correct',				// cash gained for a correct answer
			'qc_cash_incorrect',			// cash deducted for an incorect answer 
		);

		// Get their values. We'll define the keys and values separately in case we need to
		// manipulate the values at some point in the future.
		$this->qc_config_value = array(
			'qc_minimum_questions'		=> $this->generate_config_array('qc_minimum_questions', 'input'),
			'qc_maximum_questions'		=> $this->generate_config_array('qc_maximum_questions', 'input'),
			'qc_maximum_choices'		=> $this->generate_config_array('qc_maximum_choices', 'input'),

			'qc_show_answers'			=> $this->generate_config_array('qc_show_answers', 'radio'),
			'qc_quiz_author_edit'		=> $this->generate_config_array('qc_quiz_author_edit', 'radio'),
			'qc_admin_submit_only'		=> $this->generate_config_array('qc_admin_submit_only', 'radio'), 
			'qc_enable_time_limits'		=> $this->generate_config_array('qc_enable_time_limits', 'radio'),
			'qc_exclusion_time'			=> $this->generate_config_array('qc_exclusion_time', 'input'),

			'qc_cash_enabled'			=> $this->generate_config_array('qc_cash_enabled', 'input'),
			'qc_cash_column'			=> $this->generate_config_array('qc_cash_column', 'input'),

			'qc_cash_correct'			=> $this->generate_config_array('qc_cash_correct', 'input'), 
			'qc_cash_incorrect'			=> $this->generate_config_array('qc_cash_incorrect', 'input'), 
		);
	}

	// Rather than creating an array each time we can use this function to make it for us
	function generate_config_array($configuration_name, $input_type)
	{
		global $config;

		return array('value' => $config[$configuration_name], 'type' => $input_type);
	}

	// Return this list of config values
	function config_array()
	{
		return $this->qc_config_array;
	}

	// Update configuration values
	function update($name, $value)
	{
		if( array_search($name, $this->qc_config_array) !== FALSE )
		{ 
			set_config($name, $value);
		}
	}

	// Check if cash functionality is enabled and if so update the users table accordingly
	// depending on the results the user got in the quiz
	function cash($correct, $incorrect)
	{
		global $user, $db, $template;

		// Only do anything if cash is enabled
		if( $this->value('qc_cash_enabled') )
		{
			$cash_column	 = $db->sql_escape($this->value('qc_cash_column'));

			$cash_earned	 = ($correct * $this->value('qc_cash_correct'));
			$cash_squandered = ($incorrect * $this->value('qc_cash_incorrect'));

			$difference	 = $cash_earned - $cash_squandered;

			$sql = 'UPDATE ' . USERS_TABLE . '
				SET ' . $cash_column . ' = ' . $cash_column . ' + ' . $difference . '
				WHERE user_id = ' . (int) $user->data['user_id'];

			$db->sql_query($sql);

			$cash_message = ($difference < 0) ? sprintf($user->lang['UQM_QUIZ_CASH_LOST'], (0-$difference)) : sprintf($user->lang['UQM_QUIZ_CASH_GAIN'], $difference); 
			$template->assign_var('U_QUIZ_CASH', $cash_message);
		}
	}

	// Group rewards: if the admin has specified, then if a user completes all quizzes in a category to a certain
	// accuracy level they will be moved into a usergroup. If they are moved into a usergroup, a message will be returned.
	function group_rewards($quiz_id)
	{
		global $db, $user, $phpbb_root_path, $phpEx;

		// Include this for the usergroup functions
		include($phpbb_root_path . 'includes/functions_user.' . $phpEx);

		// Get the basic quiz and category details
		$sql = 'SELECT c.* 
				FROM ' . QUIZ_CATEGORIES_TABLE . ' c, ' . QUIZ_TABLE . ' q
				WHERE q.quiz_category = c.quiz_category_id
					AND q.quiz_id = ' . (int) $quiz_id;

		$result	= $db->sql_query($sql);
		$row 	= $db->sql_fetchrow($result);

		// Get the data
		$quiz_category_id							= $row['quiz_category_id'];
		$quiz_category_destination_group_id 		= $row['quiz_category_destination_group_id'];
		$quiz_category_destination_group_percentage	= $row['quiz_category_destination_group_percentage'];

		$db->sql_freeresult($result);

		// If either are null, then there is no group rewards for this category
		if (isset($quiz_category_destination_group_id) && isset($quiz_category_destination_group_percentage))
		{
			// The first thing to do is see if the user is a member of the usergroup. Because if they are, there is no
			// point continuing...
			if (group_memberships($quiz_category_destination_group_id, $user->data['user_id'], true))
			{
				return;
			}

			// Get the statistics data
			$sql = 'SELECT s.*, q.question_quiz
					FROM ' . QUIZ_STATISTICS_TABLE . ' s, ' . QUIZ_QUESTIONS_TABLE . ' q, ' . QUIZ_TABLE . ' t
					WHERE s.quiz_user = ' . $user->data['user_id'] . '
						AND q.question_id = s.quiz_question_id
						AND t.quiz_id = q.question_quiz
						AND t.quiz_category = ' . $quiz_category_id . '
					ORDER BY q.question_quiz';

			$result = $db->sql_query($sql);

			// Raw statistics information for a user
			$statistics_array = array();
			
			// Keep a record of the quiz ids played by the user
			$quiz_id_array	= array();
			$quiz_scores	= array();

			while ($row = $db->sql_fetchrow($result))
			{
				$is_correct = false;

				if ($row['quiz_is_correct'] > 0)
				{
					$is_correct = true;
				}

				$statistics_array[$row['quiz_session_id']][] = array(
					'quiz_id'			=> $row['question_quiz'],
					'question_id'		=> $row['quiz_question_id'],
					'quiz_session_id'	=> $row['quiz_session_id'],
					'is_correct'		=> (bool) $is_correct
				);

				// Add the played quiz id to the array if it's not already there
				if (!in_array($row['question_quiz'], $quiz_id_array))
				{
					$quiz_id_array[] = $row['question_quiz'];

					// We'll start this array now. Essentially, it will keep a record of the top percentage
					// a user has for that quiz.
					$quiz_scores[$row['question_quiz']] = 0;
				}
			}			

			// First check that the user has completed all quizzes in the category (by doing a NOT IN).
			$sql = 'SELECT COUNT(quiz_id) AS unplayed
					FROM ' . QUIZ_TABLE . '
					WHERE ' . $db->sql_in_set('quiz_id', $quiz_id_array, true) . '
					AND quiz_category = ' . $quiz_category_id;

			$result = $db->sql_query($sql);

			// We will only proceed beyond this point if the user has played all of the quizzes in the category
			if ($db->sql_fetchfield('unplayed') > 0)
			{
				return;
			}

			// The unique keys are also a list of session ids for that user
			// We just need to see if they scored the minimum percentage for each quiz.
			$sessions_played = array_keys($statistics_array);		

			// Now check the results; each $quiz_played is a quiz_id
			foreach ($sessions_played as $quiz_session)
			{
				$correct_answers 	= 0;
				$incorrect_answers 	= 0;
				$session_quiz_id 	= 0;

				// Loop through each question in the statistics array for this session
				foreach ($statistics_array[$quiz_session] as $statistics_item)
				{
					$session_quiz_id = ($session_quiz_id == 0) ? $statistics_item['quiz_id'] : $session_quiz_id;

					if ($statistics_item['is_correct'])
					{
						// The user got this question correct
						$correct_answers++;
					}

					else
					{
						// The user got this question incorrect
						$incorrect_answers++;
					}
				}

				// The percentage the user got for this quiz in this session
				$session_percentage = 100 * $correct_answers / ($correct_answers + $incorrect_answers);
			
				// If this is the highest percentage so far for a user in this quiz, we'll update this variable.
				$quiz_scores[$session_quiz_id] = ($session_percentage > $quiz_scores[$session_quiz_id]) ? $session_percentage : $quiz_scores[$session_quiz_id];
			}

			// Let's look at all of the top quiz scores by this user, now that we have them
			foreach ($quiz_scores as $score)
			{
				if ($score < $quiz_category_destination_group_percentage)
				{
					// Any score below the threshold means there is no point continuing
					return;
				}
			}

			// If we've reached this point, then we can move the user to the usergroup!
			group_user_add($quiz_category_destination_group_id, $user->data['user_id']);

			return sprintf($user->lang['UQM_RESULTS_GROUP_REWARD'], $quiz_category_destination_group_percentage, get_group_name($quiz_category_destination_group_id));
		}
	}

	// Return the configuration value for "$setting"
	function value($setting, $type = false)
	{
		$configuration_value = false;

		if( array_search($setting, $this->qc_config_array) === FALSE )
		{
			trigger_error('UQM_QUIZ_CONFIG_ERROR');
		}

		else
		{
			// Return the type of setting (ie. input, radio for something the user enters or 
			// clicks yes/no respectively) if type is defined.
			$array_element = ($type) ? 'type' : 'value';
			$configuration_value = $this->qc_config_value[$setting][$array_element];
		}

		return $configuration_value;
	}

	// Handle the breadcrumbs (navigational links) for each quiz page
	function breadcrumbs($links)
	{
		global $template, $user, $phpEx;

		foreach($links as $name => $link)
		{
			$template->assign_block_vars('navlinks', array(
				'FORUM_NAME'	=> $name,
				'U_VIEW_FORUM'	=> $link,
			));
		}
	}

	// Check to see whether the "new" question value would fall outside
	// of the boundaries of minimum and maximum questions and return false
	// if this is the case	
	function check_question_boundaries($in_questions, $alteration)
	{
		$follow = true;

		if( 	($in_questions + $alteration) < $this->value('qc_minimum_questions') ||
			($in_questions + $alteration) > $this->value('qc_maximum_questions') )
		{
			$follow = false;
		}

		return $follow;
	}

	// Ensure that every question has an associated correct answer
	function check_correct_checked($question_number)
	{
		$empty = true;

		for( $i = 0; $i < $question_number; $i++ )
		{
			$answer_given = request_var('answer_' . $i, -1);

			if( $answer_given < 0 )
			{
				$empty = false;
				break;
			}
		}

		return $empty;
	}

	// List quiz categories
	function categories($default_id = 0)
	{
		global $db;

		$sql = 'SELECT * FROM ' . QUIZ_CATEGORIES_TABLE;
		$result = $db->sql_query($sql);

		$select = '<select name="category">';
		while( $row = $db->sql_fetchrow($result) )
		{
			$selected = ($row['quiz_category_id'] == $default_id) ? ' selected="selected"' : '';
			$select .= '	<option value="' . $row['quiz_category_id'] . '"' . $selected . '>' . $row['quiz_category_name'] . '</option>';
		}
		$select .= '</select>';
	
		return $select;
	}

	// Create dropdown menus for time limits
	function create_time_limit_dropdown($name, $low_limit, $high_limit, $selected = null)
	{
		$selected = (empty($selected)) ? $low_limit : $selected;

		$select = '<select name="' . $name . '">';

		for ($i = $low_limit; $i <= $high_limit; $i++)
		{
			$default = ($i == $selected) ? ' selected="selected"' : '';
			$select .= '	<option value="' . $i . '"' . $default . '>' . $i . '</option>';
		}

		$select .= '</select>';
	
		return $select;
	}

	// Determine quiz percentage
	function determine_percentage($numerator, $partial_denominator)
	{
		$denominator	= ($numerator + $partial_denominator);

		$multiply	= (!is_int($denominator) || $denominator == 0 || $denominator == null) ? 0 : ((100 * $numerator) / $denominator);
		$format		= number_format($multiply, 0);

		return $format;
	}

	// Determine quiz information such as that from the quiz table
	function determine_quiz_core($in_quiz_id)
	{
		global $db;

		$sql = 'SELECT * FROM ' . QUIZ_TABLE . '
			WHERE quiz_id = ' . (int) $in_quiz_id;

		$result = $db->sql_query_limit($sql, 1);

		return $db->sql_fetchrow($result);
	}

	function determine_quiz_questions($in_quiz_id)
	{
		global $db;

		$question_id_array = array();

		$sql = 'SELECT question_id FROM ' . QUIZ_QUESTIONS_TABLE . '
			WHERE question_quiz = ' . (int) $in_quiz_id;

		$result = $db->sql_query($sql);

		while( $row = $db->sql_fetchrow($result) )
		{
			$question_id_array[] = $row['question_id'];
		}

		return $question_id_array;
	}

	// Determine out of "Edit", "Delete" and "Statistics" which information
	// a user should be allowed to see.
	function determine_quiz_information($auth_params)
	{
		global $user, $phpbb_root_path, $phpEx;

		$string = array();

		if( $this->auth('statistics', $auth_params) )
		{
			$string[] = sprintf($user->lang['UQM_INDEX_STATS'], '<a href="' . append_sid("{$phpbb_root_path}quiz.$phpEx", 'mode=statistics&amp;q=' . $auth_params['quiz_information']['quiz_id']) . '">', '</a>');
		}

		if( $this->auth('edit', $auth_params) )
		{
			$string[] = sprintf($user->lang['UQM_INDEX_EDIT'], '<a href="' . append_sid("{$phpbb_root_path}quiz.$phpEx", 'mode=edit&amp;q=' . $auth_params['quiz_information']['quiz_id']) . '">', '</a>');
		}

		return implode(", ", $string);
	}

	// Authentication for certain pages: 'statistics', 'edit'
	function auth($case, $parameters = false)
	{
		// if the "return_value" parameter is true, then we want to return a true or false value
		// from the function for whether a user does (true) or does not (false) have permissions
		// to the particular case.
		$can_view = true;

		switch($case)
		{
			// Parameters passed in: administrator, qc_admin_submit_only configuration setting
			case 'submit':
				if( $parameters['submit_setting'] && !$parameters['administrator'] ) 
				{				
					if( $parameters['return_value'] )
					{
						$can_view = false;
					}
			
					else
					{
						trigger_error('UQM_SUBMIT_NO_PERMISSIONS');
					}
				}
				
				break;
			// Parameters passed in: administrator, user_id, quiz_information, played_quiz
			case 'statistics':
				$is_author = ($parameters['quiz_information']['user_id'] == $parameters['user_id']) ? true : false;

				if( !$parameters['administrator'] && !$parameters['played_quiz'] && !$is_author )
				{
					// If the user is not an administrator, the quiz author or played the quiz - error
					if( $parameters['return_value'] )
					{
						$can_view = false;
					}

					else
					{
						trigger_error('UQM_QUIZ_STATISTICS_CANNOT_VIEW');
					}
				}
				break;

			// Parameters passed in: administrator, user_id, quiz_information
			case 'edit':
				// Only worry about checking if the user is not an administrator
				if( !$parameters['administrator'] )
				{
					// Is this non-admin user the quiz author?
					$is_author = ($parameters['quiz_information']['user_id'] == $parameters['user_id']) ? true : false;

					// Can users edit their own quizzes?
					if( !($this->value('qc_quiz_author_edit') && $is_author) )
					{
						// Through the negation, we know that the user is not the author
						// The only way this if statement will NOT be accessed is if both the config
						// setting and $is_author are true. If either of them are false, the user
						// don't have the required permissions to edit.

						if( $parameters['return_value'] )
						{
							$can_view = false;
						}

						else
						{
							trigger_error('UQM_EDIT_NOT_ALLOWED');
						}
					}
				}
				break;
		}

	return $can_view;
	}
}
