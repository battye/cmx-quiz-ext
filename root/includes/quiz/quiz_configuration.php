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

		// Load in configuration values from config table
		$this->qc_config_array = array('qc_minimum_questions', 'qc_maximum_questions', 'qc_maximum_choices', 'qc_show_answers', 'qc_quiz_author_edit', 'qc_admin_submit_only', 'qc_cash_enabled', 'qc_cash_column', 'qc_cash_correct', 'qc_cash_incorrect');
		$this->qc_config_value = array(
			'qc_minimum_questions'		=> array('value' => $config['qc_minimum_questions'], 'type' => 'input'),
			'qc_maximum_questions'		=> array('value' => $config['qc_maximum_questions'], 'type' => 'input'),
			'qc_maximum_choices'		=> array('value' => $config['qc_maximum_choices'], 'type' => 'input'),
			'qc_show_answers'		=> array('value' => $config['qc_show_answers'], 'type' => 'radio'), // show the answers after a quiz
			'qc_quiz_author_edit'		=> array('value' => $config['qc_quiz_author_edit'], 'type' => 'radio'), // allow the author to edit their own quiz
			'qc_admin_submit_only'		=> array('value' => $config['qc_admin_submit_only'], 'type' => 'radio'), // only administrators are allowed to submit quizzes

			// Cash compatibility
			'qc_cash_enabled'		=> array('value' => $config['qc_cash_enabled'], 'type' => 'radio'), // enable cash functionality
			'qc_cash_column'		=> array('value' => $config['qc_cash_column'], 'type' => 'input'), // associated column in the users table
			'qc_cash_correct'		=> array('value' => $config['qc_cash_correct'], 'type' => 'input'), // cash gained for a correct answer
			'qc_cash_incorrect'		=> array('value' => $config['qc_cash_incorrect'], 'type' => 'input'), // cash REMOVED for an incorect answer
		);
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
