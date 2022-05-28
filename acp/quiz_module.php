<?php

namespace battye\cmxquiz\acp;

class quiz_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    public function main($id, $mode)
    {
        global $phpbb_container;

        /* @var \battye\cmxquiz\quiz\manager $manager */
        $manager = $phpbb_container->get('battye.cmxquiz.manager');

        /* @var \phpbb\language\language $language */
        $language = $phpbb_container->get('language');

        /* @var \phpbb\template\template $template */
        $template = $phpbb_container->get('template');

        /* @var \phpbb\request\request $request */
        $request = $phpbb_container->get('request');

        /* @var \phpbb\config\config $config */
        $config = $phpbb_container->get('config');

        // Template information
        $this->tpl_name = 'acp_cmx_quiz_body';
        $this->page_title = $language->lang('ACP_CMX_QUIZ_CONFIGURATION');

        add_form_key('cmx_quiz_configure');

        // Get the current tags list
        $tags = $manager->get_quiz_tags();

        // Save and submit
        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('cmx_quiz_configure'))
            {
                 trigger_error('FORM_INVALID');
            }

            // We'll force this to always be at least 1
            $minimum_questions = $request->variable('cmx_quiz_minimum_questions', 0);
            $maximum_questions = $request->variable('cmx_quiz_maximum_questions', 0);

            // If someone tries to enter a minimum that's more than the maximum, set
            // both to the maximum so that it stays valid
            if ($minimum_questions > $maximum_questions)
            {
                $minimum_questions = $maximum_questions;
            }

            // Update settings
            $config->set('cmx_quiz_enabled', $request->variable('cmx_quiz_enabled', 0));
            $config->set('cmx_quiz_allow_time_limits', $request->variable('cmx_quiz_allow_time_limits', 0));
            $config->set('cmx_quiz_allow_multiple_attempts', $request->variable('cmx_quiz_allow_multiple_attempts', 0));
            $config->set('cmx_quiz_allow_move_to_usergroup', $request->variable('cmx_quiz_allow_move_to_usergroup', 0));
            $config->set('cmx_quiz_allow_user_submissions', $request->variable('cmx_quiz_allow_user_submissions', 0));
            $config->set('cmx_quiz_minimum_posts', $request->variable('cmx_quiz_minimum_posts', 0));
            $config->set('cmx_quiz_minimum_questions', ($minimum_questions < 1) ? 1 : $minimum_questions);
            $config->set('cmx_quiz_maximum_questions', $maximum_questions);
            $config->set('cmx_quiz_show_correct_answers', $request->variable('cmx_quiz_show_correct_answers', 0));
            $config->set('cmx_quiz_per_page', $request->variable('cmx_quiz_per_page', 0));
            $config->set('cmx_quiz_moderate_group_id', $request->variable('cmx_quiz_moderate_group_id', 0));

            // Update tags - we are getting a form array, key casted to int, value to string
            $deleted_tags = $tags;
            $new_tags = [];
            $updated_tags = [];

            $edited_tags = $request->variable('cmx_quiz_tags', [0 => '']);

            foreach ($edited_tags as $quiz_tag_id => $tag_name)
            {
                if ($quiz_tag_id < 0)
                {
                    $new_tags[] = $tag_name;
                }

                else 
                {
                    if ($tags[$quiz_tag_id] != $tag_name)
                    {
                        $updated_tags[$quiz_tag_id] = $tag_name;
                    }

                    // By the end of it, we'll only be left with the ones to delete
                    unset($deleted_tags[$quiz_tag_id]);
                }
            }

            // Apply the changes
            $manager->acp_tag_manager($new_tags, $updated_tags, array_keys($deleted_tags));

            // Redirect
            meta_refresh(3, $this->u_action);
            trigger_error($language->lang('ACP_CMX_QUIZ_SETTINGS_SAVED') . adm_back_link($this->u_action));
        }

        // Show the page for editing
        $template->assign_vars([
            'ACP_CMX_QUIZ_ENABLED' => $config['cmx_quiz_enabled'],
            'ACP_CMX_QUIZ_ALLOW_TIME_LIMITS' => $config['cmx_quiz_allow_time_limits'],
            'ACP_CMX_QUIZ_ALLOW_MULTIPLE_ATTEMPTS' => $config['cmx_quiz_allow_multiple_attempts'],
            'ACP_CMX_QUIZ_ALLOW_MOVE_TO_USERGROUP' => $config['cmx_quiz_allow_move_to_usergroup'],
            'ACP_CMX_QUIZ_ALLOW_USER_SUBMISSIONS' => $config['cmx_quiz_allow_user_submissions'],
            'ACP_CMX_QUIZ_MINIMUM_POSTS' => $config['cmx_quiz_minimum_posts'],
            'ACP_CMX_QUIZ_MINIMUM_QUESTIONS' => $config['cmx_quiz_minimum_questions'],
            'ACP_CMX_QUIZ_MAXIMUM_QUESTIONS' => $config['cmx_quiz_maximum_questions'],
            'ACP_CMX_QUIZ_SHOW_CORRECT_ANSWERS' => $config['cmx_quiz_show_correct_answers'],
            'ACP_CMX_QUIZ_PER_PAGE' => $config['cmx_quiz_per_page'],
            'ACP_CMX_QUIZ_MODERATE_GROUP_ID' => $config['cmx_quiz_moderate_group_id'],
            //'ACP_CMX_QUIZ_WORLD' => $config['cmx_quiz_world'],

            'ACP_CMX_QUIZ_TAGS' => $tags,
            'ACP_CMX_QUIZ_MODERATOR_GROUPS' => $manager->acp_list_groups(),

            'U_ACTION' => $this->u_action,
        ]);
    }
}