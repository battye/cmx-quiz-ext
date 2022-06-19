<?php
/**
*
* @package CMX Quiz
* @copyright (c) 2022 battye (CricketMX.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace battye\cmxquiz\quiz\model;

/**
 * CMX Quiz - Model class to handle everything related to a quiz
 */
class quiz extends \battye\cmxquiz\quiz\model\quiz_parent
{
    /* @var \phpbb\db\driver\driver_interface */
    protected $db;

    /* @var array $tables */
    protected $tables;

    /* @var int $quiz_id */
    public $quiz_id = null;

    /* @var string $quiz_name */
    public $quiz_name = null;

    /* @var string $quiz_description */
    public $quiz_description = null;

    /* @var int $user_id */
    public $user_id = null;

    /* @var string $submission_time */    
    public $submission_time = null;

    /* @var string $tags_data */  
    public $tags_data = null;

    /* @var string $question_data */  
    public $question_data = null;

    /* @var string $bbcode_uid */  
    public $bbcode_uid = null;

    /* @var string $bbcode_bitfield */  
    public $bbcode_bitfield = null;

    /* @var int $minimum_pass_mark */  
    public $minimum_pass_mark = null;

    /* @var int $pass_mark_group_id */  
    public $pass_mark_group_id = null;

    /* @var int $maximum_time_limit_minutes */      
    public $maximum_time_limit_minutes = null;

    /* @var int $maximum_attempts */      
    public $maximum_attempts = null;

    /* @var int $total_plays */
    private $total_plays = 0;

    /* @var array $quiz_results */
    private $quiz_results = [];

    /* @var boolean $mark_for_deletion */
    private $mark_for_deletion = false;

    /**
	* Constructor
	*
	* @param \phpbb\db\driver\driver_interface $db
    * @param array                             $tables
	* @param int                               $quiz_id
	*/
	public function __construct(\phpbb\db\driver\driver_interface $db, array $tables, int $quiz_id = 0)
	{
        $this->db = $db;
        $this->tables = $tables;

        if ($quiz_id > 0)
        {
            // Existing quiz? Populate the data from the database
            $this->populate($quiz_id);
        }

        else
        {
            // New quiz? Use the current time
            $this->submission_time = time();
        }
	}

    /**
     * Populate the class fields in this object
     */
    public function populate(int $quiz_id)
    {
        $this->quiz_id = $quiz_id;

        // Get the quiz
        $sql_array = [
            'SELECT' => 'q.*, COUNT(r.quiz_id) as total_plays',
        
            'FROM' => [
                $this->tables['quiz'] => 'q',
            ],
        
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [$this->tables['quiz_result'] => 'r'],
                    'ON'	=> 'q.quiz_id = r.quiz_id',
                ],
            ],
        
            'WHERE' => 'q.quiz_id = ' . $quiz_id,
        ];
        
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);

        foreach ($row as $key => $value)
        {
            $this->$key = $value;
        }

        $this->db->sql_freeresult($result);

        // Get the quiz results, with the most recent first
        $sql = 'SELECT quiz_result_id
                FROM ' . $this->tables['quiz_result'] . '
                WHERE quiz_id = ' . (int) $quiz_id . '
                ORDER BY quiz_result_id DESC';

        $result = $this->db->sql_query($sql);
        
        while ($row = $this->db->sql_fetchrow($result))
        {
            $this->quiz_results[] = new \battye\cmxquiz\quiz\model\quiz_result($this->db, $this->tables, (int) $row['quiz_result_id']);
        }

        $this->db->sql_freeresult($result);
    }

    /**
     * Prepare data for database insert
     */
    public function prepare(): array
    {
        /*$uid = $bitfield = $options = '';
        $allow_bbcode = $allow_urls = $allow_smilies = true;
        generate_text_for_storage($question_data, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);*/

        $question_data = $this->get_raw_question_data();

        return [
            'quiz_name' => $this->quiz_name,
            'quiz_description' => $this->quiz_description,
            'user_id' => $this->user_id,
            'submission_time' => $this->submission_time,
            'tags_data' => (is_array($this->tags_data)) ? json_encode($this->tags_data) : $this->tags_data,
            'question_data' => $question_data,
            'bbcode_uid' => $this->bbcode_uid,
            'bbcode_bitfield' => $this->bbcode_bitfield,
            'minimum_pass_mark' => $this->minimum_pass_mark,
            'pass_mark_group_id' => $this->pass_mark_group_id,
            'maximum_time_limit_minutes' => $this->maximum_time_limit_minutes,
            'maximum_attempts' => $this->maximum_attempts,
        ];
    }

    /**
     * Get quiz results
     */
    public function get_quiz_results()
    {
        return $this->quiz_results;
    }

    /**
     * Only have getters for attributes which aren't in the quiz table or need additional manipulation
     */
    public function get_total_plays()
    {
        return $this->total_plays;
    }

    /**
     * Show the question count
     */
    public function get_question_count()
    {
        $count = 0;

        $questions = $this->get_question_data();

        if (array_key_exists('questions', $questions))
        {
            $count = count($questions['questions']);
        }

        return $count;
    }

    /**
     * Quick access for the next function
     */
    /*public function get_question_data_for_display()
    {
        return $this->get_question_data(false);
    }*/

    /** 
     * Return question data as an array
     * If $edit is false, we generate BBCode for display
     */
    /*public function get_question_data(bool $edit = true)
    {
        $questions = [];

        if ($this->question_data != null)
        {
            if (!is_array($this->question_data))
            {
                $questions = json_decode($this->question_data, true);
            }

            else 
            {
                $questions = $this->question_data;
            }
        }

        // Now we do some BBCode parsing
        if (array_key_exists('questions', $questions))
        {
            foreach ($questions['questions'] as &$question)
            {
                if ($edit)
                {
                    // BBCode parsing for editing
                    $text = generate_text_for_edit($question['question'], $this->bbcode_uid, OPTION_FLAG_BBCODE);
                    $question['question'] = $text['text'];
                }

                else 
                {
                    // BBCode parsing for displaying
                    $question_parsed = generate_text_for_display($question['question'], $this->bbcode_uid, $this->bbcode_bitfield, OPTION_FLAG_BBCODE);
                    $question['question'] = $question_parsed;
                }

                foreach ($question['answers'] as &$answer)
                {
                    if ($edit)
                    {
                        // BBCode parsing for editing
                        $text = generate_text_for_edit($answer['answer'], $this->bbcode_uid, OPTION_FLAG_BBCODE);
                        $answer['answer'] = $text['text'];
                    }
    
                    else 
                    {
                        // BBCode parsing for displaying
                        $answer_parsed = generate_text_for_display($answer['answer'], $this->bbcode_uid, $this->bbcode_bitfield, OPTION_FLAG_BBCODE);
                        $answer['answer'] = $answer_parsed;
                    }   
                }
            }
        }

        return $questions;
    }*/

    /**
     * Show the tag name as a badge on the quiz index
     */
    public function get_tags_data($stored_tags)
    {
        $data = [];
        $tags = json_decode($this->tags_data, true);

        if (isset($tags) && count($tags) > 0)
        {
            foreach ($tags as $tag_id)
            {
                if (array_key_exists($tag_id, $stored_tags))
                {
                    $data[] = [
                        'quiz_tag_id' => $tag_id,
                        'tag_name' => $stored_tags[$tag_id],
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Make sure the question data is suitable for playing and create text with BBCode suitable for storing
     */
    public function validate_question_data($config)
    {
        $uid = $bitfield = $options = '';
        $allow_bbcode = $allow_urls = $allow_smilies = true;

        $this->bbcode_uid = $uid;
        $this->bbcode_bitfield = $bitfield;

        // If it's already in array format we don't need to parse it
        $question_data = (is_array($this->question_data)) ? $this->question_data : json_decode($this->question_data, true);
        $valid = true;

        if ($question_data != null && isset($question_data['questions']))
        {
            // Check minimum and maximum number of questions
            $question_count = count($question_data['questions']);

            if ($question_count < (int) $config['cmx_quiz_minimum_questions'])
            {
                // Validation error: not enough questions
                $valid = 'CMX_QUIZ_ALERT_TOO_FEW_QUESTIONS';
            }

            else if ($question_count > (int) $config['cmx_quiz_maximum_questions'])
            {
                // Validation error: too many questions
                $valid = 'CMX_QUIZ_ALERT_TOO_MANY_QUESTIONS';
            }

            foreach ($question_data['questions'] as &$question)
            {
                // Validation error: empty question
                if (!isset($question['question']) || empty($question['question']))
                {
                    $valid = 'CMX_QUIZ_ALERT_EMPTY_QUESTION';
                }

                // Supply BBCode to the question
                generate_text_for_storage($question['question'], $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

                $number_correct = 0;
                foreach ($question['answers'] as &$answer)
                {
                    // Validation error: empty answer
                    if (!isset($answer['answer']) || empty($answer['answer']))
                    {
                        $valid = 'CMX_QUIZ_ALERT_MISSING_ANSWER';
                    }

                    // Supply BBCode to the answer
                    generate_text_for_storage($answer['answer'], $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);

                    // See if this is a correct answer (eg. correct == true)
                    if (isset($answer['correct']) && $answer['correct'])
                    {
                        $number_correct++;
                    }
                }

                // Validation error: this question has no answer marked as correct     
                if ($number_correct == 0)
                {
                    $valid = 'CMX_QUIZ_ALERT_MISSING_CORRECT';
                }           
            }
        }

        else
        {
            // No question data
            $valid = 'CMX_QUIZ_ALERT_TOO_FEW_QUESTIONS';
        }

        $this->bbcode_uid = $uid;
        $this->bbcode_bitfield = $bitfield;

        // Re-store pre-parsed text
        $this->question_data = $question_data;

        return $valid;
    }

    /** 
     * Mark for deletion
     */
    public function set_mark_for_deletion($mark)
    {
        $this->mark_for_deletion = $mark;
    }

    /** 
     * See if this is to be deleted
     */
    public function get_mark_for_deletion()
    {
        return $this->mark_for_deletion;
    }
}