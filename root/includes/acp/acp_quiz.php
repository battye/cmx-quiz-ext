<?php
// Ultimate Quiz MOD ACP Module

if (!defined('IN_PHPBB'))
{
	exit;
}

class acp_quiz
{
	var $u_action;
	
	function main( $id, $mode )
	{
		global $db, $user, $auth, $cache, $template, $config, $phpbb_root_path, $phpbb_admin_path, $phpEx, $u_action;

		if( file_exists($phpbb_root_path . 'includes/quiz/quiz.' . $phpEx) )
		{
			global $table_prefix;
			include($phpbb_root_path . 'includes/quiz/quiz.' . $phpEx);
		}

		$quiz_configuration = new quiz_configuration;
		$quiz_configuration->load();

		$user->add_lang('acp/quiz');
		$type = request_var('t', '');

		switch($type)
		{
			case 'edit_category':
				$category_id = request_var('quiz', 0);

				// Make the database update
				if( !empty($_POST['submit']) )
				{
					if( !check_form_key('uqm_category_edit') )
					{
						// Form is invalid, link back to the edit page
						$link_to_edit_page = append_sid("{$phpbb_admin_path}index.$phpEx?i=quiz&amp;t=edit_category&amp;quiz=$category_id");
						trigger_error($user->lang['ACP_UQM_QUIZ_FORM_INVALID'] . adm_back_link($link_to_edit_page));
					}

					// Now do the actual update, fix up the category name first.
					$category = utf8_normalize_nfc( $db->sql_escape(request_var('category_name', '')) );

					$sql = "UPDATE " . QUIZ_CATEGORIES_TABLE . "
						SET quiz_category_name = '$category'
						WHERE quiz_category_id = $category_id";
					$db->sql_query($sql);

					$message = sprintf($user->lang['ACP_UQM_CATEGORY_UPDATED'], $category) . adm_back_link($this->u_action);
					trigger_error($message);					
				}

				// Deal with the form key
				add_form_key('uqm_category_edit');

				$sql = 'SELECT quiz_category_name FROM ' . QUIZ_CATEGORIES_TABLE . '
					WHERE quiz_category_id = ' . $category_id;
				$result = $db->sql_query($sql);
				$category_name = $db->sql_fetchfield('quiz_category_name');
				$db->sql_freeresult($result);

				$template->assign_vars( array(
					'S_FORM_ACTION'		=> append_sid("{$phpbb_admin_path}index.$phpEx", 'i=quiz&amp;t=edit_category&quiz=' . $category_id),
					'U_CATEGORY_VALUE'	=> $category_name,
				));
				
				$this->tpl_name = 'acp_quiz_category';
				$this->page_title = 'ACP_UQM_QUIZ';

				break;

			case 'add_category':
				if( !empty($_POST['submit']) )
				{
					if( !check_form_key('uqm_category') )
					{
						trigger_error($user->lang['ACP_UQM_QUIZ_FORM_INVALID'] . adm_back_link(append_sid("{$phpbb_admin_path}index.$phpEx?i=quiz&amp;t=add_category")));
					}

					$category = utf8_normalize_nfc( $db->sql_escape(request_var('category_name', '')) );
					$sql = "INSERT INTO " . QUIZ_CATEGORIES_TABLE . "
						(quiz_category_name) VALUES ('$category')";
					$db->sql_query($sql);

					$message = sprintf($user->lang['ACP_UQM_CATEGORY_ADDED'], $category) . adm_back_link($this->u_action);
					trigger_error($message);
				}

				// Deal with the form key
				add_form_key('uqm_category');

				$template->assign_vars( array(
					'S_FORM_ACTION'		=> append_sid("{$phpbb_admin_path}index.$phpEx", 'i=quiz&amp;t=add_category'),
					'U_CATEGORY_VALUE'	=> '',
				));
				
				$this->tpl_name = 'acp_quiz_category';
				$this->page_title = 'ACP_UQM_QUIZ';

				break;

			// We won't put a break on this case so that if the user clicks "no" they will see the default.
			case 'delete_category':
			$category_id = request_var('quiz', 0);

				if( confirm_box(true) )
				{
					// Get list of questions in the category
					$sql = 'SELECT w.question_id 
						FROM ' . QUIZ_QUESTIONS_TABLE . ' w, ' . QUIZ_TABLE . ' q
						WHERE w.question_quiz = q.quiz_id
						AND q.quiz_category = ' . $category_id;
					$result = $db->sql_query($sql);

					$question_id_list = array();
					while( $row = $db->sql_fetchrow($result) )
					{
						$question_id_list[] = $row['question_id']; 
					}

					$db->sql_freeresult($result);

					$delete_sql = array();
					$delete_sql[] = 'DELETE FROM ' . QUIZ_TABLE . ' 
							 WHERE quiz_category = ' . $category_id;
					$delete_sql[] = 'DELETE FROM ' . QUIZ_QUESTIONS_TABLE . '
							 WHERE ' . $db->sql_in_set('question_id', $question_id_list);
					$delete_sql[] = 'DELETE FROM ' . QUIZ_STATISTICS_TABLE . '
							 WHERE ' . $db->sql_in_set('quiz_question_id', $question_id_list);
					$delete_sql[] = 'DELETE FROM ' . QUIZ_CATEGORIES_TABLE . '
							 WHERE quiz_category_id = ' . $category_id;
					
					foreach($delete_sql as $query)
					{
						$db->sql_query($query);
					}

					trigger_error($user->lang['ACP_UQM_DELETE_SUCCESSFUL'] . adm_back_link($this->u_action));
				}

				else 
				{
					confirm_box(false, 'ACP_UQM_DELETE_TITLE');
				}
				
			default:

			// If the configuration values have been updated, then do some updating...
			if( !empty($_POST['submit']) )
			{
				$configuration_list = $quiz_configuration->config_array();
				foreach($configuration_list as $name)
				{
					// Check the type of setting, and use that as a parameter
					$type = ($quiz_configuration->value($name, true) == 'radio') ? 0 : '';
					$new_value = utf8_normalize_nfc(request_var($name, $type));
					$quiz_configuration->update($name, $new_value);
				}

				$message = $user->lang['ACP_UQM_CONFIG_UPDATED'] . adm_back_link($this->u_action);
				trigger_error($message);
			}

			$this->tpl_name = 'acp_quiz';
			$this->page_title = 'ACP_UQM_QUIZ';

			// Get the category list
			$sql = 'SELECT * FROM ' . QUIZ_CATEGORIES_TABLE . ' ORDER BY quiz_category_name ASC';
			$result = $db->sql_query($sql);

			while( $row = $db->sql_fetchrow($result) )
			{
				$category_name	= $row['quiz_category_name'];
				$category_id	= $row['quiz_category_id'];

				$template->assign_block_vars('category_row', array(
					'CATEGORY_NAME'		=> $category_name,
					'EDIT_LINK'		=> append_sid("{$phpbb_admin_path}index.$phpEx", 'i=quiz&amp;t=edit_category&amp;quiz=' . $category_id),
					'DELETE_LINK'		=> append_sid("{$phpbb_admin_path}index.$phpEx", 'i=quiz&amp;t=delete_category&amp;quiz=' . $category_id),
				));
			}

			$db->sql_freeresult($result);

			// Get the configuration values
			$configuration_list = $quiz_configuration->config_array();

			foreach($configuration_list as $name)
			{
				$lang_name 	= $user->lang['ACP_UQM_CONFIG_DEFINITIONS'][$name];
				$lang_explain 	= $user->lang['ACP_UQM_CONFIG_DEFINITIONS'][$name . '_explain'];

				$template->assign_block_vars('configuration_row', array(
					'CONFIGURATION_LANG'		=> $lang_name,
					'CONFIGURATION_LANG_EXPLAIN'	=> $lang_explain,

					'CONFIGURATION_NAME'	=> $name,
					'CONFIGURATION_VALUE'	=> $quiz_configuration->value($name),
					'CONFIGURATION_TYPE'	=> $quiz_configuration->value($name, true),
				));
			}

			$template->assign_vars( array(
				'S_FORM_ACTION'		=> append_sid($this->u_action),
				'U_ADD_CATEGORY'	=> sprintf($user->lang['ACP_UQM_ADD_CATEGORY'], '<a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", 'i=quiz&amp;t=add_category') . '">', '</a>'),
			));
		}
	}
}

?>
