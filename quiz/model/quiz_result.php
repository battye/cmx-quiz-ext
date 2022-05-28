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
class quiz_result extends \battye\cmxquiz\quiz\model\quiz_parent
{
    /* @var \phpbb\db\driver\driver_interface */
    protected $db;

    /* @var array $tables */
    protected $tables;

    /* @var int $quiz_id */
    public $quiz_result_id = null;

    /* @var int $quiz_id */
    public $quiz_id = null;

    /* @var int $user_id */
    public $user_id = null;

    /* @var string $question_data */
    public $question_data = null;

    /* @var int $start_time */    
    public $start_time = null;

    /* @var int $end_time */  
    public $end_time = null;

    /* @var float $score_percentage */  
    public $score_percentage = null;

    /* @var string $bbcode_uid */  
    protected $bbcode_uid = null;

    /* @var string $bbcode_bitfield */  
    protected $bbcode_bitfield = null;

    /**
	* Constructor
	*
	* @param phpbb\db\driver\driver_interface   $db
    * @param array                              $tables
	* @param int                                $quiz_result_id
	*/
	public function __construct(\phpbb\db\driver\driver_interface $db, array $tables, int $quiz_result_id = 0)
	{
        $this->db = $db;
        $this->tables = $tables;

        if ($quiz_result_id > 0)
        {
            // Existing quiz result? Populate the data from the database
            $this->populate($quiz_result_id);
        }

        else 
        {
            $this->start_time = time();
        }
	}

    /**
     * Populate the class fields in this object
     */
    public function populate(int $quiz_result_id)
    {
        $this->quiz_result_id = $quiz_result_id;

        // Get the quiz result
        $sql = 'SELECT quiz_id, user_id, question_data, start_time, end_time, score_percentage
                FROM ' . $this->tables['quiz_result'] . '
                WHERE quiz_result_id = ' . (int) $quiz_result_id;

        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);

        foreach ($row as $key => $value)
        {
            $this->$key = $value;
        }

        $this->db->sql_freeresult($result);
    }

    /**
     * Prepare data for database insert
     */
    public function prepare(): array
    {
        $question_data = $this->get_raw_question_data();

        return [
            'quiz_id' => $this->quiz_id,
            'user_id' => $this->user_id,
            'question_data' => $question_data,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'score_percentage' => $this->score_percentage,
        ];
    }

    public function is_passed()
    {
        // Get the quiz
        $sql = 'SELECT minimum_pass_mark
                FROM ' . $this->tables['quiz'] . '
                WHERE quiz_id = ' . (int) $this->quiz_id;

        $result = $this->db->sql_query($sql);

        // This is a percentage
        $pass_mark = $this->db->sql_fetchfield('minimum_pass_mark');

        return ($this->score_percentage >= $pass_mark);
    }

    /**
     * Set BBCode fields
     */
    public function set_bbcode(string $uid, string $bitfield)
    {
        $this->bbcode_uid = $uid;
        $this->bbcode_bitfield = $bitfield;
    }
}