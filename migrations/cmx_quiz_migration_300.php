<?php
namespace battye\cmxquiz\migrations;

class cmx_quiz_migration_300 extends \phpbb\db\migration\migration
{
    const DEFAULT_OFF = false;
    const DEFAULT_ON = true;
    const DEFAULT_MINIMUM_POSTS = 0;
    const DEFAULT_MINIMUM_QUESTIONS = 1;
    const DEFAULT_MAXIMUM_QUESTIONS = 5;
    const DEFAULT_QUIZ_PER_PAGE = 20;

    /**
     * So we know if it's installed
     */
	public function effectively_installed()
	{
		return isset($this->config['cmx_quiz_enabled']);
	}

    /**
     * First migration file, no dependencies except phpBB 3.3.7
     */
    static public function depends_on()
    {
        return [
            '\phpbb\db\migration\data\v33x\v337',
        ];
    }

    /**
     * Update config table and add to ACP
     */
    public function update_data()
	{                
		return [
            // Add a parent module to the Extensions tab
            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_CMX_QUIZ'
            ]],

            // Add quiz_module to the parent module
            ['module.add', [
                'acp',
                'ACP_CMX_QUIZ',
                [
                    'module_basename' => '\battye\cmxquiz\acp\quiz_module',
                    'modes' => ['settings'],
                ],
            ]],
            
            // Config settings
			['config.add', ['cmx_quiz_enabled', self::DEFAULT_ON]],
            ['config.add', ['cmx_quiz_allow_time_limits', self::DEFAULT_OFF]],
            ['config.add', ['cmx_quiz_allow_multiple_attempts', self::DEFAULT_OFF]],
            ['config.add', ['cmx_quiz_allow_move_to_usergroup', self::DEFAULT_OFF]],
            ['config.add', ['cmx_quiz_allow_user_submissions', self::DEFAULT_ON]],
            ['config.add', ['cmx_quiz_minimum_posts', self::DEFAULT_MINIMUM_POSTS]],
            ['config.add', ['cmx_quiz_minimum_questions', self::DEFAULT_MINIMUM_QUESTIONS]],
            ['config.add', ['cmx_quiz_maximum_questions', self::DEFAULT_MAXIMUM_QUESTIONS]],
            ['config.add', ['cmx_quiz_show_correct_answers', self::DEFAULT_OFF]],
            ['config.add', ['cmx_quiz_per_page', self::DEFAULT_QUIZ_PER_PAGE]],
            ['config.add', ['cmx_quiz_moderate_group_id', self::DEFAULT_OFF]],
            ['config.add', ['cmx_quiz_world', self::DEFAULT_ON]],
        ];
	}

    /**
     * Create CMX Quiz tables
     */
    public function update_schema()
    {
        return [
            'add_tables' => [
				$this->table_prefix . 'quiz' => [
					'COLUMNS' => [
						'quiz_id'                       => ['UINT', null, 'auto_increment'],
						'quiz_name'                     => ['VCHAR_UNI:255', ''],
                        'quiz_description'              => ['VCHAR_UNI:255', ''],
                        'user_id'                       => ['UINT', 0],
                        'submission_time'               => ['TIMESTAMP', null],
                        'tags_data'                     => ['TEXT_UNI', null],
                        'question_data'                 => ['TEXT_UNI', null],
                        'bbcode_uid'                    => ['VCHAR:8', null],
                        'bbcode_bitfield'               => ['VCHAR:255', null],
                        'minimum_pass_mark'             => ['UINT:3', null],
                        'pass_mark_group_id'            => ['UINT:11', null],
                        'maximum_time_limit_minutes'    => ['UINT:3', null],
                        'maximum_attempts'              => ['UINT:3', null],
                    ],

					'PRIMARY_KEY' => 'quiz_id',
				],

                $this->table_prefix . 'quiz_tag' => [
					'COLUMNS' => [
						'quiz_tag_id'                   => ['UINT', null, 'auto_increment'],
						'tag_name'                      => ['VCHAR:255', ''],
                    ],

					'PRIMARY_KEY' => 'quiz_tag_id',
				],

                $this->table_prefix . 'quiz_result' => [
					'COLUMNS' => [
						'quiz_result_id'                => ['UINT', null, 'auto_increment'],
						'quiz_id'                       => ['UINT', 0],
                        'user_id'                       => ['UINT', 0],
                        'question_data'                 => ['TEXT_UNI', null],
                        'start_time'                    => ['TIMESTAMP', null],
                        'end_time'                      => ['TIMESTAMP', null],
                        'score_percentage'              => ['DECIMAL', null],
                    ],

					'PRIMARY_KEY' => 'quiz_result_id',
				],
			],
        ];
    }

    /**
     * Remove CMX Quiz tables
     */
    public function revert_schema()
    {
        return [
			'drop_tables' => [
				$this->table_prefix . 'quiz',
                $this->table_prefix . 'quiz_tag',
                $this->table_prefix . 'quiz_result',
            ],
        ];
    }
}