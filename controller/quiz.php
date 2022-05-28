<?php
/**
*
* @package CMX Quiz
* @copyright (c) 2022 battye (CricketMX.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace battye\cmxquiz\controller;

/**
 * CMX Quiz - Controller
 */
class quiz
{
	const SUBMIT_PAGE = 1;
	const EDIT_PAGE = 2;
	const PLAY_PAGE = 3;
	const RESULTS_PAGE = 4;

	const TYPE_INPUT_ANSWER = 'input-answer';
	const TYPE_MULTIPLE_CHOICE = 'multiple-choice';

	const ERROR_QUIZ_COMPLETED = 'quiz-completed';
	const ERROR_QUIZ_TIME_LIMIT_EXCEEDED = 'quiz-time-limit-exceeded';
	const ERROR_QUIZ_CANNOT_ACCESS_QUESTION = 'quiz-cannot-access-question';

	/* @var string $root_path */
	protected $root_path;

	/* @var string $php_ext */
	protected $php_ext;

	/* @var \phpbb\request\request $request */
	protected $request;

	/* @var \phpbb\config\config $config */
	protected $config;

	/* @var \phpbb\controller\helper $helper */
	protected $helper;

	/* @var \phpbb\template\template $template */
	protected $template;

	/* @var \phpbb\user $user */
	protected $user;

	/* @var \phpbb\pagination $pagination */
	protected $pagination;

	/* @var \phpbb\language\language $language */
	protected $language;

	/* @var \battye\cmxquiz\manager\manager $manager */
	protected $manager;

	/**
	* Constructor
	*
	* @param \phpbb\reqquest\request			$request
	* @param \phpbb\config\config				$config
	* @param \phpbb\controller\helper			$helper
	* @param \phpbb\template\template			$template
	* @param \phpbb\user						$user
	* @param \phpbb\language\language           $language
	* @param \battye\cmxquiz\quiz\manager 		$manager
	*/
	public function __construct(string $root_path, string $php_ext, \phpbb\request\request $request, \phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, \phpbb\pagination $pagination, \phpbb\language\language $language, \battye\cmxquiz\quiz\manager $manager)
	{
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->request = $request;
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->pagination = $pagination;
		$this->language = $language;
		$this->manager = $manager;

		// Don't run any of this if the admin has disabled quizzes
		$enabled = ($config['cmx_quiz_enabled'] != 0);

		if (!$enabled)
		{
			throw new \phpbb\exception\http_exception(401, 'CMX_QUIZ_NOT_ENABLED');
		}
	}

	/**
	* CMX Quiz index
	*
	* @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/
	public function index()
	{
		$start = $this->request->variable('start', 0);

		$quiz_count = $this->manager->get_quiz_count();
		$quiz_data = $this->manager->get_quiz_list($start);

		foreach ($quiz_data as $row)
		{
			/* @var \battye\cmxquiz\quiz\model\quiz $quiz */
			$quiz = $row['quiz'];
			
			$this->template->assign_block_vars('quizzes', [
				'QUIZ_NAME' => $quiz->quiz_name,
				'QUIZ_DESCRIPTION' => $quiz->quiz_description,
				'QUIZ_PLAYS' => $quiz->get_total_plays(),
				'QUIZ_PLAYED' => ($row['user_plays'] > 0),
				'QUIZ_TAGS' => $quiz->get_tags_data($this->manager->get_quiz_tags()),
				'QUIZ_QUESTIONS' => $quiz->get_question_count(),
				'QUIZ_SUBMISSION_TIME' => $this->user->format_date($quiz->submission_time),

				'U_QUIZ' => $this->helper->route('cmx_quiz_play', ['id' => $quiz->quiz_id]),
				'U_SUBMITTER' => $row['submitter_name'],
			]);
		}
        
		// Pagination
		$per_page = (int) $this->config['cmx_quiz_per_page'];
		$this->pagination->generate_template_pagination(
			$this->helper->route('cmx_quiz_index'), 
			'pagination', 
			'start', 
			$quiz_count['total_quizzes'], 
			$per_page, 
			$start
		);

		$this->template->assign_vars([
			'CMX_QUIZ_COUNT' => $quiz_count['total_quizzes'],
			'CMX_QUIZ_ALLOW_USER_SUBMISSIONS' => $this->config['cmx_quiz_allow_user_submissions'],
			'CMX_QUIZ_MODERATOR' => $this->manager->is_user_quiz_moderator($this->user->data['user_id']),

			'U_SUBMIT' => $this->helper->route('cmx_quiz_submit'),
		]);

		return $this->helper->render('cmx_quiz_index_body.html', $this->user->lang('CMX_QUIZ'));
	}

	/**
	 * Count correct answers and return score percentage
	 */
	private function count_score_percentage(array $questions)
	{
		$correct = 0;
		$total = count($questions['questions']);

		foreach ($questions['questions'] as $question)
		{
			if (isset($question['submission']) && $question['submission']['correct'])
			{
				$correct++;
			} 
		}

		return ($total > 0) ? number_format(100 * $correct / $total, 2) : 0.0;
	}

    /**
	* CMX Quiz answer (for AJAX)
	* This delivers the question as well as saving an answer
	*
	* @param int $id Quiz Result ID
	* @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/
    public function answer(int $id = 0)
	{
		// We will send a JSON response back
		$json_data = [];
		$error = '';

		/* @var \battye\cmxquiz\quiz\model\quiz_result $quiz_result */
		$quiz_result = $this->manager->get_quiz_result_model($id);

		/* @var \battye\cmxquiz\quiz\model\quiz $quiz */
		$quiz = $this->manager->get_quiz_model($quiz_result->quiz_id);
		$quiz_result->set_bbcode($quiz->bbcode_uid, $quiz->bbcode_bitfield);

		// Check that the user can play this quiz
		$this->verify(self::PLAY_PAGE, $quiz, $quiz_result);

		// Need to use a Symfony request to retrieve JSON data
		$symfony_request = new \phpbb\symfony_request($this->request);
		$request_data = json_decode($symfony_request->getContent(), true);

		// Get the data from the Angular app
		$request_question_number = (int) $request_data['question']; // this is the question that the user CAME from
		$request_answer_supplied = $request_data['answer'];
		$request_direction = $request_data['direction'];

		// If clicking back, don't submit
		$is_submit = ($request_direction == 'next');

		// Has the user finished the quiz?
		$raw_questions = $quiz_result->get_question_data(false);
		$total_questions = count($raw_questions['questions']);

		// This is to ensure the original BBCode formatting isn't lost on the result page
		$original_questions = json_decode($quiz_result->get_raw_question_data(), true);

		for ($i = 0; $i < $total_questions; $i++)
		{
			// If we don't do this, it'll try and show the unparsed BBCode on the results page. This is
			// just to make sure everything looks nice for the user.
			$raw_questions['questions'][$i]['question'] = $original_questions['questions'][$i]['question'];
		}

		$is_finished = ($is_submit && $request_question_number == $total_questions);

		// Which question does the user want next
		// $question_to_load is the question number the user will GO to
		$question_to_load = ($is_submit) ? $request_question_number + 1 : $request_question_number - 1;

		if ($request_question_number <= 1 && strlen($request_answer_supplied) == 0)
		{
			$question_to_load = 1; // If no answer, it's the first and initial load.
		}

		else 
		{
			// Save the answers of the question just answered, evaluate if it is the correct
			// answer or if it is not.
			if ($is_submit)
			{
				$is_correct = false;
				$question_to_provide_submission = $raw_questions['questions'][$request_question_number - 1];
				$raw_question_type = (count($question_to_provide_submission['answers']) > 1) ? self::TYPE_MULTIPLE_CHOICE : self::TYPE_INPUT_ANSWER;

				// Check if input answer is correct
				if ($raw_question_type == self::TYPE_INPUT_ANSWER)
				{
					// Compare answers in lower case
					$is_correct = (strtolower(trim($request_answer_supplied)) == strtolower(trim($question_to_provide_submission['answers'][0]['answer'])));

					$question_to_provide_submission['submission'] = [
						'answer' => $request_answer_supplied,
						'correct' => $is_correct,
					];
				}

				// Check if multiple choice is correct
				else if ($raw_question_type == self::TYPE_MULTIPLE_CHOICE)
				{
					$multiple_choice_answer = '';
					$indexed_request_answer_supplied = (int) $request_answer_supplied - 1;

					if (isset($question_to_provide_submission['answers'][$indexed_request_answer_supplied]))
					{
						$multiple_choice = $question_to_provide_submission['answers'][$indexed_request_answer_supplied];

						$multiple_choice_answer = $multiple_choice['answer'];
						$is_correct = $multiple_choice['correct'];
					}

					$question_to_provide_submission['submission'] = [
						'answer' => $multiple_choice_answer,
						'correct' => $is_correct,
					];
				}

				$raw_questions['questions'][$request_question_number - 1] = $question_to_provide_submission;

				// Maximum time limit has been exceeded, force this quiz to be finalised
				$current_time = time();

				// We determine the maximum end time in advance, so if the current time
				// is exceeding that we know the user has taken too long.
				if ($this->config['cmx_quiz_allow_time_limits'] && $current_time > $quiz_result->end_time)
				{
					$error = self::ERROR_QUIZ_TIME_LIMIT_EXCEEDED;
					$is_finished = true;
				}

				else if ($is_finished)
				{
					$error = self::ERROR_QUIZ_COMPLETED;
					$quiz_result->end_time = $current_time;
				}

				// Refresh the quiz result with the newest data
				$quiz_result->question_data = $raw_questions;
				$quiz_result->score_percentage = $this->count_score_percentage($raw_questions);
				$quiz_result = $this->manager->submit_quiz_result($quiz_result);
			}
		}

		// Quiz is not finished yet, load a question
		if (!$is_finished) 
		{
			// Prepare to send back the next question, or if it's the end of the quiz, the final results.
			$questions = $quiz_result->get_question_data_for_display();

			// We need to determine where the user is currently at, question-wise
			// Because we don't want people skipping forward more than they are allowed to
			$maximum_allowed_question = $i = 1;

			foreach ($questions['questions'] as $question)
			{
				// Does the question already have a submission from the user?
				if (array_key_exists('submission', $question))
				{
					$maximum_allowed_question = $i;
				}

				else 
				{
					// No, the user hasn't answered this question yet
					$maximum_allowed_question++;
					break;
				}

				$i++;
			}

			if ($question_to_load <= $maximum_allowed_question)
			{
				// Send back the requested question
				$deliver_question_data = $questions['questions'][$question_to_load - 1];
				$deliver_answer_data = [];

				// Is there only one answer? If so then it's an input answer type, not multiple choice
				$question_type = (count($deliver_question_data['answers']) > 1) ? self::TYPE_MULTIPLE_CHOICE : self::TYPE_INPUT_ANSWER;

				// Multiple choice
				if ($question_type == self::TYPE_MULTIPLE_CHOICE)
				{
					$formatted_answers = array_column($deliver_question_data['answers'], 'answer');

					foreach ($formatted_answers as $formatted_answer)
					{
						// If the user has already picked a multiple choice answer, we can check to see if it matches the current one in the loop
						// and if so, we can pre-select it on the page.
						$formatted_answer_selected = (isset($deliver_question_data['submission']['answer']) && $deliver_question_data['submission']['answer'] == $formatted_answer);

						$deliver_answer_data[] = [
							'answer' => $formatted_answer,
							'selected' => $formatted_answer_selected,
						];
					}
				}

				// Input answer
				else
				{
					$deliver_answer_data[] = [
						'answer' => (isset($deliver_question_data['submission']['answer'])) ? $deliver_question_data['submission']['answer'] : '',
					];
				}

				// Combine everything we know into a JSON response
				$json_data = [
					'timer' => $quiz->maximum_time_limit_minutes,
					'total' => count($questions['questions']),
					'question' => [
						'id' => $question_to_load,
						'question' => $deliver_question_data['question'],
						'answers' => $deliver_answer_data,
					],
				];
			}

			else
			{
				// Error message
				$error = self::ERROR_QUIZ_CANNOT_ACCESS_QUESTION;
			}
		}

		// Quiz is completely finished
		else 
		{
			// Check usergroup rewards and if applicable, move the user
			if ($quiz->pass_mark_group_id != null && $quiz_result->is_passed())
			{
				// phpBB function to move the user
				if (!function_exists('group_user_add'))
				{
					include($this->root_path . 'includes/functions_user.' . $this->php_ext);
				}

				\group_user_add($quiz->pass_mark_group_id, [$this->user->data['user_id']], false);
			}

			// Send back results
			$json_data = [
				'redirect' => $this->helper->route('cmx_quiz_results', ['id' => $quiz->quiz_id, 'result' => $quiz_result->quiz_result_id]),
			];	
		}

		// Empty if there is no message to report
		$json_data['error'] = $error;

		// Send a json response back to the submit page
		$json_response = new \phpbb\json_response;
		return $json_response->send($json_data);
	}

    /**
	* CMX Quiz results
	* If $result is supplied just show that result (for after a quiz is finished)
	* Otherwise show all results
	*
	* @param int $id Quiz ID
	* @param int $result Quiz Result ID
	* @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/
    public function results(int $id = 0, int $result = 0)
	{
		/* @var \battye\cmxquiz\quiz\model\quiz $quiz */
		$quiz = $this->manager->get_quiz_model($id);

		if (!$result)
		{
			$this->verify(self::RESULTS_PAGE, $quiz);
		}

		// Quiz moderators can always see correct answers. Otherwise the option must be enabled.
		$show_correct_answers = $this->manager->is_user_quiz_moderator($this->user->data['user_id']) || $this->config['cmx_quiz_show_correct_answers'];

		// $quiz->get_quiz_results() will contain all the results; if we are just looking for a specific
		// one then we need to filter the list.
		$quiz_results = [];
		$temp_quiz_results = $quiz->get_quiz_results();

		// Look for a specific result id ($result) if necessary
		foreach ($temp_quiz_results as $quiz_result)
		{
			if (!$this->manager->is_user_quiz_moderator($this->user->data['user_id']))
			{
				// For regular users, only show their results here. If it's a quiz moderator then
				// it's okay to show all user results.
				if ($quiz_result->user_id != $this->user->data['user_id'])
				{
					continue;
				}
			}

			$display_questions = $quiz_result->get_question_data_for_display();

			// Filter in only the correct answers
			foreach ($display_questions['questions'] as &$question)
			{
				$correct_answers = [];

				foreach ($question['answers'] as $answer)
				{
					if ($answer['correct'])
					{
						$correct_answers[] = $answer['answer'];
					}
				}

				// Override as we don't need the multiple choices here
				$question['answers'] = $correct_answers;
			}

			$formatted_quiz_result = [
				'play_time' => $this->user->format_date($quiz_result->end_time),
				'time_taken' => ceil(($quiz_result->end_time - $quiz_result->start_time) / 60),
				'score_percentage' => $quiz_result->score_percentage,
				'display_questions' => $display_questions,
				'played_by' => $this->manager->get_formatted_username($quiz_result->user_id),
			];

			if ($result > 0)
			{
				$this->verify(self::RESULTS_PAGE, $quiz, $quiz_result);

				$quiz_results = [$formatted_quiz_result];
				break;
			}

			else 
			{
				$quiz_results[] = $formatted_quiz_result;
			}
		}

		$this->template->assign_vars([
			'CMX_QUIZ_NAME' => $quiz->quiz_name,
			'CMX_QUIZ_MINIMUM_PASS_MARK' => ($quiz->minimum_pass_mark != null) ? $quiz->minimum_pass_mark : false,
			'CMX_QUIZ_RESULTS' => $quiz_results,
			'CMX_QUIZ_SHOW_CORRECT_ANSWERS' => $show_correct_answers,
		]);

		return $this->helper->render('cmx_quiz_play_results_body.html', $quiz->quiz_name);
	}

    /**
	* CMX Quiz play
	*
	* @param int $id Quiz ID
	* @param string $questions If filled, then this is the screen to actually play. Otherwise, the splash screen.
	* @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/
    public function play(int $id = 0, string $questions = '')
	{
		/* @var \battye\cmxquiz\quiz\model\quiz $quiz */
		$quiz = $this->manager->get_quiz_model($id);

		// Check that the user can play this quiz
		$this->verify(self::PLAY_PAGE, $quiz);

		// There are two screens for playing, firstly a splash screen which will show an edit
		// button if is_user_quiz_moderator checks out and also some information about the quiz
		// like the number of questions and the time limit (if there is one).
		if (!$questions)
		{
			$display_questions = $quiz->get_question_data_for_display();

			$this->template->assign_vars([
				'CMX_QUIZ_NAME' => $quiz->quiz_name,
				'CMX_QUIZ_QUESTION_COUNT' => $quiz->get_question_count(),
				'CMX_QUIZ_TIME_LIMIT' => ($this->config['cmx_quiz_allow_time_limits'] && $quiz->maximum_time_limit_minutes > 0) ? $quiz->maximum_time_limit_minutes : false,
				'CMX_QUIZ_PASS_MARK' => ($quiz->minimum_pass_mark != null) ? (int) $quiz->minimum_pass_mark : false,
				'CMX_QUIZ_MODERATOR' => $this->manager->is_user_quiz_moderator($this->user->data['user_id']),

				'U_EDIT' => $this->helper->route('cmx_quiz_submit', ['id' => $id]),
				'U_RESULTS' => $this->helper->route('cmx_quiz_results', ['id' => $id]),
				'U_START' => $this->helper->route('cmx_quiz_play', ['id' => $id, 'questions' => 'questions']),
			]);

			return $this->helper->render('cmx_quiz_play_start_body.html', $quiz->quiz_name);
		}

		// Secondly, an interactive play screen where the user can actually play the quiz
		else
		{
			if ($questions)
			{			
				/* @var \battye\cmxquiz\quiz\model\quiz_result $quiz_result */
				$quiz_result = $this->manager->get_quiz_result_model();
				$quiz_result->quiz_id = (int) $id;
				$quiz_result->user_id = $this->user->data['user_id'];
				$quiz_result->question_data = $quiz->question_data;
				$quiz_result->end_time = ($quiz->maximum_time_limit_minutes) ? ($quiz_result->start_time + ($quiz->maximum_time_limit_minutes * 60)) : null;

				// Firstly, create a quiz result record
				$quiz_result_id = $this->manager->submit_quiz_result($quiz_result)->quiz_result_id;

				$this->template->assign_vars([
					'U_CQA_ENDPOINT' => $this->helper->route('cmx_quiz_answer', ['id' => $quiz_result_id]),
				]);

				return $this->helper->render('cmx_quiz_play_body.html', $quiz->quiz_name);
			}
		}
	}

    /**
	* CMX Quiz submit (and edit)
	*
	* @param int $id - if this is supplied, then it's to edit the quiz
	* @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/
    public function submit($id = 0)
	{
		// Set up variables for the user to see on the submit page
		$move_to_usergroup = (int) $this->config['cmx_quiz_allow_move_to_usergroup'];

		// Are we editing an existing quiz?
		$is_edit = ($id > 0);

		// Edit existing quiz
		if ($is_edit)
		{
			/* @var \battye\cmxquiz\quiz\model\quiz $quiz */
			$quiz = $this->manager->get_quiz_model($id);

			$this->verify(self::EDIT_PAGE, $quiz);

			// Check if it's an edit submission (post). If the quiz is marked to be deleted, this will be 
			// handled in here as well.
			if ($this->request->is_ajax())
			{
				/* @var \battye\cmxquiz\quiz\model\quiz $edited_quiz */
				$edited_quiz = $this->interpret_quiz_submission();
				$edited_quiz->quiz_id = $quiz->quiz_id;

				return $this->return_quiz_submission($edited_quiz);
			}

			// We want to show the questions on the edit page without using javascript
			$counter = array_keys(
				array_fill(1, $quiz->get_question_count(), null)
			);

			$questions = $quiz->get_question_data();
			$page_title = $this->user->lang('CMX_QUIZ_EDIT_QUIZ');

			// Some custom template vars for editing only
			$this->template->assign_vars([
				'CMX_QUIZ_NAME'					=> $quiz->quiz_name,
				'CMX_QUIZ_DESCRIPTION'			=> $quiz->quiz_description,

				'CMX_QUIZ_TAG_DATA'				=> array_column($quiz->get_tags_data($this->manager->get_quiz_tags()), 'quiz_tag_id'),
				'CMX_QUIZ_TIME_LIMIT'			=> $quiz->maximum_time_limit_minutes,
				'CMX_QUIZ_MAXIMUM_ATTEMPTS'		=> $quiz->maximum_attempts,
				'CMX_QUIZ_MINIMUM_PASS_MARK'	=> $quiz->minimum_pass_mark,
				'CMX_QUIZ_PASS_MARK_GROUP_ID'	=> $quiz->pass_mark_group_id,

				'CMX_QUIZ_QUESTIONS'			=> $questions,
			]);
		}

		// Submit new quiz
		else
		{
			$this->verify(self::SUBMIT_PAGE);

			// Check if it's a submission (post)
			if ($this->request->is_ajax())
			{
				/* @var \battye\cmxquiz\quiz\model\quiz $quiz */
				$quiz = $this->interpret_quiz_submission();
				return $this->return_quiz_submission($quiz);
			}

			// A counter so we know how to label the question IDs in the template
			$counter = array_keys(
				array_fill(1, (int) $this->config['cmx_quiz_minimum_questions'], null)
			);

			$page_title = $this->user->lang('CMX_QUIZ_NEW');
		}

		$this->language->add_lang('posting');
		$this->template->assign_vars([
			'CMX_QUIZ_IS_EDIT'					=> $is_edit,

			'CMX_QUIZ_MODERATOR'				=> $this->manager->is_user_quiz_moderator($this->user->data['user_id']),
			'CMX_QUIZ_TAGS'						=> $this->manager->get_quiz_tags(),
			'CMX_QUIZ_USERGROUPS'				=> ($move_to_usergroup) ? $this->manager->get_groups() : false,

			'CMX_QUIZ_MINIMUM_QUESTIONS'		=> (int) $this->config['cmx_quiz_minimum_questions'],
			'CMX_QUIZ_MAXIMUM_QUESTIONS'		=> (int) $this->config['cmx_quiz_maximum_questions'],
			'CMX_QUIZ_ALLOW_TIME_LIMITS'		=> (int) $this->config['cmx_quiz_allow_time_limits'],
			'CMX_QUIZ_ALLOW_MULTIPLE_ATTEMPTS'	=> (int) $this->config['cmx_quiz_allow_multiple_attempts'],
			'CMX_QUIZ_ALLOW_MOVE_TO_USERGROUP'	=> $move_to_usergroup,

			'CMX_QUIZ_COUNTER'					=> $counter,

			'S_BBCODE_ALLOWED'					=> $this->config['allow_bbcode'],
			'S_SMILIES_ALLOWED'					=> $this->config['allow_smilies'],
			'S_BBCODE_IMG'						=> $this->config['allow_bbcode'],
			'S_BBCODE_FLASH'					=> false,
			'S_LINKS_ALLOWED'					=> $this->config['allow_urls'],
		]);

		return $this->helper->render('cmx_quiz_submit_body.html', $page_title);
	}

	/**
	 * Interpret data from the AJAX submission (submit and edit)
	 */
	private function interpret_quiz_submission()
	{
		// Need to use a Symfony request to retrieve JSON data
		$symfony_request = new \phpbb\symfony_request($this->request);
		$quiz_data = json_decode($symfony_request->getContent(), true);
		
		/* @var \battye\cmxquiz\quiz\model\quiz $quiz */
		$quiz = $this->manager->get_quiz_model();

		$quiz->user_id = $this->user->data['user_id'];
		$quiz->quiz_name = $quiz_data['quiz_name'];
		$quiz->question_data = $quiz_data['question_data'];
		$quiz->maximum_time_limit_minutes = (!empty($quiz_data['maximum_time_limit_minutes'])) ? $quiz_data['maximum_time_limit_minutes'] : 0;
		$quiz->minimum_pass_mark = (!empty($quiz_data['minimum_pass_mark'])) ? $quiz_data['minimum_pass_mark'] : 0;
		$quiz->maximum_attempts = (!empty($quiz_data['maximum_attempts'])) ? $quiz_data['maximum_attempts'] : 0;
		$quiz->pass_mark_group_id = (!empty($quiz_data['pass_mark_group_id'])) ? $quiz_data['pass_mark_group_id'] : 0;
		$quiz->quiz_description = $quiz_data['quiz_description'];
		$quiz->tags_data = $quiz_data['tags_data'];

		if ($quiz_data['delete_quiz'])
		{
			// This quiz is to be deleted
			$quiz->set_mark_for_deletion(true);
		}

		return $quiz;
	}

	/**
	 * Return JSON for the quiz submission (submit and edit)
	 */
	private function return_quiz_submission(\battye\cmxquiz\quiz\model\quiz $quiz)
	{
		$is_quiz_valid = strlen($quiz->quiz_name) > 0 && $quiz->validate_question_data($this->config);
		$is_quiz_valid_message = 'CMX_QUIZ_ALERT_INVALID_QUESTION_DATA';

		if ($is_quiz_valid)
		{
			// Do we need to delete?
			if ($quiz->get_mark_for_deletion())
			{
				if ($this->manager->delete_quiz($quiz))
				{
					$is_quiz_valid_message = 'CMX_QUIZ_DELETED_SUCCESSFULLY';
				}
			}

			// Submit or edit quiz in the database
			else 
			{
				if ($this->manager->submit_quiz($quiz))
				{
					$is_quiz_valid_message = 'CMX_QUIZ_SUBMITTED_SUCCESSFULLY';
				}
			}
		}

		
		$json_response = new \phpbb\json_response;

		// Send a json response back to the submit page
		return $json_response->send([
			'IS_VALID'		=> $is_quiz_valid,
			'MESSAGE_TITLE'	=> $this->language->lang('CMX_QUIZ_INFORMATION'),
			'MESSAGE_TEXT'	=> $this->language->lang($is_quiz_valid_message),
			'REFRESH_DATA'	=> [
				'url'		=> $this->helper->route('cmx_quiz_index'),
				'time'		=> 3,
			],
		]);
	}

	/**
	 * Check the user is logged in
	 */
	private function verify(int $page, \battye\cmxquiz\quiz\model\quiz $quiz = null, \battye\cmxquiz\quiz\model\quiz_result $quiz_result = null)
	{
		// User has to be logged in
		if ($this->user->data['user_id'] == ANONYMOUS)
		{
			// Could use login_box?
			throw new \phpbb\exception\http_exception(401, 'CMX_QUIZ_NOT_LOGGED_IN');
		}

		// Check minimum post count requirements
		if ($this->config['cmx_quiz_minimum_posts'] > 0)
		{
			if ((int) $this->user->data['user_posts'] < (int) $this->config['cmx_quiz_minimum_posts'])
			{
				throw new \phpbb\exception\http_exception(401, $this->language->lang('CMX_QUIZ_NOT_ENOUGH_POSTS', $this->config['cmx_quiz_minimum_posts']));
			}
		}

		// Check the user is a quiz moderator if they are submitting and regular users are not allowed to submit
		if ($page === self::SUBMIT_PAGE && $this->config['cmx_quiz_allow_user_submissions'] == 0 && $this->config['cmx_quiz_moderate_group_id'] > 0)
		{
			// Non credentialled users cannot submit, so we have to make sure the user is part of the quiz moderator group
			if (!$this->manager->is_user_quiz_moderator($this->user->data['user_id']))
			{
				throw new \phpbb\exception\http_exception(401, 'CMX_QUIZ_NOT_CREDENTIALLED');
			}
		}

		if ($page == self::EDIT_PAGE)
		{
			$quiz_author = $quiz->user_id;

			// If they are a quiz moderator, they can edit
			$is_quiz_moderator = $this->manager->is_user_quiz_moderator($this->user->data['user_id']);

			// Otherwise, if they are the user that submitted the quiz they can edit it too
			if (!$is_quiz_moderator && !($this->config['cmx_quiz_allow_user_submissions'] && $quiz_author == $this->user->data['user_id']))
			{
				throw new \phpbb\exception\http_exception(401, 'CMX_QUIZ_NOT_CREDENTIALLED');
			}
		}

		if ($page == self::PLAY_PAGE)
		{
			// Multiple attempts
			if ($this->config['cmx_quiz_allow_multiple_attempts'])
			{
				$quiz_maximum_attempts = (int) $quiz->maximum_attempts;
				$user_plays = 0;
				
				/* @var \battye\cmxquiz\quiz\model\quiz_result $individual_quiz_result */
				foreach ($quiz->get_quiz_results() as $individual_quiz_result)
				{
					if ($individual_quiz_result->user_id == $this->user->data['user_id'])
					{
						$user_plays++;
					}
				}

				if ($quiz_maximum_attempts > 0 && $user_plays >= $quiz_maximum_attempts)
				{
					throw new \phpbb\exception\http_exception(401, 'CMX_QUIZ_EXCEEDED_MAXIMUM_PLAYS');
				}
			}
		}

		if ($page == self::RESULTS_PAGE)
		{
			// Make sure the user isn't trying to access someone else's quiz results
			if ($quiz_result != null)
			{
				if ($quiz_result->user_id != $this->user->data['user_id'])
				{
					throw new \phpbb\exception\http_exception(401, 'CMX_QUIZ_NOT_AUTHORISED');
				}
			}
		}
	}
}