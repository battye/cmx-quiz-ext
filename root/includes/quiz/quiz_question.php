<?php
// quiz_question class

if( !defined('IN_PHPBB') )
{
	exit;
}

class quiz_question
{
	var $question_id;
	var $question;
	var $answers;
	var $correct;

	function initialise($in_question, $in_answers, $in_correct, $in_question_id = -1)
	{
		global $quiz_configuration;

		$this->answers	= array();

		$this->question	= $in_question;
		$this->correct	= $in_correct;

		// Only allow up to the defined numbers of multiple choices, and exclude any blank answers
		for( $i = 0; $i < $quiz_configuration->value('qc_maximum_choices'); $i++ )
		{
			if( !empty($in_answers[$i]) )
			{
				$this->answers[$i] = $in_answers[$i]; 
			}
		}

		$this->question_id = ($in_question_id >= 0) ? $in_question_id : null;
	}

	function insert($question_array, $quiz_name, $quiz_category)
	{
		global $db, $user;

		$quiz_array = array(
			'quiz_name'		=> utf8_normalize_nfc($quiz_name),
			'quiz_time'		=> time(),
			'quiz_category'		=> (int) $quiz_category,
			'user_id'		=> (int) $user->data['user_id'],
			'username'		=> $user->data['username_clean'],
			'user_colour'		=> $user->data['user_colour'],
		);

		$db->sql_query('INSERT INTO ' . QUIZ_TABLE . ' ' . $db->sql_build_array('INSERT', $quiz_array));

		$quiz_id = $db->sql_nextid();

		$question_insert = array();

		foreach( $question_array as $question_data )
		{
			$data_answers	= $question_data->show_answers();
			$correct_value	= $data_answers[$question_data->correct];

			if( in_array($correct_value, $data_answers) )
			{
				$question_insert[] = array(
					'question_name'		=> utf8_normalize_nfc($question_data->question),
					'question_correct'	=> utf8_normalize_nfc($correct_value),
					'question_answers'	=> utf8_normalize_nfc($question_data->show_answers(true)),
					'question_quiz'		=> (int) $quiz_id,
				);

			}
		}

		$db->sql_multi_insert(QUIZ_QUESTIONS_TABLE, $question_insert);
	}

	// To update an array of questions in the database, ie. such as when editing a quiz
	function update($question_array, $in_quiz_name, $in_quiz_id, $in_quiz_category)
	{
		global $db;

		// update the quiz name and quiz category
		$new_name = '';
		if( !empty($in_quiz_name) )
		{
			$new_name = "quiz_name = '";
			$new_name .= $db->sql_escape( utf8_normalize_nfc($in_quiz_name) );
			$new_name .= "', ";
		}

		$db->sql_query("UPDATE " . QUIZ_TABLE . " SET $new_name
				quiz_category = " . (int) $in_quiz_category . " WHERE quiz_id = " . (int) $in_quiz_id); 
		
		foreach($question_array as $question)
		{
			// Actually update the question data now
			$sql_data = array(
				'question_name'		=> utf8_normalize_nfc($question->show_question()),
				'question_correct'	=> utf8_normalize_nfc($question->show_correct()),
				'question_answers'	=> utf8_normalize_nfc($question->show_answers(true)),
				'question_quiz'		=> (int) $in_quiz_id,
			);
		
			$sql = 'UPDATE ' . QUIZ_QUESTIONS_TABLE . ' 
				SET ' . $db->sql_build_array('UPDATE', $sql_data) . '
				WHERE question_id = ' . (int) $question->show_question_id();
			
			$db->sql_query($sql);
		}
	}

	// Preparation for editing a quiz
	function edit($in_quiz_id)
	{
		return $this->play($in_quiz_id);
	}

	// Delete a quiz and all of its contents
	function delete($in_quiz_id, $in_question_ids)
	{
		global $db;

		// So we want to delete from the quiz, quiz questions and quiz statistics tables
		$sql = array();
		$sql[] = 'DELETE FROM ' . QUIZ_STATISTICS_TABLE . ' WHERE ' . $db->sql_in_set('quiz_question_id', $in_question_ids);
		$sql[] = 'DELETE FROM ' . QUIZ_TABLE . ' WHERE quiz_id = ' . (int) $in_quiz_id;
		$sql[] = 'DELETE FROM ' . QUIZ_QUESTIONS_TABLE . ' WHERE question_quiz = ' . (int) $in_quiz_id;

		foreach($sql as $query)
		{
			// Perform the query
			$db->sql_query($query);
		}
	}

	// Preparation for playing a quiz
	function play($in_quiz_id)
	{
		global $db;

		// The purpose of this function is to create an array of objects of each question for the quiz
		$sql = 'SELECT * FROM ' . QUIZ_QUESTIONS_TABLE . '
			WHERE question_quiz = ' . $in_quiz_id;

		$result = $db->sql_query($sql);
		$object_array = array();

		while( $row = $db->sql_fetchrow($result) )
		{
			$quiz_question = new quiz_question;
			$quiz_question->initialise($row['question_name'], explode("\n", $row['question_answers']), $row['question_correct'], $row['question_id']);

			$object_array[] = $quiz_question;
		}

		$db->sql_freeresult($result);

		return $object_array;
	}

	// Output the results, $actual is the actual answer while $submitted is what the user chose
	function obtain_result_data($actual = null, $submitted = null, $question_id = null)
	{
		global $user, $db;
		static $statistics_array = array();

		// Run the database query
		if( empty($actual) && empty($submitted) )
		{
			$db->sql_multi_insert(QUIZ_STATISTICS_TABLE, $statistics_array);
			$question_result = null;
		}

		// Continue to build the results
		else
		{
			// We will use the actual string results rather than array position for posterity - as any 
			// edits may change those array positions.

			$statistics_array[] = array(
				'quiz_question_id'	=> (int) $question_id,
				'quiz_user_answer'	=> (string) $submitted,
				'quiz_actual_answer'	=> (string) $actual,
				'quiz_is_correct'	=> (int) ($submitted == $actual) ? 1 : 0,
				'quiz_user'		=> (int) $user->data['user_id'],
			);

			$question_result = sprintf($user->lang['UQM_QUIZ_USER_SELECTED'], $submitted, $actual);
		}

		return $question_result;		
	}

	// Obtain question data from the submit page upon adding or removing, etc
	function refresh_obtain(&$any_empty = false)
	{
		global $quiz_configuration;

		$object_array = array();
		$check_empty = false;

		// Firstly, loop through the questions
		for($i = 0; $i < request_var('question_number', 0); $i++)
		{
			$question_name	= request_var('question_name_' . $i, '');
			$answers_name	= request_var('answers_' . $i, '');
			$multiples 	= explode("\n", $answers_name);
			$correct	= request_var('answer_' . $i, '');

			if( !$check_empty )
			{
				$check_empty = ((strlen($question_name) < 1) || (strlen($answers_name) < 1)) ? true : false;
			}

			$object = new quiz_question;
			$object->initialise($question_name, $multiples, $correct);

			$object_array[] = $object; // Add the question object to the list
		}

		$any_empty = $check_empty;

		return $object_array;
	}

	// Accessor for question id
	function show_question_id()
	{
		return $this->question_id;
	}

	// Accessor for question
	function show_question()
	{
		return $this->question;
	}

	// Accessor for the correct answer
	function show_correct()
	{
		return $this->correct;
	}

	// Accessor for answers; if condensed, show it in a linear form. If false, return the actual array
	function show_answers($condensed = false)
	{
		return ($condensed) ? implode("\n", $this->answers) : $this->answers;
	}

	// This function MUST only be uncommented during testing or debugging as the phpBB language mechanism is not used
	function DEBUG()
	{
		echo 'Question ID: ' . $this->question_id;
		echo '<br />';
		echo 'Question: ' . $this->question;
		echo '<br />';
		echo 'Correct answer: ' . $this->correct;
		echo '<br />';
		echo 'Answers: ';
		print_r($this->answers);
		echo '<br /><br />';
	}
}
