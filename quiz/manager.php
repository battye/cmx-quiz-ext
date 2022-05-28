<?php
/**
*
* @package CMX Quiz
* @copyright (c) 2022 battye (CricketMX.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace battye\cmxquiz\quiz;

/**
 * CMX Quiz - Manager
 */
class manager
{
    // phpBB prebuilt administrators group
    const ADMINISTRATORS_GROUP = 5;

    // phpBB has prebuilt usergroups up until this ID
    const CUSTOM_GROUP_START = 8;

    /* @var \phpbb\db\driver\driver_interface $db */
    protected $db;

	/* @var \phpbb\config\config $config */
	protected $config;

	/* @var \phpbb\user $user */
	protected $user;

    /** @var \phpbb\user_loader $user_loader */
	protected $user_loader;

    /** @var \phpbb\language\language $language */
    protected $language;

    /* @var array $tables */
    protected $tables;

    /* @var array $tags */
    private $tags = null;

	/**
	* Constructor
	*
    * @param \phpbb\db\driver\driver_interface  $db
	* @param \phpbb\config\config		        $config
	* @param \phpbb\user				        $user
    * @param \phpbb\user_loader                 $user_loader
    * @param \phpbb\language\language           $language
    * array                                     $tables
	*/
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, \phpbb\user $user, \phpbb\user_loader $user_loader, \phpbb\language\language $language, array $tables)
	{
        $this->db = $db;
		$this->config = $config;
		$this->user = $user;
        $this->user_loader = $user_loader;
        $this->language = $language;
        $this->tables = $tables;

        $this->get_quiz_tags();
	}

    /**
     * Little factory method for creating a quiz object
     */
    public function get_quiz_model(int $id = 0)
    {
        return new \battye\cmxquiz\quiz\model\quiz($this->db, $this->tables, $id);
    }

    /**
     * Little factory method for creating a quiz result object
     */
    public function get_quiz_result_model(int $id = 0)
    {
        return new \battye\cmxquiz\quiz\model\quiz_result($this->db, $this->tables, $id);
    }

    /**
     * Get single quiz result with played by information
     */
    /*public function get_result(int $quiz_result_id)
    {
        $quiz_result = $this->get_quiz_result_model($quiz_result_id);
        $quiz = $this->get_quiz_model($quiz_result->quiz_id);
        $played_by = $this->user_loader->get_username($quiz_result->user_id, 'full', false, false, true);

        return [
            'quiz' => $quiz,
            'quiz_result' => $quiz_result,
            'played_by' => $played_by,
        ];
    }*/

    /**
     * Get all results for a quiz
     */
    /*public function get_results(int $quiz_id)
    {
        $sql = 'SELECT quiz_result_id
                FROM ' . $this->tables['quiz_result'] . '
                WHERE quiz_id = ' . (int) $quiz_id . '
                ORDER BY quiz_result_id DESC';

        $result = $this->db->sql_query($sql);
        $quiz_results = [];

        while ($row = $this->db->sql_fetchrow($result))
        {
            $quiz_results[] = $this->get_result($row['quiz_result_id']);
        }

        $this->db->sql_freeresult($result);

        return $quiz_results;
    }*/

    /**
     * Get all the quiz tags, store this in memory so we don't keep calling it
     */
    public function get_quiz_tags()
    {
        if ($this->tags == null)
        {
            $sql = 'SELECT *
                    FROM ' . $this->tables['quiz_tag'] . '
                    ORDER BY tag_name ASC';
            
            $result = $this->db->sql_query($sql);
            $data = [];
            
            while ($row = $this->db->sql_fetchrow($result))
            {
                $data[$row['quiz_tag_id']] = $row['tag_name'];
            }

            $this->db->sql_freeresult($result);
            $this->tags = $data;
        }

        return $this->tags;
    }
    
    /**
     * Get total number of quizzes for pagination purposes
     */
    public function get_quiz_count()
	{
		// Get the quizzes per page setting from the config
		$config_quiz_per_page = (int) $this->config['cmx_quiz_per_page'];
		
		// Count the number of quizzes this user has access to (eg. registered users may see more than guests)
        $sql = 'SELECT COUNT(quiz_id) as quiz_count
                FROM ' . $this->tables['quiz'];

        $result = $this->db->sql_query($sql);
        $total_quizzes = (int) $this->db->sql_fetchfield('quiz_count');
		$last_page = ($total_quizzes == 0) ? 1 : (int) ceil($total_quizzes / $config_quiz_per_page);

        $this->db->sql_freeresult($result);

		return [
			'total_quizzes' => $total_quizzes,
			'last_page' => $last_page,
		];
	}

    /**
     * Get the list of quizzes, also get the number of times a user has played it so
     * we know which icon to display. Use start number for pagination.
     */
    public function get_quiz_list(int $start)
    {
        $data = [];

        $limit = (int) $this->config['cmx_quiz_per_page'];

        $sql_array = [
            'SELECT' => 'q.quiz_id, COUNT(r.quiz_id) as user_plays',
        
            'FROM' => [
                $this->tables['quiz'] => 'q',
            ],
        
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [$this->tables['quiz_result'] => 'r'],
                    'ON'	=> 'q.quiz_id = r.quiz_id AND r.user_id = ' . (int) $this->user->data['user_id'],
                ],
            ],
        
            'GROUP_BY' => 'q.quiz_id',
            'ORDER_BY' => 'q.quiz_id DESC',
        ];
        
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query_limit($sql, $limit, $start);

        while ($row = $this->db->sql_fetchrow($result))
        {
            $quiz = $this->get_quiz_model((int) $row['quiz_id']);

            // Use the user loader to get the proper link to the username
            $data[] = [
                'submitter_name' => $this->get_formatted_username($quiz->user_id),
                'quiz' => $quiz,
                'user_plays' => $row['user_plays'],
            ];
        }

        $this->db->sql_freeresult($result);

        return $data;
    }

    /**
     * Get the coloured and linked username
     */
    public function get_formatted_username(int $user_id)
    {
        return $this->user_loader->get_username($user_id, 'full', false, false, true);
    }

    /**
     * Does the supplied user id belong to the quiz moderator group as defined in the ACP?
     */
    public function is_user_quiz_moderator(int $user_id)
    {
        $quiz_moderator_group_id = (int) $this->config['cmx_quiz_moderate_group_id'];

        if ($quiz_moderator_group_id == 0)
        {
            // Default to administrators if nothing is selected
            $quiz_moderator_group_id = self::ADMINISTRATORS_GROUP;
        }

        $sql = 'SELECT COUNT(*) AS membership 
                FROM ' . USER_GROUP_TABLE . '
                WHERE user_id = ' . (int) $user_id . '
                AND group_id = ' . (int) $quiz_moderator_group_id . '
                AND user_pending = 0';

        $result = $this->db->sql_query($sql);
        $count = $this->db->sql_fetchfield('membership');

        $this->db->sql_freeresult($result);

        return ($count > 0);
    }

    /**
     * Get the restricted group list
     */
    public function get_groups()
    {
        return $this->acp_list_groups(true);
    }

    /**
     * Submit a quiz result
     * We know by the presence of a quiz_result_id whether we are creating a new one or editing
     * an existing record (eg. a quiz that is currently in progress)
     */
    public function submit_quiz_result(\battye\cmxquiz\quiz\model\quiz_result $quiz_result)
    {
        $id = $quiz_result->quiz_result_id;

        // Edit an existing one
        if ($id != null && $id > 0)
        {
            $sql = 'UPDATE ' . $this->tables['quiz_result'] . '
                    SET ' . $this->db->sql_build_array('UPDATE', $quiz_result->prepare()) . '
                    WHERE quiz_result_id = ' . (int) $quiz_result->quiz_result_id;

            $this->db->sql_query($sql);
        }

        // Add a new result
        else 
        {
            $sql = 'INSERT INTO ' . $this->tables['quiz_result'] . ' ' .
                    $this->db->sql_build_array('INSERT', $quiz_result->prepare());

            $this->db->sql_query($sql);
            $id = $this->db->sql_nextid();
        }

        return $this->get_quiz_result_model($id);
    }

    /**
     * Submit a quiz
     * We know by the presence of a quiz_id whether it's a new submission or an edit
     */
    public function submit_quiz(\battye\cmxquiz\quiz\model\quiz $quiz)
    {
        $id = $quiz->quiz_id;

        // Edit an existing one
        if ($id != null && $id > 0)
        {
            $sql = 'UPDATE ' . $this->tables['quiz'] . '
                    SET ' . $this->db->sql_build_array('UPDATE', $quiz->prepare()) . '
                    WHERE quiz_id = ' . (int) $id;

            $this->db->sql_query($sql);
        }

        // Insert a new one
        else 
        {
            $sql = 'INSERT INTO ' . $this->tables['quiz'] . ' ' .
                    $this->db->sql_build_array('INSERT', $quiz->prepare());

            $this->db->sql_query($sql);
            $id = $this->db->sql_nextid();
        }

        return $this->get_quiz_model($id);
    }

    /**
     * Delete quiz data
     */
    public function delete_quiz(\battye\cmxquiz\quiz\model\quiz $quiz)
    {
        $success = false;

        if ($quiz->get_mark_for_deletion())
        {
            $quiz_id = $quiz->quiz_id;

            // Delete quiz records
            $this->db->sql_query('DELETE FROM ' . $this->tables['quiz_result'] . ' WHERE quiz_id = ' . (int) $quiz_id);

            // And delete the quiz
            $this->db->sql_query('DELETE FROM ' . $this->tables['quiz'] . ' WHERE quiz_id = ' . (int) $quiz_id);

            $success = true;
        }

        return $success;
    }

    /**
     * Synchronise the tags based on admin selections.
     * Created is an array of strings, updated is a key pair with the id and name, deleted is an array of ids
     */
    public function acp_tag_manager(array $created, array $updated, array $deleted)
    {
        // Delete first
        if (count($deleted) > 0)
        {
            $this->db->sql_query('DELETE FROM ' . $this->tables['quiz_tag'] . ' WHERE ' . $this->db->sql_in_set('quiz_tag_id', $deleted));
        }

        // Created next
        if (count($created) > 0)
        {
            $sql_ary = [];

            foreach ($created as $new)
            {
                $sql_ary[] = [
                    'tag_name' => $new,
                ];
            }
            
            $this->db->sql_multi_insert($this->tables['quiz_tag'], $sql_ary);
        }

        // And updated last
        if (count($updated) > 0)
        {
            foreach ($updated as $quiz_tag_id => $change)
            {
                $sql_ary = [
                    'tag_name' => $change,
                ];
                
                $sql = 'UPDATE ' . $this->tables['quiz_tag'] . '
                        SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
                        WHERE quiz_tag_id = ' . (int) $quiz_tag_id;

                $this->db->sql_query($sql);
            }
        }
    }

    /**
     * List the groups so the admin can pick a quiz moderator group
     * If $filter is true, then we restrict the list only to newly created custom groups
     * instead of the preincluded ones like Administrators, etc. This is so a quiz moderator
     * can't give access to the special groups.
     */
    public function acp_list_groups($filter = false)
    {
        $where = ($filter) ? 'WHERE group_id >= ' . self::CUSTOM_GROUP_START : '';
        $sql = 'SELECT group_id, group_name, group_type
                FROM ' . GROUPS_TABLE . " 
                $where
                ORDER BY group_name ASC";

 		$result = $this->db->sql_query($sql);

        while ($row = $this->db->sql_fetchrow($result))
        {			
            $data[] = [
                'group_id' => $row['group_id'],
                'group_name' => ($row['group_type'] == GROUP_SPECIAL) ? $this->language->lang('G_' . $row['group_name']) : $row['group_name'],
            ];
        }

        $this->db->sql_freeresult($result);

        return $data;
    }
}